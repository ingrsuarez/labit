<?php

namespace App\Http\Controllers;

use App\Models\A25AnalyteMapping;
use App\Models\LabBranch;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class A25AnalyteMappingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('a25.mappings.manage');

        $mappings = A25AnalyteMapping::with(['test', 'labBranch'])
            ->when($request->filled('search'), fn ($q) => $q->where(
                'equipment_analyte_name', 'like', '%'.$request->search.'%'
            ))
            ->orderBy('equipment_analyte_name')
            ->paginate(50)
            ->withQueryString();

        $branches = LabBranch::orderBy('name')->get(['id', 'name']);

        return view('lab.a25.mappings.index', compact('mappings', 'branches'));
    }

    public function create(): View
    {
        $this->authorize('a25.mappings.manage');

        $tests = Test::whereNull('parent')
            ->orWhereNotNull('parent')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $branches = LabBranch::orderBy('name')->get(['id', 'name']);

        return view('lab.a25.mappings.create', compact('tests', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('a25.mappings.manage');

        $data = $request->validate([
            'equipment_analyte_name' => 'required|string|max:255',
            'test_id' => 'required|exists:tests,id',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
            'material_type' => 'nullable|string|max:20',
        ]);

        $data['material_type'] = $data['material_type'] ?: 'SER';

        $exists = A25AnalyteMapping::where('equipment_analyte_name', $data['equipment_analyte_name'])
            ->where('lab_branch_id', $data['lab_branch_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Ya existe una equivalencia para ese nombre de analito en ese ámbito.');
        }

        A25AnalyteMapping::create($data);

        return redirect()->route('a25.mappings.index')
            ->with('success', 'Equivalencia creada correctamente.');
    }

    public function edit(A25AnalyteMapping $mapping): View
    {
        $this->authorize('a25.mappings.manage');

        $tests = Test::orderBy('name')->get(['id', 'name', 'code']);
        $branches = LabBranch::orderBy('name')->get(['id', 'name']);

        return view('lab.a25.mappings.edit', compact('mapping', 'tests', 'branches'));
    }

    public function update(Request $request, A25AnalyteMapping $mapping): RedirectResponse
    {
        $this->authorize('a25.mappings.manage');

        $data = $request->validate([
            'equipment_analyte_name' => 'required|string|max:255',
            'test_id' => 'required|exists:tests,id',
            'lab_branch_id' => 'nullable|exists:lab_branches,id',
            'material_type' => 'nullable|string|max:20',
        ]);

        $data['material_type'] = $data['material_type'] ?: 'SER';

        $exists = A25AnalyteMapping::where('equipment_analyte_name', $data['equipment_analyte_name'])
            ->where('lab_branch_id', $data['lab_branch_id'])
            ->where('id', '!=', $mapping->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Ya existe una equivalencia para ese nombre de analito en ese ámbito.');
        }

        $mapping->update($data);

        return redirect()->route('a25.mappings.index')
            ->with('success', 'Equivalencia actualizada.');
    }

    public function destroy(A25AnalyteMapping $mapping): RedirectResponse
    {
        $this->authorize('a25.mappings.manage');

        $mapping->delete();

        return redirect()->route('a25.mappings.index')
            ->with('success', 'Equivalencia eliminada.');
    }
}
