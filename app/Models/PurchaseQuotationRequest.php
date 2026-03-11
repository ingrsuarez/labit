<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseQuotationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'supplier_id',
        'date',
        'valid_until',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseQuotationRequestItem::class)->orderBy('sort_order');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'quotation_request_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador',
            'enviada' => 'Enviada',
            'recibida' => 'Recibida',
            'cancelada' => 'Cancelada',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'gray',
            'enviada' => 'blue',
            'recibida' => 'green',
            'cancelada' => 'red',
            default => 'gray',
        };
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "SC-{$year}-%")
            ->orderByDesc('number')
            ->first();

        $nextNumber = $last ? ((int) substr($last->number, -5)) + 1 : 1;
        return sprintf("SC-%s-%05d", $year, $nextNumber);
    }
}
