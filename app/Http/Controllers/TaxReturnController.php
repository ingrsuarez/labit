<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use App\Models\TaxReturn;
use App\Models\TaxReturnApplication;
use App\Services\TaxReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxReturnController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('tax-returns.manage');

        $q = TaxReturn::query()
            ->where('company_id', active_company_id())
            ->with(['tax', 'creator'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month');

        if ($request->filled('tax_id')) {
            $q->where('tax_id', (int) $request->tax_id);
        }
        if ($request->filled('year')) {
            $q->where('period_year', (int) $request->year);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $returns = $q->paginate(20)->withQueryString();
        $taxes = Tax::where('company_id', active_company_id())->active()->orderBy('name')->get();

        return view('tax-returns.index', compact('returns', 'taxes'));
    }

    public function create(): View
    {
        $this->authorize('tax-returns.manage');

        $taxes = Tax::where('company_id', active_company_id())->active()->orderBy('name')->get();

        return view('tax-returns.create', compact('taxes'));
    }

    public function availableAdvances(Request $request, TaxReturnService $service): JsonResponse
    {
        $this->authorize('tax-returns.manage');

        $validated = $request->validate([
            'tax_id' => ['required', 'exists:taxes,id'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $tax = Tax::where('company_id', active_company_id())->findOrFail($validated['tax_id']);

        $items = $service->availableAdvances(
            $tax,
            (int) $validated['period_year'],
            isset($validated['period_month']) ? (int) $validated['period_month'] : null
        );

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('tax-returns.manage');

        $validated = $this->validateTaxReturnPayload($request);

        $tax = Tax::where('company_id', active_company_id())->findOrFail($validated['tax_id']);

        $this->assertUniquePeriod($tax, (int) $validated['period_year'], $validated['period_month'] ?? null);

        $taxReturn = TaxReturn::create([
            'company_id' => active_company_id(),
            'tax_id' => $tax->id,
            'period_year' => $validated['period_year'],
            'period_month' => $validated['period_month'] ?? null,
            'declared_amount' => $validated['declared_amount'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $this->syncApplications($taxReturn, $validated['applications'] ?? []);
        $taxReturn->recalculateTotals();
        $taxReturn->save();

        return redirect()->route('tax-returns.show', $taxReturn)->with('success', 'Declaración guardada en borrador.');
    }

    public function show(TaxReturn $taxReturn): View
    {
        $this->authorize('tax-returns.manage');
        $this->ensureCompany($taxReturn);

        $taxReturn->load([
            'tax.liabilityAccount',
            'applications.purchaseInvoicePerception.purchaseInvoice',
            'applications.purchaseCreditNotePerception.purchaseCreditNote',
            'journalEntry.lines.account',
            'cancellationJournalEntry.lines.account',
            'creator',
            'confirmedBy',
        ]);

        return view('tax-returns.show', compact('taxReturn'));
    }

    public function edit(TaxReturn $taxReturn): View
    {
        $this->authorize('tax-returns.manage');
        $this->ensureCompany($taxReturn);
        abort_unless($taxReturn->isDraft(), 403);

        $taxReturn->load([
            'applications.purchaseInvoicePerception.purchaseInvoice',
            'applications.purchaseCreditNotePerception.purchaseCreditNote',
        ]);

        $prefillLoaded = $taxReturn->applications->map(function ($a) {
            if ($a->purchase_invoice_perception_id) {
                $pip = $a->purchaseInvoicePerception;
                $inv = $pip?->purchaseInvoice;

                return [
                    'kind' => 'fc',
                    'purchase_invoice_perception_id' => $pip->id,
                    'purchase_credit_note_perception_id' => null,
                    'amount' => (float) $pip->amount,
                    'apply' => (float) $a->amount_applied,
                    'label' => ($inv ? $inv->full_number.' — ' : '').($pip->name_snapshot ?? ''),
                    'include' => true,
                ];
            }
            $pcnp = $a->purchaseCreditNotePerception;
            $cn = $pcnp?->purchaseCreditNote;

            return [
                'kind' => 'nc',
                'purchase_invoice_perception_id' => null,
                'purchase_credit_note_perception_id' => $pcnp->id,
                'amount' => (float) $pcnp->amount,
                'apply' => (float) $a->amount_applied,
                'label' => ($cn ? $cn->full_number.' — ' : '').($pcnp->name_snapshot ?? ''),
                'include' => true,
            ];
        })->values()->all();

        $taxes = Tax::where('company_id', active_company_id())->active()->orderBy('name')->get();

        return view('tax-returns.edit', compact('taxReturn', 'taxes', 'prefillLoaded'));
    }

    public function update(Request $request, TaxReturn $taxReturn): RedirectResponse
    {
        $this->authorize('tax-returns.manage');
        $this->ensureCompany($taxReturn);
        abort_unless($taxReturn->isDraft(), 403);

        $validated = $this->validateTaxReturnPayload($request, $taxReturn->id);

        $tax = Tax::where('company_id', active_company_id())->findOrFail($validated['tax_id']);

        $this->assertUniquePeriod($tax, (int) $validated['period_year'], $validated['period_month'] ?? null, $taxReturn->id);

        $taxReturn->update([
            'tax_id' => $tax->id,
            'period_year' => $validated['period_year'],
            'period_month' => $validated['period_month'] ?? null,
            'declared_amount' => $validated['declared_amount'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $taxReturn->applications()->delete();
        $this->syncApplications($taxReturn, $validated['applications'] ?? []);
        $taxReturn->recalculateTotals();
        $taxReturn->save();

        return redirect()->route('tax-returns.show', $taxReturn)->with('success', 'Declaración actualizada.');
    }

    public function destroy(TaxReturn $taxReturn): RedirectResponse
    {
        $this->authorize('tax-returns.manage');
        $this->ensureCompany($taxReturn);
        abort_unless($taxReturn->isDraft(), 403);

        $taxReturn->applications()->delete();
        $taxReturn->delete();

        return redirect()->route('tax-returns.index')->with('success', 'Declaración eliminada.');
    }

    public function confirm(Request $request, TaxReturn $taxReturn, TaxReturnService $service): RedirectResponse
    {
        $this->authorize('tax-returns.manage');
        $this->ensureCompany($taxReturn);

        try {
            $service->confirm($taxReturn);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tax-returns.show', $taxReturn)->with('success', 'Declaración confirmada y asiento generado.');
    }

    public function cancel(Request $request, TaxReturn $taxReturn, TaxReturnService $service): RedirectResponse
    {
        $this->authorize('tax-returns.manage');
        $this->ensureCompany($taxReturn);

        try {
            $service->cancel($taxReturn);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tax-returns.show', $taxReturn)->with('success', 'Declaración anulada; asiento reverso generado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTaxReturnPayload(Request $request, ?int $taxReturnId = null): array
    {
        $rules = [
            'tax_id' => ['required', 'exists:taxes,id'],
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'declared_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'applications' => ['nullable', 'array'],
            'applications.*.purchase_invoice_perception_id' => ['nullable', 'integer', 'exists:purchase_invoice_perceptions,id'],
            'applications.*.purchase_credit_note_perception_id' => ['nullable', 'integer', 'exists:purchase_credit_note_perceptions,id'],
            'applications.*.amount_applied' => ['nullable', 'numeric', 'min:0'],
        ];

        $validated = $request->validate($rules);

        $tax = Tax::where('company_id', active_company_id())->findOrFail($validated['tax_id']);

        if ($tax->frequency === 'annual') {
            $validated['period_month'] = null;
        } elseif ($tax->frequency === 'quarterly' && isset($validated['period_month'])) {
            if (! in_array((int) $validated['period_month'], [1, 4, 7, 10], true)) {
                abort(422, 'Para periodicidad trimestral el mes debe ser 1, 4, 7 u 10.');
            }
        } elseif (in_array($tax->frequency, ['monthly', 'quarterly'], true) && empty($validated['period_month'])) {
            abort(422, 'Indique el período (mes).');
        }

        foreach ($validated['applications'] ?? [] as $row) {
            $hasPip = ! empty($row['purchase_invoice_perception_id']);
            $hasPcnp = ! empty($row['purchase_credit_note_perception_id']);
            if ($hasPip && $hasPcnp) {
                abort(422, 'Cada línea debe referir solo a FC o solo a NC.');
            }
            if (! $hasPip && ! $hasPcnp) {
                continue;
            }
            $amt = (float) ($row['amount_applied'] ?? 0);
            if ($amt <= 0) {
                abort(422, 'Monto de aplicación inválido.');
            }
        }

        return $validated;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncApplications(TaxReturn $taxReturn, array $rows): void
    {
        foreach ($rows as $row) {
            $pip = ! empty($row['purchase_invoice_perception_id']) ? (int) $row['purchase_invoice_perception_id'] : null;
            $pcnp = ! empty($row['purchase_credit_note_perception_id']) ? (int) $row['purchase_credit_note_perception_id'] : null;
            if (! $pip && ! $pcnp) {
                continue;
            }
            TaxReturnApplication::create([
                'tax_return_id' => $taxReturn->id,
                'purchase_invoice_perception_id' => $pip,
                'purchase_credit_note_perception_id' => $pcnp,
                'amount_applied' => round((float) ($row['amount_applied'] ?? 0), 2),
            ]);
        }
    }

    private function assertUniquePeriod(Tax $tax, int $year, ?int $month, ?int $ignoreId = null): void
    {
        $q = TaxReturn::query()
            ->where('company_id', active_company_id())
            ->where('tax_id', $tax->id)
            ->where('period_year', $year)
            ->where('status', '<>', 'cancelled');

        if ($month === null) {
            $q->whereNull('period_month');
        } else {
            $q->where('period_month', $month);
        }

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        if ($q->exists()) {
            abort(422, 'Ya existe una declaración para ese impuesto y período (no anulada).');
        }
    }

    private function ensureCompany(TaxReturn $taxReturn): void
    {
        abort_if((int) $taxReturn->company_id !== (int) active_company_id(), 403);
    }
}
