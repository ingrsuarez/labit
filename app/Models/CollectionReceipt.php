<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'customer_id', 'date', 'total', 'status',
        'payment_method', 'payment_reference', 'notes',
        'created_by', 'confirmed_by',
    ];

    protected $casts = [
        'date' => 'date', 'total' => 'decimal:2',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function confirmer() { return $this->belongsTo(User::class, 'confirmed_by'); }
    public function items() { return $this->hasMany(CollectionReceiptItem::class); }

    public function recalculate(): void
    {
        $this->total = $this->items()->sum('amount');
        $this->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador',
            'confirmado' => 'Confirmado',
            'anulado' => 'Anulado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'gray',
            'confirmado' => 'green',
            'anulado' => 'red',
            default => 'gray',
        };
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('number', 'like', "RC-{$year}-%")->orderByDesc('number')->first();
        $nextNumber = $last ? ((int) substr($last->number, -5)) + 1 : 1;
        return sprintf("RC-%s-%05d", $year, $nextNumber);
    }
}
