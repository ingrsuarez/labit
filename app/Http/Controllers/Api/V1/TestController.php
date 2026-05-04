<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TestResource;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Búsqueda en el catálogo de tests/determinaciones para LISCOM.
 *
 * Permite que LISCOM busque cualquier test del laboratorio por nombre o
 * código, independientemente de si aparece en un protocolo reciente.
 * Detrás de `auth.api_key` (v1.46.0).
 */
class TestController extends Controller
{
    /**
     * GET /api/v1/tests?search=...&category=...
     *
     * Requiere `search` con al menos 2 caracteres. Busca por `name` y `code`
     * con LIKE. Opcionalmente filtra por `category` (clinico, aguas_alimentos,
     * veterinario). Devuelve resultados paginados con meta estándar.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim($request->input('search', ''));
        $category = trim($request->input('category', ''));

        $perPage = (int) $request->input('per_page', 30);
        $perPage = max(1, min($perPage, 100));
        $page = max(1, (int) $request->input('page', 1));

        $query = Test::with(['materialRelation', 'childTests', 'parentTests'])
            ->orderBy('code');

        if (mb_strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        } else {
            $query->whereRaw('0 = 1');
        }

        if ($category !== '') {
            $query->whereJsonContains('categories', $category);
        }

        $total = $query->count();
        $items = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return TestResource::collection($items)->additional([
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }
}
