<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    protected $fillable = [
        'company_id',
        'credit_note_number',
        'voucher_type',
        'point_of_sale_id',
        'customer_id',
        'sales_invoice_id',
        'issue_date',
        'reason',
        'subtotal',
        'iva_21',
        'iva_10_5',
        'iva_27',
        'percepciones',
        'otros_impuestos',
        'total',
        'status',
        'is_electronic',
        'cae',
        'cae_expiration',
        'afip_voucher_number',
        'afip_result',
        'afip_response',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'cae_expiration' => 'date',
        'subtotal' => 'decimal:2',
        'iva_21' => 'decimal:2',
        'iva_10_5' => 'decimal:2',
        'iva_27' => 'decimal:2',
        'percepciones' => 'decimal:2',
        'otros_impuestos' => 'decimal:2',
        'total' => 'decimal:2',
        'is_electronic' => 'boolean',
        'afip_response' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class)->orderBy('sort_order');
    }

    public function recalculate(): void
    {
        $this->load('items');

        $subtotal = 0;
        $iva21 = 0;
        $iva105 = 0;
        $iva27 = 0;

        foreach ($this->items as $item) {
            $lineNet = $item->quantity * $item->unit_price;
            $subtotal += $lineNet;

            match (true) {
                $item->iva_rate == 21 => $iva21 += $item->iva_amount,
                $item->iva_rate == 10.5 => $iva105 += $item->iva_amount,
                $item->iva_rate == 27 => $iva27 += $item->iva_amount,
                default => null,
            };
        }

        $this->subtotal = round($subtotal, 2);
        $this->iva_21 = round($iva21, 2);
        $this->iva_10_5 = round($iva105, 2);
        $this->iva_27 = round($iva27, 2);
        $this->total = round(
            $subtotal + $iva21 + $iva105 + $iva27 + $this->percepciones + $this->otros_impuestos,
            2
        );
        $this->save();
    }

    public function getFullNumberAttribute(): string
    {
        $pos = $this->pointOfSale;
        $posCode = $pos ? $pos->code : '00001';
        $number = str_pad($this->afip_voucher_number ?? $this->credit_note_number, 8, '0', STR_PAD_LEFT);
        return "NC {$this->voucher_type} {$posCode}-{$number}";
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'confirmada' => 'Confirmada',
            'anulada' => 'Anulada',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'yellow',
            'confirmada' => 'green',
            'anulada' => 'red',
            default => 'gray',
        };
    }

    public function getIsAfipApprovedAttribute(): bool
    {
        return $this->afip_result === 'A';
    }
}
