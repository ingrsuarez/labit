<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CircularSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'circular_id',
        'employee_id',
        'read_at',
        'signed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    /**
     * Circular asociada
     */
    public function circular()
    {
        return $this->belongsTo(Circular::class);
    }

    /**
     * Empleado que firmó
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Verificar si está firmada
     */
    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    /**
     * Verificar si fue leída
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}

