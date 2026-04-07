<?php

namespace App\Http\Controllers;

use App\Models\PurchaseService;
use App\Models\PurchaseServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseServiceController extends Controller
{
    public function index()
    {
        $this->authorize('purchase-services.index');

        $services = PurchaseService::query()
            ->forCompany(active_company_id())
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('purchase-services.index', compact('services'));
    }

    public function create()
    {
        $this->authorize('purchase-services.create');

        $categories = PurchaseServiceCategory::query()
            ->forCompany(active_company_id())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('purchase-services.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorize('purchase-services.create');

        $companyId = active_company_id();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('purchase_services', 'code')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'purchase_service_category_id' => [
                'nullable', 'integer',
                Rule::exists('purchase_service_categories', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'sort_order' => 'nullable|integer|min:0|max:32767',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'code.unique' => 'Ya existe un servicio con ese código en esta empresa.',
        ]);

        $validated['company_id'] = $companyId;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = true;

        PurchaseService::create($validated);

        return redirect()->route('purchase-services.index')
            ->with('success', 'Servicio de compra creado correctamente.');
    }

    public function edit(PurchaseService $purchaseService)
    {
        $this->authorize('purchase-services.edit');
        $this->ensureCompany($purchaseService);

        $categories = PurchaseServiceCategory::query()
            ->forCompany(active_company_id())
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('purchase-services.edit', compact('purchaseService', 'categories'));
    }

    public function update(Request $request, PurchaseService $purchaseService)
    {
        $this->authorize('purchase-services.edit');
        $this->ensureCompany($purchaseService);

        $companyId = (int) $purchaseService->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('purchase_services', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($purchaseService->id),
            ],
            'purchase_service_category_id' => [
                'nullable', 'integer',
                Rule::exists('purchase_service_categories', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'sort_order' => 'nullable|integer|min:0|max:32767',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'code.unique' => 'Ya existe un servicio con ese código en esta empresa.',
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $purchaseService->update($validated);

        return redirect()->route('purchase-services.index')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(PurchaseService $purchaseService)
    {
        $this->authorize('purchase-services.delete');
        $this->ensureCompany($purchaseService);

        if ($purchaseService->purchaseInvoiceItems()->exists()) {
            return redirect()->route('purchase-services.index')
                ->with('error', 'No se puede eliminar: hay líneas de factura de compra que referencian este servicio.');
        }

        $purchaseService->delete();

        return redirect()->route('purchase-services.index')
            ->with('success', 'Servicio eliminado.');
    }

    protected function ensureCompany(PurchaseService $purchaseService): void
    {
        if ((int) $purchaseService->company_id !== (int) active_company_id()) {
            abort(403);
        }
    }
}
