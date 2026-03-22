<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'invoice_number', 'voucher_type', 'point_of_sale', 'point_of_sale_id', 'customer_id',
        'quote_id', 'admission_id', 'issue_date', 'due_date',
        'subtotal', 'iva_21', 'iva_10_5', 'iva_27', 'percepciones', 'otros_impuestos',
        'total', 'amount_collected', 'balance', 'status', 'notes', 'created_by',
        'cae', 'cae_expiration', 'afip_voucher_number', 'afip_result', 'afip_response', 'is_electronic',
    ];

    protected $casts = [
        'issue_date' => 'date', 'due_date' => 'date', 'cae_expiration' => 'date',
        'subtotal' => 'decimal:2', 'iva_21' => 'decimal:2', 'iva_10_5' => 'decimal:2',
        'iva_27' => 'decimal:2', 'percepciones' => 'decimal:2', 'otros_impuestos' => 'decimal:2',
        'total' => 'decimal:2', 'amount_collected' => 'decimal:2', 'balance' => 'decimal:2',
        'afip_response' => 'array', 'is_electronic' => 'boolean',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function quote() { return $this->belongsTo(Quote::class); }
    public function pointOfSale() { return $this->belongsTo(PointOfSale::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(SalesInvoiceItem::class)->orderBy('sort_order'); }
    public function collectionReceiptItems() { return $this->hasMany(CollectionReceiptItem::class); }
    public function creditNotes() { return $this->hasMany(CreditNote::class); }

    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $ivaByRate = $this->items()->reorder()
            ->selectRaw('iva_rate, SUM(iva_amount) as total_iva')
            ->groupBy('iva_rate')
            ->pluck('total_iva', 'iva_rate');

        $this->iva_21 = $ivaByRate[21.00] ?? $ivaByRate['21.00'] ?? 0;
        $this->iva_10_5 = $ivaByRate[10.50] ?? $ivaByRate['10.50'] ?? 0;
        $this->iva_27 = $ivaByRate[27.00] ?? $ivaByRate['27.00'] ?? 0;
        $this->total = $this->subtotal + $this->iva_21 + $this->iva_10_5 + $this->iva_27
                      + $this->percepciones + $this->otros_impuestos;
        $this->balance = $this->total - $this->amount_collected;
        $this->save();
    }

    public function updateCollectionStatus(): void
    {
        if ($this->balance <= 0) {
            $this->status = 'cobrada';
        } elseif ($this->amount_collected > 0) {
            $this->status = 'parcialmente_cobrada';
        } else {
            $this->status = 'pendiente';
        }
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'parcialmente_cobrada' => 'Parcialmente Cobrada',
            'cobrada' => 'Cobrada',
            'anulada' => 'Anulada',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'amber',
            'parcialmente_cobrada' => 'blue',
            'cobrada' => 'green',
            'anulada' => 'red',
            default => 'gray',
        };
    }

    public function getFullNumberAttribute(): string
    {
        $pv = $this->pointOfSale ? $this->pointOfSale->code . '-' : ($this->point_of_sale ? str_pad($this->point_of_sale, 5, '0', STR_PAD_LEFT) . '-' : '');
        return "FC {$this->voucher_type} {$pv}{$this->invoice_number}";
    }

    public function getIsAfipApprovedAttribute(): bool
    {
        return $this->afip_result === 'A';
    }
}
