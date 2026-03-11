<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'voucher_type', 'point_of_sale', 'supplier_id',
        'delivery_note_id', 'purchase_order_id', 'issue_date', 'due_date',
        'subtotal', 'iva_21', 'iva_10_5', 'iva_27', 'percepciones', 'otros_impuestos',
        'total', 'amount_paid', 'balance', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date', 'due_date' => 'date',
        'subtotal' => 'decimal:2', 'iva_21' => 'decimal:2', 'iva_10_5' => 'decimal:2',
        'iva_27' => 'decimal:2', 'percepciones' => 'decimal:2', 'otros_impuestos' => 'decimal:2',
        'total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'balance' => 'decimal:2',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function deliveryNote() { return $this->belongsTo(DeliveryNote::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function items() { return $this->hasMany(PurchaseInvoiceItem::class); }
    public function paymentOrderItems() { return $this->hasMany(PaymentOrderItem::class); }

    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $ivaByRate = $this->items()->selectRaw('iva_rate, SUM(iva_amount) as total_iva')->groupBy('iva_rate')->pluck('total_iva', 'iva_rate');
        $this->iva_21 = $ivaByRate[21.00] ?? $ivaByRate['21.00'] ?? 0;
        $this->iva_10_5 = $ivaByRate[10.50] ?? $ivaByRate['10.50'] ?? 0;
        $this->iva_27 = $ivaByRate[27.00] ?? $ivaByRate['27.00'] ?? 0;
        $this->total = $this->subtotal + $this->iva_21 + $this->iva_10_5 + $this->iva_27 + $this->percepciones + $this->otros_impuestos;
        $this->balance = $this->total - $this->amount_paid;
        $this->save();
    }

    public function updatePaymentStatus(): void
    {
        if ($this->balance <= 0) {
            $this->status = 'pagada';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'parcialmente_pagada';
        } else {
            $this->status = 'pendiente';
        }
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente', 'parcialmente_pagada' => 'Parcialmente Pagada',
            'pagada' => 'Pagada', 'anulada' => 'Anulada', default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'amber', 'parcialmente_pagada' => 'blue',
            'pagada' => 'green', 'anulada' => 'red', default => 'gray',
        };
    }

    public function getFullNumberAttribute(): string
    {
        $pv = $this->point_of_sale ? str_pad($this->point_of_sale, 5, '0', STR_PAD_LEFT) . '-' : '';
        return "FC {$this->voucher_type} {$pv}{$this->invoice_number}";
    }
}
