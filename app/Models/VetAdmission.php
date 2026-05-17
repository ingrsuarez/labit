<?php

namespace App\Models;

use App\Services\ProtocolStatusCalculator;
use App\Support\ProtocolCountableTestFilter;
use App\Support\VetAdmissionTestDisplayOrder;
use App\Traits\Auditable;
use App\Traits\GeneratesProtocolNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class VetAdmission extends Model
{
    use Auditable, GeneratesProtocolNumber, HasFactory, ProtocolCountableTestFilter;

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
        if ($this->status === 'cancelled') {
            return 'Cancelado';
        }

        return ProtocolStatusCalculator::labelFor($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'red';
        }

        return ProtocolStatusCalculator::colorFor($this->status);
    }

    public function getCalculatedStatusAttribute(): string
    {
        $countable = $this->filterCountableVetTests($this->vetTests);

        return app(ProtocolStatusCalculator::class)->calculate($countable);
    }

    public function syncWorkStatusFromTests(): void
    {
        $this->unsetRelation('vetTests');
        $this->load(['vetTests.test.childTests', 'vetTests.test.children']);
        $this->update(['status' => $this->calculated_status]);
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
