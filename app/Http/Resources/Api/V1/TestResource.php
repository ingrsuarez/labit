<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Estructura liviana de un test del catálogo para LISCOM.
 *
 * A diferencia de DeterminationResource (que trabaja sobre una determinación
 * de un protocolo), este resource trabaja directamente sobre el modelo Test
 * y expone su catálogo completo independiente de protocolos.
 */
class TestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasChildren = $this->relationLoaded('childTests')
            ? $this->childTests->isNotEmpty()
            : $this->childTests()->exists();

        $hasParents = $this->relationLoaded('parentTests')
            ? $this->parentTests->isNotEmpty()
            : $this->parentTests()->exists();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'unit' => $this->unit,
            'method' => $this->method,
            'nbu' => $this->nbu ? (float) $this->nbu : null,
            'categories' => $this->categories ?? [],
            'is_parent' => $hasChildren,
            'is_child' => $hasParents,
            'material' => $this->materialRelation ? [
                'id' => $this->materialRelation->id,
                'name' => $this->materialRelation->name,
                'abbreviation' => $this->materialRelation->code,
            ] : null,
        ];
    }
}
