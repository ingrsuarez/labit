<?php

namespace App\Models;

use App\Support\VetAdmissionTestDisplayOrder;
use App\Traits\Auditable;
use App\Traits\GeneratesProtocolNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class VetAdmission extends Model
{
    use Auditable, GeneratesProtocolNumber, HasFactory;

    protected $fillable = [
        'protocol_number', 'external_equipment_sample_id', 'date', 'customer_id', 'veterinarian_id',
        'species_id', 'animal_name', 'owner_name', 'owner_phone', 'owner_email',
        'breed', 'age', 'status', 'sent_at', 'observations', 'total_price', 'created_by',
        'lab_branch_id',
    ];

    protected $casts = [
        'date' => 'date',
        'total_price' => 'decimal:2',
        'sent_at' => 'datetime',
    ];

    public static function generateProtocolNumber(): string
    {
        return static::generatePrefixedProtocolNumber('V');
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
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

    public function determinationProfileApplications()
    {
        return $this->morphMany(DeterminationProfileApplication::class, 'applicable')->latest();
    }

    /**
     * Determinaciones en orden de informe (jerarquía + sort_order), todas las filas.
     *
     * @return Collection<int, array{vt: VetAdmissionTest, level: int, isParent: bool, isSubParent: bool, isChild: bool}>
     */
    public function getVetTestsOrderedForDisplay(): Collection
    {
        return VetAdmissionTestDisplayOrder::orderedEntries($this, false);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function labBranch()
    {
        return $this->belongsTo(LabBranch::class);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->sent_at !== null && $this->status === 'validated') {
            return 'Enviado';
        }

        return match ($this->status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En Proceso',
            'completed' => 'Completado',
            'validated' => 'Validado',
            'cancelled' => 'Cancelado',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->sent_at !== null && $this->status === 'validated') {
            return 'sky';
        }

        return match ($this->status) {
            'pending' => 'yellow',
            'in_progress' => 'blue',
            'completed' => 'green',
            'validated' => 'purple',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getCalculatedStatusAttribute(): string
    {
        // Excluir padres-título: tests sin resultado cuyo Test tiene hijos
        // (agrupadores que no llevan resultado propio).
        // Solo aplica si la relación test.childTests está cargada; si no,
        // se incluye por defecto para no ocultar determinaciones pendientes reales.
        $countable = $this->vetTests->filter(function (VetAdmissionTest $vt) {
            if ($vt->hasResult() || $vt->is_validated) {
                return true;
            }

            if (! $vt->relationLoaded('test') || ! $vt->test) {
                return true;
            }
            $t = $vt->test;
            if ($t->relationLoaded('childTests') && $t->childTests->isNotEmpty()) {
                return false;
            }
            if ($t->relationLoaded('children') && $t->children->isNotEmpty()) {
                return false;
            }

            return true;
        });

        $total = $countable->count();

        if ($total === 0) {
            return 'pending';
        }

        $validated = $countable->where('is_validated', true)->count();
        $completed = $countable->whereIn('status', ['completed', 'validated'])->count();

        if ($validated === $total) {
            return 'validated';
        }

        if ($completed === $total || $validated > 0) {
            return 'completed';
        }

        return 'pending';
    }

    public function invoiceProtocols()
    {
        return $this->morphMany(InvoiceProtocol::class, 'protocol');
    }

    public function salesInvoices()
    {
        return $this->morphToMany(SalesInvoice::class, 'protocol', 'invoice_protocols')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function isInvoiced(): bool
    {
        return $this->invoiceProtocols()->exists();
    }

    public function scopeUninvoiced($query)
    {
        return $query->whereDoesntHave('invoiceProtocols');
    }

    public function scopeInvoiced($query)
    {
        return $query->whereHas('invoiceProtocols');
    }
}
