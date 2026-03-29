<?php

namespace App\Http\Controllers;

use App\Models\LabBranch;
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
            'name'       => 'required|string|max:255',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:100',
            'province'   => 'nullable|string|max:100',
            'zip_code'   => 'nullable|string|max:10',
            'phone'      => 'nullable|string|max:50',
            'email'      => 'nullable|email|max:255',
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
            'name'       => 'required|string|max:255',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:100',
            'province'   => 'nullable|string|max:100',
            'zip_code'   => 'nullable|string|max:10',
            'phone'      => 'nullable|string|max:50',
            'email'      => 'nullable|email|max:255',
            'is_central' => 'boolean',
        ]);

        if (! empty($validated['is_central']) && ! $labBranch->is_central) {
            LabBranch::where('is_central', true)->update(['is_central' => false]);
        }

        $labBranch->update($validated);

        return redirect()->route('lab-branches.index')
            ->with('success', 'Sede actualizada correctamente.');
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
