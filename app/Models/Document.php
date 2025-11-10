<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'employee_id',
        'creator',
        'name',
        'comments',
        'fecha_creacion',
        'fecha_vencimiento',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'creator');
    }

    public function files()
    {
        return $this->hasMany(DocumentFile::class);
    }
}
