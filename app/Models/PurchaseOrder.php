<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'supplier_id', 'quotation_request_id', 'date', 'expected_delivery_date',
        'status', 'subtotal', 'tax_rate', 'tax_amount', 'total', 'notes',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function quotationRequest() { return $this->belongsTo(PurchaseQuotationRequest::class, 'quotation_request_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order'); }
    public function deliveryNotes() { return $this->hasMany(DeliveryNote::class); }

    public function recalculate(): void
    {
        $this->subtotal = $this->items()->sum('total');
        $this->tax_amount = round($this->subtotal * ($this->tax_rate / 100), 2);
        $this->total = $this->subtotal + $this->tax_amount;
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador', 'aprobada' => 'Aprobada', 'parcial' => 'Parcialmente Recibida',
            'recibida' => 'Recibida', 'cancelada' => 'Cancelada', default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'gray', 'aprobada' => 'blue', 'parcial' => 'amber',
            'recibida' => 'green', 'cancelada' => 'red', default => 'gray',
        };
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "OC-{$year}-%")->orderByDesc('number')->first();
        $nextNumber = $last ? ((int) substr($last->number, -5)) + 1 : 1;
        return sprintf("OC-%s-%05d", $year, $nextNumber);
    }
}
