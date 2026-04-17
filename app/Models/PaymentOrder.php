<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PaymentOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'company_id', 'supplier_id', 'date', 'total', 'status',
        'payment_method', 'payment_reference', 'cheque_due_date', 'notes',
        'created_by', 'approved_by',
    ];

    protected $casts = [
        'date' => 'date', 'total' => 'decimal:2', 'cheque_due_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PaymentOrderItem::class);
    }

    /**
     * Líneas de cobro (e-cheq) de cartera vinculadas a esta OP (borrador reserva o pagada).
     */
    public function portfolioEcheqPayments()
    {
        return $this->hasMany(CollectionReceiptPayment::class, 'payment_order_id')
            ->where('line_type', 'echeq')
            ->orderBy('id');
    }

    /**
     * Medios de liquidación de la orden (transferencia, cheque, efectivo, e-cheq cartera, etc.).
     */
    public function paymentLines()
    {
        return $this->hasMany(PaymentOrderPaymentLine::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Etiqueta corta para listados (índice, conciliación).
     */
    public function paymentMethodsLabel(): string
    {
        $this->loadMissing('paymentLines');

        if ($this->paymentLines->isEmpty()) {
            if ($this->relationLoaded('portfolioEcheqPayments') ? $this->portfolioEcheqPayments->isNotEmpty() : $this->portfolioEcheqPayments()->exists()) {
                $n = $this->relationLoaded('portfolioEcheqPayments')
                    ? $this->portfolioEcheqPayments->count()
                    : $this->portfolioEcheqPayments()->count();

                return 'E-cheq cartera ('.$n.')';
            }

            return match ($this->payment_method) {
                'transferencia' => 'Transferencia',
                'cheque' => 'Cheque',
                'efectivo' => 'Efectivo',
                default => '—',
            };
        }

        $onlyPortfolio = $this->paymentLines->every(fn ($l) => $l->kind === 'portfolio_echeq');
        if ($onlyPortfolio) {
            return 'E-cheq cartera ('.$this->paymentLines->count().')';
        }

        if ($this->paymentLines->count() > 1) {
            return 'Varios medios ('.$this->paymentLines->count().')';
        }

        return match ($this->paymentLines->first()->kind) {
            'transferencia' => 'Transferencia',
            'cheque' => 'Cheque',
            'efectivo' => 'Efectivo',
            'portfolio_echeq' => 'E-cheq cartera',
            default => $this->paymentLines->first()->kind,
        };
    }

    public function reconciledMovements(): MorphMany
    {
        return $this->morphMany(BankMovement::class, 'reconciled', 'reconciled_type', 'reconciled_id');
    }

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

        return sprintf('OP-%s-%05d', $year, $nextNumber);
    }
}
