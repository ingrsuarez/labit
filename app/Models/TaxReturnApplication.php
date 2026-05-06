<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxReturnApplication extends Model
{
    protected $fillable = [
        'tax_return_id',
        'purchase_invoice_perception_id',
        'purchase_credit_note_perception_id',
        'amount_applied',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
    ];

    public function taxReturn(): BelongsTo
    {
        return $this->belongsTo(TaxReturn::class);
    }

    public function purchaseInvoicePerception(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoicePerception::class);
    }

    public function purchaseCreditNotePerception(): BelongsTo
    {
        return $this->belongsTo(PurchaseCreditNotePerception::class);
    }

    public function sourceLabel(): string
    {
        $this->loadMissing([
            'purchaseInvoicePerception.purchaseInvoice',
            'purchaseCreditNotePerception.purchaseCreditNote',
        ]);

        if ($this->purchase_invoice_perception_id && $this->purchaseInvoicePerception?->purchaseInvoice) {
            return 'FC '.$this->purchaseInvoicePerception->purchaseInvoice->full_number;
        }
        if ($this->purchase_credit_note_perception_id && $this->purchaseCreditNotePerception?->purchaseCreditNote) {
            return 'NC '.$this->purchaseCreditNotePerception->purchaseCreditNote->full_number;
        }

        return '—';
    }

    public function sourceDate(): ?string
    {
        $this->loadMissing([
            'purchaseInvoicePerception.purchaseInvoice',
            'purchaseCreditNotePerception.purchaseCreditNote',
        ]);

        if ($this->purchase_invoice_perception_id && $this->purchaseInvoicePerception?->purchaseInvoice) {
            return optional($this->purchaseInvoicePerception->purchaseInvoice->issue_date)->toDateString();
        }
        if ($this->purchase_credit_note_perception_id && $this->purchaseCreditNotePerception?->purchaseCreditNote) {
            return optional($this->purchaseCreditNotePerception->purchaseCreditNote->issue_date)->toDateString();
        }

        return null;
    }
}
