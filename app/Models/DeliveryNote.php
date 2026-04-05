<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'lab_branch_id', 'remito_number', 'purchase_order_id', 'supplier_id', 'date',
        'status', 'notes', 'received_by',
    ];

    protected $casts = ['date' => 'date'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function labBranch(): BelongsTo
    {
        return $this->belongsTo(LabBranch::class, 'lab_branch_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(DeliveryNoteItem::class);
    }

    /** Vínculo histórico por columna purchase_invoices.delivery_note_id (se mantiene alineada con la pivote). */
    public function legacyLinkedPurchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'delivery_note_id');
    }

    public function purchaseInvoices(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseInvoice::class, 'delivery_note_purchase_invoice')
            ->withTimestamps();
    }

    public function hasPurchaseInvoice(): bool
    {
        return $this->purchaseInvoices()->exists()
            || $this->legacyLinkedPurchaseInvoices()->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente', 'aceptado' => 'Aceptado',
            'con_diferencias' => 'Con Diferencias', default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'amber', 'aceptado' => 'green',
            'con_diferencias' => 'red', default => 'gray',
        };
    }
}
