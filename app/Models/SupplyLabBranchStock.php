<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyLabBranchStock extends Model
{
    protected $table = 'supply_lab_branch_stock';

    protected $fillable = [
        'supply_id',
        'lab_branch_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function labBranch(): BelongsTo
    {
        return $this->belongsTo(LabBranch::class, 'lab_branch_id');
    }
}
