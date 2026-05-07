<?php

namespace App\Http\Controllers;

use App\Enums\DeterminationProfileLabType;
use App\Http\Requests\StoreDeterminationProfileRequest;
use App\Http\Requests\UpdateDeterminationProfileRequest;
use App\Models\Admission;
use App\Models\DeterminationProfile;
use App\Models\Sample;
use App\Models\Test;
use App\Models\VetAdmission;
use App\Services\DeterminationProfileApplicationService;
use App\Support\DeterminationProfileTestMatcher;
use Illuminate\Http\Request;

class DeterminationProfileController extends Controller
{
    public function __construct(
        protected DeterminationProfileApplicationService $applicationService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('determination-profiles.index');

        $tab = $request->get('tipo') ?: 'todos';
        $search = $request->get('q');

        $query = DeterminationProfile::query()
            ->withCount('tests')
            ->orderBy('name');

        if ($tab !== 'todos') {
            $query->where('lab_type', $tab);
        }

        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $profiles = $query->paginate(20)->withQueryString();

        return view('lab.determination-profiles.index', compact('profiles', 'tab', 'search'));
    }

    public function create()
    {
        $this->authorize('determination-profiles.manage');

        return view('lab.determination-profiles.create');
    }

    public function store(StoreDeterminationProfileRequest $request)
    {
        $profile = DeterminationProfile::create([
            'name' => $request->validated('name'),
            'lab_type' => DeterminationProfileLabType::from($request->validated('lab_type')),
            'is_active' => true,
        ]);

        $sync = [];
        foreach ($request->validated('test_ids') as $index => $testId) {
            $sync[$testId] = ['sort_order' => $index];
        }
        $profile->tests()->sync($sync);

        return redirect()->route('determination-profiles.index')
            ->with('success', 'Perfil creado correctamente.');
    }

    public function edit(DeterminationProfile $determination_profile)
    {
        $this->authorize('determination-profiles.manage');

        $determination_profile->load(['tests' => fn ($q) => $q->orderBy('determination_profile_test.sort_order')]);

        return view('lab.determination-profiles.edit', ['profile' => $determination_profile]);
    }

    public function update(UpdateDeterminationProfileRequest $request, DeterminationProfile $determination_profile)
    {
        $determination_profile->update([
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $sync = [];
        foreach ($request->validated('test_ids') as $index => $testId) {
            $sync[$testId] = ['sort_order' => $index];
        }
        $determination_profile->tests()->sync($sync);

        return redirect()->route('determination-profiles.index')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    public function destroy(Request $request, DeterminationProfile $determination_profile)
    {
        $this->authorize('determination-profiles.manage');

        if ($request->boolean('hard_delete')) {
            $determination_profile->tests()->detach();
            $determination_profile->delete();
            $message = 'Perfil eliminado.';
        } else {
            $determination_profile->update(['is_active' => false]);
            $message = 'Perfil desactivado. Los protocolos ya cargados no se modifican.';
        }

        return redirect()->route('determination-profiles.index')->with('success', $message);
    }

    /**
     * Búsqueda de determinaciones para armar el perfil (Tom Select).
     */
    public function searchTests(Request $request)
    {
        $this->authorize('determination-profiles.manage');

        $request->validate([
            'lab_type' => ['required', \Illuminate\Validation\Rule::enum(DeterminationProfileLabType::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $labType = DeterminationProfileLabType::from($request->lab_type);
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $testsQuery = Test::query()
            ->where(function ($w) use ($q) {
                $w->where('code', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%');
            })
            ->limit(40)
            ->orderBy('code');

        $tests = $testsQuery->get()->filter(fn (Test $t) => DeterminationProfileTestMatcher::matches($t, $labType));

        return response()->json($tests->values()->map(fn (Test $t) => [
            'id' => $t->id,
            'code' => $t->code,
            'name' => $t->name,
        ]));
    }

    /**
     * Listado JSON de perfiles activos para selects en admisiones.
     */
    public function forSelect(Request $request)
    {
        $request->validate([
            'lab_type' => ['required', \Illuminate\Validation\Rule::enum(DeterminationProfileLabType::class)],
        ]);

        $this->authorize('determination-profiles.index');

        $labType = DeterminationProfileLabType::from($request->lab_type);

        $profiles = DeterminationProfile::query()
            ->active()
            ->forLabType($labType)
            ->orderBy('name')
            ->get(['id', 'name', 'lab_type']);

        return response()->json($profiles);
    }

    public function previewAdmission(Request $request)
    {
        $this->authorizeAnyAdmission();

        $validated = $request->validate([
            'insurance_id' => ['required', 'exists:insurances,id'],
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['integer', 'exists:determination_profiles,id'],
            'existing_test_ids' => ['nullable', 'array'],
            'existing_test_ids.*' => ['integer'],
        ]);

        $result = $this->applicationService->previewAdmission(
            (int) $validated['insurance_id'],
            $validated['profile_ids'],
            $validated['existing_test_ids'] ?? [],
            DeterminationProfileLabType::Clinico
        );

        return response()->json($result);
    }

    public function applyAdmission(Request $request, Admission $admission)
    {
        $this->authorize('lab-admissions.edit');

        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['integer', 'exists:determination_profiles,id'],
        ]);

        $result = $this->applicationService->applyToAdmission($admission, $validated['profile_ids']);

        return $this->applyFlashRedirect($request, $result, 'práctica(s)');
    }

    public function previewSample(Request $request)
    {
        $this->authorizeSample();

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['integer', 'exists:determination_profiles,id'],
            'existing_test_ids' => ['nullable', 'array'],
            'existing_test_ids.*' => ['integer'],
        ]);

        $result = $this->applicationService->previewSample(
            (int) $validated['customer_id'],
            $validated['profile_ids'],
            $validated['existing_test_ids'] ?? [],
            DeterminationProfileLabType::AguasAlimentos
        );

        return response()->json($result);
    }

    public function applySample(Request $request, Sample $sample)
    {
        $this->authorize('samples.edit');

        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['integer', 'exists:determination_profiles,id'],
        ]);

        $result = $this->applicationService->applyToSample($sample, $validated['profile_ids']);

        return $this->applyFlashRedirect($request, $result, 'determinación(es)');
    }

    public function previewVet(Request $request)
    {
        // Misma política que vet/admissions/search-tests y store: acceso vía middleware check.access.

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['integer', 'exists:determination_profiles,id'],
            'existing_test_ids' => ['nullable', 'array'],
            'existing_test_ids.*' => ['integer'],
        ]);

        $result = $this->applicationService->previewVet(
            (int) $validated['customer_id'],
            $validated['profile_ids'],
            $validated['existing_test_ids'] ?? [],
            DeterminationProfileLabType::Veterinario
        );

        return response()->json($result);
    }

    public function applyVet(Request $request, VetAdmission $vetAdmission)
    {
        $this->authorize('vet-admissions.edit');

        $validated = $request->validate([
            'profile_ids' => ['required', 'array', 'min:1'],
            'profile_ids.*' => ['integer', 'exists:determination_profiles,id'],
        ]);

        $result = $this->applicationService->applyToVetAdmission($vetAdmission, $validated['profile_ids']);

        return $this->applyFlashRedirect($request, $result, 'determinación(es)');
    }

    private function authorizeAnyAdmission(): void
    {
        if (! auth()->user()->can('lab-admissions.create') && ! auth()->user()->can('lab-admissions.edit')) {
            abort(403);
        }
    }

    private function authorizeSample(): void
    {
        if (! auth()->user()->can('samples.create') && ! auth()->user()->can('samples.edit')) {
            abort(403);
        }
    }

    private function applyFlashRedirect(Request $request, array $result, string $unitLabel): \Illuminate\Http\RedirectResponse
    {
        if (! empty($result['error'])) {
            return back()->with('error', $result['error']);
        }

        /** @var \Illuminate\Support\Collection $profiles */
        $profiles = $result['profiles'];
        $profileNames = $profiles->pluck('name')->filter()->implode(', ');
        $added = (int) ($result['added_count'] ?? 0);
        $skipped = (int) ($result['skipped_duplicate_count'] ?? 0);
        $skippedNom = $result['skipped_nomenclator'] ?? [];

        $parts = [];
        if ($added > 0) {
            $parts[] = 'Se agregaron '.$added.' '.$unitLabel.'.';
        }
        if ($skipped > 0) {
            $parts[] = $skipped.' ya estaban en el pedido y se omitieron.';
        }
        if ($skippedNom !== []) {
            $parts[] = 'No agregadas por nomenclador/cobertura: '.count($skippedNom).' ítem(s).';
        }
        if ($added === 0 && $skipped === 0 && $skippedNom === []) {
            $parts[] = 'No hubo cambios.';
        }

        $msg = trim(($profileNames ? 'Perfiles: '.$profileNames.'. ' : '').implode(' ', $parts));

        $flashKey = ($added > 0 || $skipped > 0) ? 'success' : 'warning';

        return back()->with($flashKey, $msg);
    }
}
