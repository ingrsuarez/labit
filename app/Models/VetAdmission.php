<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\GeneratesProtocolNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VetAdmission extends Model
{
    use Auditable, HasFactory, GeneratesProtocolNumber;

    protected $fillable = [
        'protocol_number', 'date', 'customer_id', 'veterinarian_id',
        'species_id', 'animal_name', 'owner_name', 'owner_phone', 'owner_email',
        'breed', 'age', 'status', 'observations', 'total_price', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'total_price' => 'decimal:2',
    ];

    public static function generateProtocolNumber(): string
    {
        return static::generatePrefixedProtocolNumber('V');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function veterinarian()
    {
        return $this->belongsTo(Veterinarian::class);
    }

    public function species()
    {
        return $this->belongsTo(Species::class);
    }

    public function vetTests()
    {
        return $this->hasMany(VetAdmissionTest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En Proceso',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getCalculatedStatusAttribute(): string
    {
        $total = $this->vetTests->count();
        if ($total === 0) {
            return 'pending';
        }

        $validated = $this->vetTests->where('is_validated', true)->count();
        $completed = $this->vetTests->where('status', 'completed')->count();

        if ($validated === $total) {
            return 'validated';
        }
        if ($completed === $total || $validated > 0) {
            return 'completed';
        }

        return 'pending';
    }
}
