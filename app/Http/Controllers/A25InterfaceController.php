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
        $branchFilter = $request->input('lab_branch_id');

        $query = Admission::with(['patient', 'admissionTests.test', 'labBranch'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->latest();

        if ($branchFilter) {
            $query->where('lab_branch_id', $branchFilter);
        }

        $admissions = $query->paginate(50)->withQueryString();

        return view('lab.a25.index', compact('admissions', 'branches', 'branchFilter'));
    }

    /**
     * Genera un preview del worklist sin descargarlo.
     */
    public function previewWorklist(Request $request): View|RedirectResponse
    {
        $this->authorize('a25.worklist');

        $request->validate([
            'admission_ids' => 'required|array|min:1',
            'admission_ids.*' => 'integer|exists:admissions,id',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $admissions = Admission::with(['patient', 'admissionTests.test'])
            ->whereIn('id', $request->admission_ids)
            ->get();

        $labBranchId = $request->lab_branch_id ? (int) $request->lab_branch_id : null;
        $result = $this->worklistBuilder->build($admissions, $labBranchId);

        $admissionIds = $request->admission_ids;

        return view('lab.a25.worklist-preview', compact('result', 'admissionIds', 'labBranchId'));
    }

    /**
     * Descarga el worklist (import.txt) para los protocolos seleccionados.
     */
    public function downloadWorklist(Request $request): Response|RedirectResponse
    {
        $this->authorize('a25.worklist');

        $request->validate([
            'admission_ids' => 'required|array|min:1',
            'admission_ids.*' => 'integer|exists:admissions,id',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $admissions = Admission::with(['admissionTests.test'])
            ->whereIn('id', $request->admission_ids)
            ->get();

        $labBranchId = $request->lab_branch_id ? (int) $request->lab_branch_id : null;
        $result = $this->worklistBuilder->build($admissions, $labBranchId);

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
