<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    use Auditable, HasFactory;

    public const LEVEL_MINIMAL = 'minimal';

    public const LEVEL_STANDARD = 'standard';

    public const PATIENT_DATA_LEVELS = [
        self::LEVEL_MINIMAL => 'Mínimo (sin DNI)',
        self::LEVEL_STANDARD => 'Estándar (incluye DNI)',
    ];

    protected $fillable = [
        'name',
        'api_key_hash',
        'key_preview',
        'lab_branch_id',
        'company_id',
        'active',
        'patient_data_level',
        'last_used_at',
        'requests_count',
        'notes',
        'created_by',
    ];

    protected $hidden = [
        'api_key_hash',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_used_at' => 'datetime',
        'requests_count' => 'integer',
    ];

    public function labBranch(): BelongsTo
    {
        return $this->belongsTo(LabBranch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Genera una API key plana con prefijo `labit_` para detección de leaks.
     * Solo se devuelve una vez al crearse o regenerarse.
     */
    public static function generateKey(): string
    {
        return 'labit_'.Str::random(40);
    }

    /**
     * Calcula el hash SHA-256 de una key plana. Es lo único que persistimos.
     */
    public static function hashKey(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Construye el preview visible (primeros 12 chars + elipsis) a partir
     * de la key plana, para mostrar en listado sin exponer la key real.
     */
    public static function buildPreview(string $plain): string
    {
        return substr($plain, 0, 12).'…';
    }

    /**
     * Indica si esta key tiene acceso a protocolos de todas las sedes.
     * Sucede cuando lab_branch_id es null.
     */
    public function isGlobal(): bool
    {
        return $this->lab_branch_id === null;
    }

    /**
     * Indica si esta key está autorizada a recibir el DNI del paciente
     * en las respuestas de la API. Por defecto es false (level=minimal).
     */
    public function includesDni(): bool
    {
        return $this->patient_data_level === self::LEVEL_STANDARD;
    }
}
