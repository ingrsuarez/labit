<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'low',
        'high',
        'other_reference',
        'instructions',
        'parent',
        'default_reference_category_id',
        'decimals',
        'negative',
        'positive',
        'questions',
        'method',
        'price',
        'cost',
        'work_sheet',
        'material',
        'formula',
        'box',
        'nbu',
        'categories',
        'sort_order',
    ];

    protected $casts = [
        'categories' => 'array',
    ];

    /**
     * Relación con determinaciones de muestras
     */
    public function sampleDeterminations()
    {
        return $this->hasMany(SampleDetermination::class);
    }

    /**
     * Relación con el test padre (legacy - para compatibilidad)
     *
     * @deprecated Usar parentTests() para múltiples padres
     */
    public function parentTest()
    {
        return $this->belongsTo(Test::class, 'parent');
    }

    /**
     * Relación con tests hijos (legacy - para compatibilidad)
     *
     * @deprecated Usar childTests() para múltiples padres
     */
    public function children()
    {
        return $this->hasMany(Test::class, 'parent');
    }

    /**
     * Relación muchos-a-muchos con tests padres
     * Un test puede tener múltiples padres
     */
    public function parentTests()
    {
        return $this->belongsToMany(Test::class, 'test_parents', 'child_test_id', 'parent_test_id')
            ->withPivot('order')
            ->withTimestamps();
    }

    /**
     * Relación muchos-a-muchos con tests hijos
     * Un test padre puede tener múltiples hijos
     */
    public function childTests()
    {
        return $this->belongsToMany(Test::class, 'test_parents', 'parent_test_id', 'child_test_id')
            ->withPivot('order')
            ->orderBy('test_parents.order')
            ->withTimestamps();
    }

    /**
     * Verifica si este test tiene hijos (nueva relación)
     */
    public function hasChildren(): bool
    {
        return $this->childTests()->count() > 0;
    }

    /**
     * Verifica si este test es hijo de algún padre (nueva relación)
     */
    public function hasParents(): bool
    {
        return $this->parentTests()->count() > 0;
    }

    /**
     * Verifica si es hijo de un padre específico
     */
    public function isChildOf(int $parentId): bool
    {
        return $this->parentTests()->where('parent_test_id', $parentId)->exists();
    }

    /**
     * Obtiene todos los hijos incluyendo ambas relaciones (legacy y nueva)
     *
     * @param  bool  $withRelations  Si true, carga los referenceValues de cada hijo
     */
    public function getAllChildren(bool $withRelations = true): \Illuminate\Support\Collection
    {
        $eagerLoad = $withRelations
            ? ['children', 'childTests', 'referenceValues.category']
            : [];

        $directChildren = $this->childTests()
            ->when($withRelations, fn ($q) => $q->with($eagerLoad))
            ->orderBy('test_parents.order')
            ->get();

        $allDescendants = collect();
        foreach ($directChildren as $child) {
            $allDescendants->push($child);
            $grandchildren = $child->getAllChildren($withRelations);
            $allDescendants = $allDescendants->merge($grandchildren);
        }

        $legacyChildren = $withRelations
            ? $this->children()->with($eagerLoad)->get()
            : $this->children()->get();

        $allDescendants = $allDescendants->merge(
            $legacyChildren->filter(fn ($c) => ! $allDescendants->contains('id', $c->id))
        );

        return $allDescendants->unique('id');
    }

    public function isSubParent(): bool
    {
        return $this->parentTests()->exists() && $this->childTests()->exists();
    }

    /**
     * Relación con valores de referencia
     */
    public function referenceValues()
    {
        return $this->hasMany(TestReferenceValue::class);
    }

    /**
     * Relación con la categoría de referencia predeterminada
     */
    public function defaultReferenceCategory()
    {
        return $this->belongsTo(ReferenceCategory::class, 'default_reference_category_id');
    }

    /**
     * Obtiene el valor de referencia por defecto
     */
    public function getDefaultReferenceValue()
    {
        return $this->referenceValues()->where('is_default', true)->first();
    }

    /**
     * Verifica si tiene múltiples valores de referencia
     */
    public function hasMultipleReferenceValues(): bool
    {
        return $this->referenceValues()->count() > 1;
    }

    /**
     * Obtiene el nombre del material
     */
    public function getMaterialNameAttribute(): string
    {
        $materials = [
            1 => 'EDTA',
            2 => 'Suero',
            3 => 'Orina',
            4 => 'Citrato',
            5 => 'Heparina',
            6 => 'Frasco Estéril',
            7 => 'Botella de Vidrio',
            8 => 'Hemograma',
        ];

        return $materials[$this->material] ?? 'N/A';
    }

    /**
     * Sigla corta del material para etiquetas
     */
    public function getMaterialAbbreviationAttribute(): string
    {
        $abbreviations = [
            1 => 'EDTA',
            2 => 'SUE',
            3 => 'ORI',
            4 => 'CIT',
            5 => 'HEP',
            6 => 'FE',
            7 => 'BV',
            8 => 'HEM',
        ];

        return $abbreviations[$this->material] ?? '?';
    }
}
