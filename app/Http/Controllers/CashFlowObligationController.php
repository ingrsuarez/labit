<?php

namespace App\Http\Controllers;

use App\Models\CashFlowObligation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFlowObligationController extends Controller
{
    public function create(): View
    {
        $this->authorize('cash-flow.manage');

        return view('cash-flow.obligations.create', [
            'categories' => CashFlowObligation::categoryLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('cash-flow.manage');

        $validated = $this->validatePayload($request);

        CashFlowObligation::query()->create([
            ...$validated,
            'company_id' => active_company_id_or_abort(),
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('cash-flow.index', ['date' => $validated['due_date']])
            ->with('success', 'Obligación registrada.');
    }

    public function edit(CashFlowObligation $obligation): View
    {
        $this->authorize('cash-flow.manage');
        $this->assertCompany($obligation);

        return view('cash-flow.obligations.edit', [
            'obligation' => $obligation,
            'categories' => CashFlowObligation::categoryLabels(),
        ]);
    }

    public function update(Request $request, CashFlowObligation $obligation): RedirectResponse
    {
        $this->authorize('cash-flow.manage');
        $this->assertCompany($obligation);

        $obligation->update($this->validatePayload($request));

        return redirect()
            ->route('cash-flow.index', ['date' => $obligation->due_date->toDateString()])
            ->with('success', 'Obligación actualizada.');
    }

    public function destroy(CashFlowObligation $obligation): RedirectResponse
    {
        $this->authorize('cash-flow.manage');
        $this->assertCompany($obligation);

        $obligation->delete();

        return redirect()
            ->route('cash-flow.index')
            ->with('success', 'Obligación eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'in:'.implode(',', CashFlowObligation::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    protected function assertCompany(CashFlowObligation $obligation): void
    {
        if ((int) $obligation->company_id !== active_company_id_or_abort()) {
            abort(404);
        }
    }
}
