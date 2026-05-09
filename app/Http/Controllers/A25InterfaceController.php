<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\LabBranch;
use App\Models\VetAdmission;
use App\Services\A25\A25ResultParser;
use App\Services\A25\A25WorklistBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class A25InterfaceController extends Controller
{
    public function __construct(
        private readonly A25WorklistBuilder $worklistBuilder,
        private readonly A25ResultParser $resultParser,
    ) {}

    /**
     * Pantalla principal: lista de protocolos pendiente/en proceso para seleccionar y generar worklist.
     */
    public function index(Request $request): View
    {
        $this->authorize('a25.worklist');

        $branches = LabBranch::orderBy('name')->get(['id', 'name']);
        $branchFilter = $request->filled('lab_branch_id') ? (int) $request->input('lab_branch_id') : null;
        if ($branchFilter && ! LabBranch::query()->whereKey($branchFilter)->exists()) {
            $branchFilter = null;
        }

        $dateFilter = null;
        if ($request->filled('date')) {
            $request->validate([
                'date' => ['required', 'date'],
            ]);
            $dateFilter = $request->input('date');
        }

        $query = Admission::with(['patient', 'admissionTests.test', 'labBranch'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest();

        if ($branchFilter) {
            $query->where('lab_branch_id', $branchFilter);
        }

        if ($dateFilter) {
            $query->whereDate('date', $dateFilter);
        }

        $admissions = $query->paginate(50)->withQueryString();

        $vetQuery = VetAdmission::with(['customer', 'species', 'vetTests.test', 'labBranch'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest();

        if ($branchFilter) {
            $vetQuery->where('lab_branch_id', $branchFilter);
        }

        if ($dateFilter) {
            $vetQuery->whereDate('date', $dateFilter);
        }

        $vetAdmissions = $vetQuery->paginate(50, ['*'], 'vet_page')->withQueryString();

        return view('lab.a25.index', compact('admissions', 'vetAdmissions', 'branches', 'branchFilter', 'dateFilter'));
    }

    /**
     * Genera un preview del worklist sin descargarlo.
     */
    public function previewWorklist(Request $request): View|RedirectResponse
    {
        $this->authorize('a25.worklist');

        $request->validate([
            'admission_ids' => 'nullable|array',
            'admission_ids.*' => 'integer|exists:admissions,id',
            'vet_admission_ids' => 'nullable|array',
            'vet_admission_ids.*' => 'integer|exists:vet_admissions,id',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $admissionIds = array_values(array_filter(array_map('intval', $request->input('admission_ids', []))));
        $vetAdmissionIds = array_values(array_filter(array_map('intval', $request->input('vet_admission_ids', []))));

        if ($admissionIds === [] && $vetAdmissionIds === []) {
            return back()->with('error', 'Seleccioná al menos un protocolo clínico o veterinario.');
        }

        $admissions = $admissionIds === []
            ? collect()
            : Admission::with(['patient', 'admissionTests.test'])
                ->whereIn('id', $admissionIds)
                ->get();

        $vetAdmissions = $vetAdmissionIds === []
            ? collect()
            : VetAdmission::with(['customer', 'species', 'vetTests.test'])
                ->whereIn('id', $vetAdmissionIds)
                ->get();

        $labBranchId = $request->lab_branch_id ? (int) $request->lab_branch_id : null;
        $result = $this->worklistBuilder->buildCombined($admissions, $vetAdmissions, $labBranchId);

        return view('lab.a25.worklist-preview', compact('result', 'admissionIds', 'vetAdmissionIds', 'labBranchId'));
    }

    /**
     * Descarga el worklist (import.txt) para los protocolos seleccionados.
     */
    public function downloadWorklist(Request $request): Response|RedirectResponse
    {
        $this->authorize('a25.worklist');

        $request->validate([
            'admission_ids' => 'nullable|array',
            'admission_ids.*' => 'integer|exists:admissions,id',
            'vet_admission_ids' => 'nullable|array',
            'vet_admission_ids.*' => 'integer|exists:vet_admissions,id',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $admissionIds = array_values(array_filter(array_map('intval', $request->input('admission_ids', []))));
        $vetAdmissionIds = array_values(array_filter(array_map('intval', $request->input('vet_admission_ids', []))));

        if ($admissionIds === [] && $vetAdmissionIds === []) {
            return back()->with('error', 'Seleccioná al menos un protocolo clínico o veterinario.');
        }

        $admissions = $admissionIds === []
            ? collect()
            : Admission::with(['admissionTests.test'])
                ->whereIn('id', $admissionIds)
                ->get();

        $vetAdmissions = $vetAdmissionIds === []
            ? collect()
            : VetAdmission::with(['vetTests.test'])
                ->whereIn('id', $vetAdmissionIds)
                ->get();

        $labBranchId = $request->lab_branch_id ? (int) $request->lab_branch_id : null;
        $result = $this->worklistBuilder->buildCombined($admissions, $vetAdmissions, $labBranchId);

        if ($result['lines'] === 0) {
            return back()->with('error', 'No hay determinaciones pendientes con equivalencia A25 configurada para los protocolos seleccionados.');
        }

        return response($result['content'], 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="import.txt"',
        ]);
    }

    /**
     * Importa el archivo de resultados exportado por el equipo A25.
     */
    public function importResults(Request $request): View|RedirectResponse
    {
        $this->authorize('a25.import');

        $request->validate([
            'results_file' => 'required|file|mimes:txt,csv|max:2048',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $content = file_get_contents($request->file('results_file')->getRealPath());
        $labBranchId = $request->lab_branch_id ? (int) $request->lab_branch_id : null;

        $importResult = $this->resultParser->import($content, $labBranchId);

        $branches = LabBranch::orderBy('name')->get(['id', 'name']);

        return view('lab.a25.import-result', compact('importResult', 'branches'));
    }

    /**
     * Asigna o actualiza el id de equipo externo en una admisión.
     * Accesible desde el show de admisión.
     */
    public function assignSampleId(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorize('lab-admissions.edit');

        $request->validate([
            'external_equipment_sample_id' => 'nullable|string|max:50',
        ]);

        $admission->update([
            'external_equipment_sample_id' => $request->external_equipment_sample_id ?: null,
        ]);

        return back()->with('success', 'ID de equipo actualizado.');
    }

    /**
     * Asigna o actualiza el id de equipo externo en un protocolo veterinario (mismo flujo que clínico para import A25).
     */
    public function assignVetSampleId(Request $request, VetAdmission $vetAdmission): RedirectResponse
    {
        $this->authorize('a25.worklist');
        $this->authorize('vet-admissions.edit');

        $request->validate([
            'external_equipment_sample_id' => 'nullable|string|max:50',
        ]);

        $vetAdmission->update([
            'external_equipment_sample_id' => $request->external_equipment_sample_id ?: null,
        ]);

        return back()->with('success', 'ID de equipo actualizado.');
    }
}
