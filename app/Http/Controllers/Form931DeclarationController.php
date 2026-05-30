<?php

namespace App\Http\Controllers;

use App\Models\Form931Declaration;
use App\Services\Form931DeclarationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class Form931DeclarationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('form931.manage');

        $query = Form931Declaration::query()
            ->where('company_id', active_company_id())
            ->with(['creator', 'confirmedBy'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month');

        if ($request->filled('year')) {
            $query->where('period_year', (int) $request->year);
        }
        if ($request->filled('month')) {
            $query->where('period_month', (int) $request->month);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $declarations = $query->paginate(20)->withQueryString();

        return view('form931-declarations.index', compact('declarations'));
    }

    public function create(): View
    {
        $this->authorize('form931.manage');

        return view('form931-declarations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('form931.manage');

        $validated = $this->validatePayload($request);

        $this->assertUniqueDraftPeriod(
            (int) $validated['period_year'],
            (int) $validated['period_month']
        );

        $declaration = new Form931Declaration($validated);
        $declaration->company_id = active_company_id();
        $declaration->created_by = auth()->id();
        $declaration->status = 'draft';
        $declaration->recalculateTotal();
        $declaration->save();

        if ($request->boolean('confirm_now')) {
            return $this->confirmDeclaration($declaration);
        }

        return redirect()
            ->route('form931-declarations.show', $declaration)
            ->with('success', 'DDJJ Form 931 guardada como borrador.');
    }

    public function show(Form931Declaration $form931Declaration): View
    {
        $this->authorize('form931.manage');
        $this->assertCompany($form931Declaration);

        $form931Declaration->load(['journalEntry', 'cancellationJournalEntry', 'creator', 'confirmedBy']);

        return view('form931-declarations.show', ['declaration' => $form931Declaration]);
    }

    public function edit(Form931Declaration $form931Declaration): View|RedirectResponse
    {
        $this->authorize('form931.manage');
        $this->assertCompany($form931Declaration);

        if (! $form931Declaration->isDraft()) {
            return redirect()
                ->route('form931-declarations.show', $form931Declaration)
                ->with('error', 'Solo se pueden editar borradores.');
        }

        return view('form931-declarations.edit', ['declaration' => $form931Declaration]);
    }

    public function update(Request $request, Form931Declaration $form931Declaration): RedirectResponse
    {
        $this->authorize('form931.manage');
        $this->assertCompany($form931Declaration);

        if (! $form931Declaration->isDraft()) {
            return back()->with('error', 'Solo se pueden editar borradores.');
        }

        $validated = $this->validatePayload($request);

        $this->assertUniqueDraftPeriod(
            (int) $validated['period_year'],
            (int) $validated['period_month'],
            $form931Declaration->id
        );

        $form931Declaration->fill($validated);
        $form931Declaration->recalculateTotal();
        $form931Declaration->save();

        if ($request->boolean('confirm_now')) {
            return $this->confirmDeclaration($form931Declaration);
        }

        return redirect()
            ->route('form931-declarations.show', $form931Declaration)
            ->with('success', 'DDJJ Form 931 actualizada.');
    }

    public function destroy(Form931Declaration $form931Declaration): RedirectResponse
    {
        $this->authorize('form931.manage');
        $this->assertCompany($form931Declaration);

        if (! $form931Declaration->isDraft()) {
            return back()->with('error', 'Solo se pueden eliminar borradores.');
        }

        $form931Declaration->delete();

        return redirect()
            ->route('form931-declarations.index')
            ->with('success', 'Borrador eliminado.');
    }

    public function confirm(Form931Declaration $form931Declaration, Form931DeclarationService $service): RedirectResponse
    {
        $this->authorize('form931.manage');
        $this->assertCompany($form931Declaration);

        return $this->confirmDeclaration($form931Declaration, $service);
    }

    public function cancel(Form931Declaration $form931Declaration, Form931DeclarationService $service): RedirectResponse
    {
        $this->authorize('form931.manage');
        $this->assertCompany($form931Declaration);

        try {
            $service->cancel($form931Declaration);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('form931-declarations.show', $form931Declaration)
            ->with('success', 'DDJJ Form 931 anulada. Se generó el asiento reverso.');
    }

    protected function confirmDeclaration(
        Form931Declaration $declaration,
        ?Form931DeclarationService $service = null
    ): RedirectResponse {
        $service ??= app(Form931DeclarationService::class);

        try {
            $service->confirm($declaration->fresh());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('form931-declarations.show', $declaration)
            ->with('success', 'DDJJ Form 931 confirmada. Asiento contable generado.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount_aportes_patronales' => ['required', 'numeric', 'min:0'],
            'amount_contribuciones_patronales' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    protected function assertCompany(Form931Declaration $declaration): void
    {
        if ((int) $declaration->company_id !== (int) active_company_id()) {
            abort(404);
        }
    }

    protected function assertUniqueDraftPeriod(int $year, int $month, ?int $exceptId = null): void
    {
        $query = Form931Declaration::query()
            ->where('company_id', active_company_id())
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('status', 'draft');

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'period_month' => 'Ya existe un borrador para este período.',
            ]);
        }
    }
}
