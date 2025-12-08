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
     * @deprecated Usar parentTests() para múltiples padres
     */
    public function parentTest()
    {
        return $this->belongsTo(Test::class, 'parent');
    }

    /**
     * Relación con tests hijos (legacy - para compatibilidad)
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
     * @param bool $withRelations Si true, carga los referenceValues de cada hijo
     */
    public function getAllChildren(bool $withRelations = true)
    {
        // Combinar hijos de la relación legacy y la nueva tabla pivote
        if ($withRelations) {
            $legacyChildren = $this->children()->with('referenceValues.category')->get();
            $pivotChildren = $this->childTests()->with('referenceValues.category')->get();
        } else {
            $legacyChildren = $this->children()->get();
            $pivotChildren = $this->childTests()->get();
        }
        
        return $legacyChildren->merge($pivotChildren)->unique('id');
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
        ];

        return $materials[$this->material] ?? 'N/A';
    }
}
