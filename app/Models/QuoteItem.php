<?php

namespace App\Models;

use App\Services\QuoteItemChildrenSnapshotBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'test_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
        'children_snapshot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'total' => 'float',
        'sort_order' => 'integer',
        'children_snapshot' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /**
     * @return array<int, array{test_id?: int|null, name: string, depth: int}>
     */
    public function resolvedChildren(): array
    {
        if (! empty($this->children_snapshot)) {
            return $this->children_snapshot;
        }

        if ($this->test_id) {
            $test = $this->relationLoaded('test') ? $this->test : Test::find($this->test_id);
            if ($test && ($test->childTests()->exists() || $test->children()->exists())) {
                return app(QuoteItemChildrenSnapshotBuilder::class)->build($test);
            }
        }

        return [];
    }
}
