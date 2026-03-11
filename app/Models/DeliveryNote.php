<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'remito_number', 'purchase_order_id', 'supplier_id', 'date',
        'status', 'notes', 'received_by',
    ];

    protected $casts = ['date' => 'date'];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
    public function items() { return $this->hasMany(DeliveryNoteItem::class); }

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
