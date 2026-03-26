<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Sample;
use App\Models\Test;
use App\Models\Worksheet;
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

        $preview = null;
        $filters = $request->only(['date_from', 'date_to', 'protocol_from', 'protocol_to', 'include_without_results', 'include_with_results']);

        if ($request->has('preview') || $request->has('pdf')) {
            $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'protocol_from' => 'nullable|string',
                'protocol_to' => 'nullable|string',
            ]);

            $preview = $this->buildPreviewData($worksheet, $request);
        }

        return view('worksheets.show', compact('worksheet', 'preview', 'filters'));
    }

    public function generatePdf(Request $request, Worksheet $worksheet)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'protocol_from' => 'nullable|string',
            'protocol_to' => 'nullable|string',
        ]);

        $worksheet->load('tests');
        $data = $this->buildPreviewData($worksheet, $request);

        $pdf = PDF::loadView('worksheets.pdf', [
            'worksheet' => $worksheet,
            'rows' => $data['rows'],
            'tests' => $data['tests'],
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
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
            ->whereBetween('date', [$request->date_from, $request->date_to]);

        if ($request->filled('protocol_from')) {
            $query->where('protocol_number', '>=', $request->protocol_from);
        }
        if ($request->filled('protocol_to')) {
            $query->where('protocol_number', '<=', $request->protocol_to);
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
                $results[$testId] = $at ? ($at->result ?? '') : '';
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
            ->whereBetween('entry_date', [$request->date_from, $request->date_to]);

        if ($request->filled('protocol_from')) {
            $query->where('protocol_number', '>=', $request->protocol_from);
        }
        if ($request->filled('protocol_to')) {
            $query->where('protocol_number', '<=', $request->protocol_to);
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
                $results[$testId] = $det ? ($det->result ?? '') : '';
            }

            return [
                'protocol' => $sample->protocol_number,
                'name' => $sample->customer ? $sample->customer->name : '—',
                'results' => $results,
            ];
        })->values();
    }
}
