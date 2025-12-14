<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NonConformityFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'non_conformity_id',
        'user_id',
        'notes',
        'status_change',
    ];

    /**
     * Relación con la no conformidad
     */
    public function nonConformity()
    {
        return $this->belongsTo(NonConformity::class);
    }

    /**
     * Usuario que hizo el seguimiento
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
