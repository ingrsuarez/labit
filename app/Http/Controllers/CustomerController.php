<?php

namespace App\Http\Controllers;

use App\Models\CollectionReceipt;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\Sample;
use App\Models\VetAdmission;
use App\Services\AfipService;
use App\Services\NbuRetroactivePricingService;
use App\Support\SyncsEntityEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Muestra el listado de clientes
     */
    public function index(Request $request)
    {
        $query = Customer::with('emails')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('taxId', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('emails', fn ($eq) => $eq->where('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->whereJsonContains('type', $request->type);
        }

        $customers = $query->paginate(15);

        return view('customer.index', compact('customers'));
    }

    /**
     * Muestra el formulario para crear un nuevo cliente
     */
    public function create()
    {
        return view('customer.create');
    }

    /**
     * Almacena un nuevo cliente
     */
    public function store(Request $request, SyncsEntityEmails $syncsEntityEmails)
    {
        $validated = $request->validate(array_merge($this->customerRules(), $this->entityEmailRules()));

        $emailRows = $this->resolveEmailRows($request);

        $validated['status'] = 'activo';
        $validated['discount_percent'] = $validated['discount_percent'] ?? 0;
        $validated['type'] = $validated['type'] ?? ['aguas'];
        $validated['email'] = null;
        if (! empty($validated['afip_verified_at'])) {
            $validated['afip_verified_at'] = \Carbon\Carbon::parse($validated['afip_verified_at']);
        }

        DB::transaction(function () use ($validated, $emailRows, $syncsEntityEmails) {
            $customer = Customer::create($validated);
            $syncsEntityEmails->sync($customer, $emailRows);
        });

        return redirect()->route('customer.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    /**
     * Muestra el formulario para editar un cliente
     */
    public function edit(Customer $customer)
    {
        $customer->load('emails');

        return view('customer.edit', compact('customer'));
    }

    /**
     * Actualiza un cliente
     */
    public function update(Request $request, Customer $customer, SyncsEntityEmails $syncsEntityEmails)
    {
        $validated = $request->validate(array_merge($this->customerRules(), $this->entityEmailRules(), [
            'status' => 'required|in:activo,inactivo',
            'retroactive_from' => 'nullable|date|before_or_equal:today',
        ]));

        $emailRows = $this->resolveEmailRows($request);

        $validated['type'] = $validated['type'] ?? $customer->type;

        if (! empty($validated['afip_verified_at'])) {
            $validated['afip_verified_at'] = \Carbon\Carbon::parse($validated['afip_verified_at']);
        }

        $oldVetRate = (float) ($customer->veterinary_nbu_value ?? 0);
        $newVetRate = (float) ($validated['veterinary_nbu_value'] ?? 0);

        DB::transaction(function () use ($customer, $validated, $emailRows, $syncsEntityEmails) {
            $customer->update($validated);
            $syncsEntityEmails->sync($customer, $emailRows);
        });

        $retroResult = null;
        if ($customer->isVeterinary()) {
            $retroResult = app(NbuRetroactivePricingService::class)
                ->applyVetIfRequested($request, $customer->fresh(), $newVetRate, $oldVetRate);
        }

        return redirect()->route('customer.index')
            ->with('success', NbuRetroactivePricingService::flashMessage($retroResult, 'protocolos veterinarios'));
    }

    public function previewRetroactiveVetNbu(Request $request, Customer $customer)
    {
        if (! $customer->isVeterinary()) {
            return response()->json(['error' => 'Cliente no veterinario'], 422);
        }

        $validated = $request->validate([
            'new_nbu_value' => 'required|numeric|min:0',
            'from_date' => 'required|date|before_or_equal:today',
        ]);

        return response()->json(
            app(NbuRetroactivePricingService::class)->previewVet(
                $customer,
                (float) $validated['new_nbu_value'],
                $validated['from_date']
            )
        );
    }

    /**
     * Elimina un cliente solo si no tiene protocolos ni facturación vinculados.
     *
     * Protocolos: muestras (aguas/alimentos) y protocolos veterinarios.
     * Facturación: facturas de venta, recibos de cobro y notas de crédito.
     */
    public function destroy(Customer $customer)
    {
        $hasProtocols = Sample::where('customer_id', $customer->id)->exists()
            || VetAdmission::where('customer_id', $customer->id)->exists();

        $hasBilling = SalesInvoice::where('customer_id', $customer->id)->exists()
            || CollectionReceipt::where('customer_id', $customer->id)->exists()
            || CreditNote::where('customer_id', $customer->id)->exists();

        $reasons = [];
        if ($hasProtocols) {
            $reasons[] = 'protocolos cargados (muestras de aguas/alimentos o protocolos veterinarios)';
        }
        if ($hasBilling) {
            $reasons[] = 'facturación registrada (facturas de venta, recibos de cobro o notas de crédito)';
        }

        if ($reasons !== []) {
            return redirect()->route('customer.edit', $customer)
                ->with('error', 'No se puede eliminar este cliente porque tiene '.implode(' y ', $reasons).'.');
        }

        DB::transaction(fn () => $customer->delete());

        return redirect()->route('customer.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private function customerRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'taxId' => 'required|string|max:20',
            'tax' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal' => 'nullable|string|max:20',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'afip_activity' => 'nullable|string|max:500',
            'cuit_status' => 'nullable|string|max:50',
            'afip_verified_at' => 'nullable|date',
            'type' => 'nullable|array',
            'type.*' => 'in:obra_social,aguas,veterinario,clinico,particular,laborales',
            'veterinary_nbu_value' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function entityEmailRules(): array
    {
        return [
            'emails' => 'nullable|array',
            'emails.*.email' => 'nullable|email|max:255',
            'emails.*.label_preset' => 'nullable|string|max:50',
            'emails.*.label_custom' => 'nullable|string|max:50',
            'emails.*.is_primary' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveEmailRows(Request $request): array
    {
        $rows = $request->input('emails', []);

        if ($rows === [] && $request->filled('email')) {
            return [['email' => $request->input('email'), 'is_primary' => true]];
        }

        return is_array($rows) ? $rows : [];
    }

    public function consultarCuit(string $cuit)
    {
        $cuit = preg_replace('/\D/', '', $cuit);

        if (strlen($cuit) !== 11) {
            return response()->json(['success' => false, 'error' => 'El CUIT debe tener 11 dígitos.'], 422);
        }

        try {
            $afipService = new AfipService;
            $result = $afipService->consultarPadron($cuit);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo conectar con AFIP. Podés cargar los datos manualmente.',
            ], 503);
        }
    }
}
