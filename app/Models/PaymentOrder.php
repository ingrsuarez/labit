<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'supplier_id', 'date', 'total', 'status',
        'payment_method', 'payment_reference', 'notes',
        'created_by', 'approved_by',
    ];

    protected $casts = [
        'date' => 'date', 'total' => 'decimal:2',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(PaymentOrderItem::class); }

    public function recalculate(): void
    {
        $this->total = $this->items()->sum('amount');
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador', 'aprobada' => 'Aprobada',
            'pagada' => 'Pagada', 'anulada' => 'Anulada', default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'gray', 'aprobada' => 'blue',
            'pagada' => 'green', 'anulada' => 'red', default => 'gray',
        };
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "OP-{$year}-%")->orderByDesc('number')->first();
        $nextNumber = $last ? ((int) substr($last->number, -5)) + 1 : 1;
        return sprintf("OP-%s-%05d", $year, $nextNumber);
    }
}
