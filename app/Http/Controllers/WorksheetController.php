<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionTest;
use App\Models\LabBranch;
use App\Models\Sample;
use App\Models\SampleDetermination;
use App\Models\Test;
use App\Models\Worksheet;
use App\Services\LabBranchResolver;
use Illuminate\Http\Request;
use PDF;

class WorksheetController extends Controller
{
    public function index()
    {
        $worksheets = Worksheet::with('creator')
            ->withCount('tests')
            ->orderBy('name')
            ->get();

        return view('worksheets.index', compact('worksheets'));
    }

    public function create()
    {
        return view('worksheets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:clinico,muestras',
            'tests' => 'required|array|min:1',
            'tests.*' => 'exists:tests,id',
        ]);

        $worksheet = Worksheet::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'created_by' => auth()->id(),
        ]);

        $syncData = [];
        foreach ($validated['tests'] as $index => $testId) {
            $syncData[$testId] = ['sort_order' => $index];
        }
        $worksheet->tests()->sync($syncData);

        return redirect()->route('worksheets.index')
            ->with('success', 'Planilla creada correctamente.');
    }

    public function edit(Worksheet $worksheet)
    {
        $worksheet->load('tests');

        return view('worksheets.edit', compact('worksheet'));
    }

    public function update(Request $request, Worksheet $worksheet)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:clinico,muestras',
            'tests' => 'required|array|min:1',
            'tests.*' => 'exists:tests,id',
        ]);

        $worksheet->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
        ]);

        $syncData = [];
        foreach ($validated['tests'] as $index => $testId) {
            $syncData[$testId] = ['sort_order' => $index];
        }
        $worksheet->tests()->sync($syncData);

        return redirect()->route('worksheets.index')
            ->with('success', 'Planilla actualizada correctamente.');
    }

    public function destroy(Worksheet $worksheet)
    {
        $worksheet->delete();

        return redirect()->route('worksheets.index')
            ->with('success', 'Planilla eliminada correctamente.');
    }

    public function show(Request $request, Worksheet $worksheet)
    {
        $worksheet->load('tests');

        $labBranches = LabBranchResolver::activeBranchesForForms();

        $preview = null;
        $filters = $request->only([
            'date_from', 'date_to', 'protocol_from', 'protocol_to',
            'include_without_results', 'include_with_results', 'lab_branch_id',
        ]);

        if ($request->has('preview') || $request->has('pdf')) {
            $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'protocol_from' => 'nullable|string',
                'protocol_to' => 'nullable|string',
                'lab_branch_id' => 'nullable|exists:lab_branches,id',
            ]);

            $preview = $this->buildPreviewData($worksheet, $request);
        }

        return view('worksheets.show', compact('worksheet', 'preview', 'filters', 'labBranches'));
    }

    public function generatePdf(Request $request, Worksheet $worksheet)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'protocol_from' => 'nullable|string',
            'protocol_to' => 'nullable|string',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
        ]);

        $worksheet->load('tests');
        $data = $this->buildPreviewData($worksheet, $request);

        $pdf = PDF::loadView('worksheets.pdf', [
            'worksheet' => $worksheet,
            'rows' => $data['rows'],
            'tests' => $data['tests'],
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
            'filterBranchLabel' => $this->worksheetBranchFilterLabel($request),
        ], [], [
            'orientation' => 'L',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 10,
        ]);

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $worksheet->name);
        $filename = "Planilla_{$safeName}_{$request->date_from}_{$request->date_to}.pdf";

        return $pdf->download($filename);
    }

    public function searchTests(Request $request)
    {
        $search = $request->get('q', '');
        $type = $request->get('type', 'clinico');

        $query = Test::whereNull('parent')
            ->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });

        if ($type === 'muestras') {
            $query->whereJsonContains('categories', 'aguas_alimentos');
        }

        $tests = $query->limit(20)->get(['id', 'code', 'name']);

        return response()->json($tests);
    }

    private function buildPreviewData(Worksheet $worksheet, Request $request): array
    {
        $testIds = $worksheet->tests->pluck('id')->toArray();
        $orderedTests = $worksheet->tests;

        $includeWithout = $request->boolean('include_without_results', true);
        $includeWith = $request->boolean('include_with_results', true);

        $rows = collect();

        if ($worksheet->type === 'clinico') {
            $rows = $this->buildClinicoData($testIds, $request, $includeWithout, $includeWith);
        } else {
            $rows = $this->buildMuestrasData($testIds, $request, $includeWithout, $includeWith);
        }

        return [
            'rows' => $rows,
            'tests' => $orderedTests,
        ];
    }

    private function buildClinicoData(array $testIds, Request $request, bool $includeWithout, bool $includeWith)
    {
        $query = Admission::with(['patient', 'admissionTests' => function ($q) use ($testIds) {
            $q->whereIn('test_id', $testIds);
        }])
            ->whereHas('admissionTests', function ($q) use ($testIds) {
                $q->whereIn('test_id', $testIds);
            })
            ->whereDate('date', '>=', $request->date_from)
            ->whereDate('date', '<=', $request->date_to);

        if ($request->filled('protocol_from')) {
            $query->where('protocol_number', '>=', $request->protocol_from);
        }
        if ($request->filled('protocol_to')) {
            $query->where('protocol_number', '<=', $request->protocol_to);
        }

        if ($request->filled('lab_branch_id')) {
            $query->where('lab_branch_id', $request->integer('lab_branch_id'));
        }

        $admissions = $query->orderBy('protocol_number')->get();

        return $admissions->filter(function ($admission) use ($testIds, $includeWithout, $includeWith) {
            $hasAnyResult = $admission->admissionTests
                ->whereIn('test_id', $testIds)
                ->filter(fn ($at) => $at->result !== null && $at->result !== '')
                ->isNotEmpty();

            if (! $includeWithout && ! $hasAnyResult) {
                return false;
            }
            if (! $includeWith && $hasAnyResult) {
                return false;
            }

            return true;
        })->map(function ($admission) use ($testIds) {
            $results = [];
            foreach ($testIds as $testId) {
                $at = $admission->admissionTests->where('test_id', $testId)->first();
                $results[$testId] = $at ? [
                    'id' => $at->id,
                    'value' => $at->result ?? '',
                    'is_validated' => (bool) $at->is_validated,
                ] : null;
            }

            return [
                'protocol' => $admission->protocol_number,
                'name' => $admission->patient ? $admission->patient->full_name : '—',
                'results' => $results,
            ];
        })->values();
    }

    private function buildMuestrasData(array $testIds, Request $request, bool $includeWithout, bool $includeWith)
    {
        $query = Sample::with(['customer', 'determinations' => function ($q) use ($testIds) {
            $q->whereIn('test_id', $testIds);
        }])
            ->whereHas('determinations', function ($q) use ($testIds) {
                $q->whereIn('test_id', $testIds);
            })
            ->whereDate('entry_date', '>=', $request->date_from)
            ->whereDate('entry_date', '<=', $request->date_to);

        if ($request->filled('protocol_from')) {
            $query->where('protocol_number', '>=', $request->protocol_from);
        }
        if ($request->filled('protocol_to')) {
            $query->where('protocol_number', '<=', $request->protocol_to);
        }

        if ($request->filled('lab_branch_id')) {
            $query->where('lab_branch_id', $request->integer('lab_branch_id'));
        }

        $samples = $query->orderBy('protocol_number')->get();

        return $samples->filter(function ($sample) use ($testIds, $includeWithout, $includeWith) {
            $hasAnyResult = $sample->determinations
                ->whereIn('test_id', $testIds)
                ->filter(fn ($d) => $d->result !== null && $d->result !== '')
                ->isNotEmpty();

            if (! $includeWithout && ! $hasAnyResult) {
                return false;
            }
            if (! $includeWith && $hasAnyResult) {
                return false;
            }

            return true;
        })->map(function ($sample) use ($testIds) {
            $results = [];
            foreach ($testIds as $testId) {
                $det = $sample->determinations->where('test_id', $testId)->first();
                $results[$testId] = $det ? [
                    'id' => $det->id,
                    'value' => $det->result ?? '',
                    'is_validated' => (bool) $det->is_validated,
                ] : null;
            }

            return [
                'protocol' => $sample->protocol_number,
                'name' => $sample->customer ? $sample->customer->name : '—',
                'results' => $results,
            ];
        })->values();
    }

    public function saveResults(Request $request, Worksheet $worksheet)
    {
        if ($worksheet->type === 'clinico') {
            $this->authorize('lab-results.create');
        } else {
            $this->authorize('samples-results.create');
        }

        $input = $request->input('results', []);
        if (empty($input)) {
            return redirect()->back()->with('info', 'No hay resultados para guardar.');
        }

        $saved = 0;

        if ($worksheet->type === 'clinico') {
            $admissionIds = collect();

            foreach ($input as $id => $value) {
                $at = AdmissionTest::find($id);
                if (! $at || $at->is_validated) {
                    continue;
                }

                $result = trim($value) !== '' ? trim($value) : null;
                $at->update(['result' => $result]);
                $admissionIds->push($at->admission_id);
                $saved++;
            }

            foreach ($admissionIds->unique() as $admId) {
                $admission = Admission::with('admissionTests')->find($admId);
                if ($admission) {
                    $admission->update(['status' => $admission->calculated_status]);
                }
            }
        } else {
            foreach ($input as $id => $value) {
                $det = SampleDetermination::find($id);
                if (! $det || $det->is_validated) {
                    continue;
                }

                $result = trim($value) !== '' ? trim($value) : null;
                $hasResult = $result !== null;

                $update = ['result' => $result];

                if ($hasResult) {
                    $update['status'] = 'completed';
                    if (! $det->analyzed_at) {
                        $update['analyzed_at'] = now();
                        $update['analyzed_by'] = auth()->id();
                    }
                } else {
                    $update['status'] = 'pending';
                    $update['analyzed_at'] = null;
                    $update['analyzed_by'] = null;
                }

                $det->update($update);
                $saved++;
            }
        }

        return redirect()->back()->with('success', "{$saved} resultado(s) guardado(s) correctamente.");
    }

    private function worksheetBranchFilterLabel(Request $request): string
    {
        if (! $request->filled('lab_branch_id')) {
            return 'Todas las sedes';
        }

        $name = LabBranch::query()
            ->active()
            ->whereKey($request->integer('lab_branch_id'))
            ->value('name');

        return $name ?? 'Sede';
    }
}
