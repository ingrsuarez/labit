<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PurchaseCreditNote extends Model
{
    protected $fillable = [
        'company_id', 'lab_branch_id', 'supplier_id', 'purchase_invoice_id',
        'credit_note_number', 'voucher_type', 'point_of_sale', 'issue_date',
        'subtotal', 'iva_21', 'iva_10_5', 'iva_27', 'percepciones', 'otros_impuestos',
        'total', 'notes', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'subtotal' => 'decimal:2',
        'iva_21' => 'decimal:2',
        'iva_10_5' => 'decimal:2',
        'iva_27' => 'decimal:2',
        'percepciones' => 'decimal:2',
        'otros_impuestos' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function labBranch(): BelongsTo
    {
        return $this->belongsTo(LabBranch::class, 'lab_branch_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseCreditNoteItem::class);
    }

    public function recalculate(): void
    {
        $this->subtotal = round((float) $this->items()->reorder()->sum(DB::raw('quantity * unit_price')), 2);
        $ivaByRate = $this->items()->reorder()
            ->selectRaw('iva_rate, SUM(iva_amount) as total_iva')
            ->groupBy('iva_rate')
            ->pluck('total_iva', 'iva_rate');
        $this->iva_21 = $ivaByRate[21.00] ?? $ivaByRate['21.00'] ?? 0;
        $this->iva_10_5 = $ivaByRate[10.50] ?? $ivaByRate['10.50'] ?? 0;
        $this->iva_27 = $ivaByRate[27.00] ?? $ivaByRate['27.00'] ?? 0;
        $this->total = round(
            (float) $this->subtotal + (float) $this->iva_21 + (float) $this->iva_10_5 + (float) $this->iva_27
            + (float) $this->percepciones + (float) $this->otros_impuestos,
            2
        );
        $this->save();
    }

    public function getFullNumberAttribute(): string
    {
        $pv = ($this->point_of_sale !== null && $this->point_of_sale !== '')
            ? str_pad((string) $this->point_of_sale, 5, '0', STR_PAD_LEFT).'-'
            : '';

        return "NC {$this->voucher_type} {$pv}{$this->credit_note_number}";
    }
}
