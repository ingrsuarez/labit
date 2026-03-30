<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\LabBranch;
use App\Models\Sample;
use App\Models\VetAdmission;
use Illuminate\Http\Request;

class LabBranchController extends Controller
{
    public function index()
    {
        $this->authorize('lab.section');

        $branches = LabBranch::orderByDesc('is_central')->orderBy('name')->get();

        return view('lab-branches.index', compact('branches'));
    }

    public function create()
    {
        $this->authorize('lab.section');

        return view('lab-branches.create');
    }

    public function store(Request $request)
    {
        $this->authorize('lab.section');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_central' => 'boolean',
        ]);

        if (! empty($validated['is_central'])) {
            LabBranch::where('is_central', true)->update(['is_central' => false]);
        }

        LabBranch::create($validated);

        return redirect()->route('lab-branches.index')
            ->with('success', 'Sede creada correctamente.');
    }

    public function edit(LabBranch $labBranch)
    {
        $this->authorize('lab.section');

        return view('lab-branches.edit', compact('labBranch'));
    }

    public function update(Request $request, LabBranch $labBranch)
    {
        $this->authorize('lab.section');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_central' => 'boolean',
        ]);

        if (! empty($validated['is_central']) && ! $labBranch->is_central) {
            LabBranch::where('is_central', true)->update(['is_central' => false]);
        }

        $labBranch->update($validated);

        return redirect()->route('lab-branches.index')
            ->with('success', 'Sede actualizada correctamente.');
    }

    public function assignOrphans()
    {
        $this->authorize('lab.section');

        $orphanAdmissions = Admission::whereNull('lab_branch_id')->count();
        $orphanSamples = Sample::whereNull('lab_branch_id')->count();
        $orphanVet = VetAdmission::whereNull('lab_branch_id')->count();
        $branches = LabBranch::active()->orderByDesc('is_central')->orderBy('name')->get();

        return view('lab-branches.assign-orphans', compact(
            'orphanAdmissions',
            'orphanSamples',
            'orphanVet',
            'branches'
        ));
    }

    public function assignOrphansStore(Request $request)
    {
        $this->authorize('lab.section');

        $request->validate([
            'lab_branch_id' => 'required|exists:lab_branches,id',
            'modules' => 'required|array|min:1',
            'modules.*' => 'in:admissions,samples,vet_admissions',
        ]);

        $branchId = $request->lab_branch_id;
        $counts = [];

        if (in_array('admissions', $request->modules)) {
            $counts['admissions'] = Admission::whereNull('lab_branch_id')
                ->update(['lab_branch_id' => $branchId]);
        }

        if (in_array('samples', $request->modules)) {
            $counts['samples'] = Sample::whereNull('lab_branch_id')
                ->update(['lab_branch_id' => $branchId]);
        }

        if (in_array('vet_admissions', $request->modules)) {
            $counts['vet_admissions'] = VetAdmission::whereNull('lab_branch_id')
                ->update(['lab_branch_id' => $branchId]);
        }

        $total = array_sum($counts);
        $branchName = LabBranch::find($branchId)->name;

        return redirect()->route('lab-branches.index')
            ->with('success', "Se asignaron {$total} protocolos a la sede '{$branchName}'.");
    }

    public function destroy(LabBranch $labBranch)
    {
        $this->authorize('lab.section');

        $hasProtocols = $labBranch->admissions()->exists()
            || $labBranch->samples()->exists()
            || $labBranch->vetAdmissions()->exists();

        if ($hasProtocols) {
            $labBranch->update(['is_active' => false]);

            return redirect()->route('lab-branches.index')
                ->with('success', 'Sede desactivada (tiene protocolos asociados).');
        }

        $labBranch->delete();

        return redirect()->route('lab-branches.index')
            ->with('success', 'Sede eliminada.');
    }
}
