<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PayrollPayment extends Model
{
    protected $fillable = [
        'company_id',
        'bank_account_id',
        'year',
        'month',
        'period_label',
        'payment_date',
        'total',
        'employee_count',
        'status',
        'notes',
        'created_by',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function reconciledMovements(): MorphMany
    {
        return $this->morphMany(BankMovement::class, 'reconciled', 'reconciled_type', 'reconciled_id');
    }

    public function isConfirmado(): bool
    {
        return $this->status === 'confirmado';
    }

    public function recalculate(): void
    {
        $this->total = $this->payrolls()->sum('neto_a_cobrar');
        $this->employee_count = $this->payrolls()->count();
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador',
            'confirmado' => 'Confirmado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'yellow',
            'confirmado' => 'green',
            default => 'gray',
        };
    }
}
