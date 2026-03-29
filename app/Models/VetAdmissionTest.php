<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VetAdmissionTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'vet_admission_id', 'test_id', 'price', 'nbu_units',
        'status', 'result', 'unit', 'reference_value', 'method',
        'observations', 'analyzed_by', 'analyzed_at',
        'is_validated', 'validated_by', 'validated_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_validated' => 'boolean',
        'analyzed_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function vetAdmission()
    {
        return $this->belongsTo(VetAdmission::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function analyzer()
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }

    public function validatorUser()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En Proceso',
            'completed' => 'Completado',
            default => $this->status,
        };
    }

    public function hasResult(): bool
    {
        return ! empty($this->result);
    }
}
