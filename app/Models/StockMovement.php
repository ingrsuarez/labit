<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id',
        'type',
        'quantity',
        'previous_stock',
        'new_stock',
        'reason',
        'lot_number',
        'expiration_date',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'previous_stock' => 'decimal:2',
        'new_stock' => 'decimal:2',
        'expiration_date' => 'date',
    ];

    public function supply()
    {
        return $this->belongsTo(Supply::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'ajuste' => 'Ajuste',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'entrada' => 'green',
            'salida' => 'red',
            'ajuste' => 'amber',
            default => 'gray',
        };
    }

    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            'compra' => 'Compra',
            'consumo' => 'Consumo',
            'ajuste_manual' => 'Ajuste Manual',
            'devolucion' => 'Devolución',
            default => $this->reason,
        };
    }
}
