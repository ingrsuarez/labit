<?php

namespace App\Http\Controllers;

use App\Models\PurchaseServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseServiceCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('purchase-service-categories.index');

        $categories = PurchaseServiceCategory::query()
            ->forCompany(active_company_id())
            ->withCount('services')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('purchase-service-categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('purchase-service-categories.create');

        return view('purchase-service-categories.create');
    }

    public function store(Request $request)
    {
        $this->authorize('purchase-service-categories.create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0|max:32767',
        ], [
            'name.required' => 'El nombre es obligatorio.',
        ]);

        $companyId = active_company_id();
        $validated['company_id'] = $companyId;
        $validated['slug'] = $this->uniqueCategorySlug($companyId, $validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = true;

        PurchaseServiceCategory::create($validated);

        return redirect()->route('purchase-service-categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(PurchaseServiceCategory $purchaseServiceCategory)
    {
        $this->authorize('purchase-service-categories.edit');
        $this->ensureCompany($purchaseServiceCategory);

        return view('purchase-service-categories.edit', compact('purchaseServiceCategory'));
    }

    public function update(Request $request, PurchaseServiceCategory $purchaseServiceCategory)
    {
        $this->authorize('purchase-service-categories.edit');
        $this->ensureCompany($purchaseServiceCategory);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0|max:32767',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre es obligatorio.',
        ]);

        $validated['slug'] = $this->uniqueCategorySlug(
            (int) $purchaseServiceCategory->company_id,
            $validated['name'],
            $purchaseServiceCategory->id
        );
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $purchaseServiceCategory->update($validated);

        return redirect()->route('purchase-service-categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(PurchaseServiceCategory $purchaseServiceCategory)
    {
        $this->authorize('purchase-service-categories.delete');
        $this->ensureCompany($purchaseServiceCategory);

        if ($purchaseServiceCategory->services()->exists()) {
            return redirect()->route('purchase-service-categories.index')
                ->with('error', 'No se puede eliminar: hay servicios en esta categoría. Reasignalos o eliminá los servicios primero.');
        }

        $purchaseServiceCategory->delete();

        return redirect()->route('purchase-service-categories.index')
            ->with('success', 'Categoría eliminada.');
    }

    protected function ensureCompany(PurchaseServiceCategory $purchaseServiceCategory): void
    {
        if ((int) $purchaseServiceCategory->company_id !== (int) active_company_id()) {
            abort(403);
        }
    }

    protected function uniqueCategorySlug(int $companyId, string $name, ?int $exceptId = null): string
    {
        $base = Str::slug($name) ?: 'categoria';
        $candidate = $base;
        $i = 0;

        while ($i < 1000) {
            $q = PurchaseServiceCategory::where('company_id', $companyId)->where('slug', $candidate);
            if ($exceptId !== null) {
                $q->where('id', '!=', $exceptId);
            }
            if (! $q->exists()) {
                return $candidate;
            }
            $i++;
            $candidate = $base.'-'.$i;
        }

        return $base.'-'.uniqid();
    }
}
