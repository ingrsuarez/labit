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
        'protocol_number', 'date', 'customer_id', 'veterinarian_id',
        'species_id', 'animal_name', 'owner_name', 'owner_phone', 'owner_email',
        'breed', 'age', 'status', 'observations', 'total_price', 'created_by',
        'lab_branch_id',
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
