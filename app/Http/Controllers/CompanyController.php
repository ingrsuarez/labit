<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::withCount('users')->orderBy('name')->get();

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'cuit' => 'required|string|max:13|unique:companies,cuit',
            'tax_condition' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'iibb' => 'nullable|string|max:50',
            'activity_start' => 'nullable|date',
        ]);

        Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Empresa creada correctamente.');
    }

    public function show(Company $company)
    {
        $company->load('users');
        $availableUsers = User::whereDoesntHave('companies', function ($q) use ($company) {
            $q->where('companies.id', $company->id);
        })->orderBy('name')->get();

        return view('companies.show', compact('company', 'availableUsers'));
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'cuit' => 'required|string|max:13|unique:companies,cuit,'.$company->id,
            'tax_condition' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'iibb' => 'nullable|string|max:50',
            'activity_start' => 'nullable|date',
        ]);

        $company->update($validated);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroy(Company $company)
    {
        $company->update(['is_active' => false]);

        return redirect()->route('companies.index')
            ->with('success', 'Empresa desactivada correctamente.');
    }

    public function switchCompany(Request $request)
    {
        $request->validate(['company_id' => 'required|exists:companies,id']);

        $user = auth()->user();
        $hasAccess = $user->companies()->where('companies.id', $request->company_id)->exists();

        if (! $hasAccess) {
            abort(403);
        }

        session(['active_company_id' => (int) $request->company_id]);

        return redirect()->back();
    }

    public function attachUser(Request $request, Company $company)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_default' => 'boolean',
        ]);

        $company->users()->syncWithoutDetaching([
            $request->user_id => ['is_default' => $request->boolean('is_default')],
        ]);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Usuario asignado correctamente.');
    }

    public function detachUser(Company $company, User $user)
    {
        $company->users()->detach($user->id);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Usuario desvinculado correctamente.');
    }

    public function setDefaultCompany(Company $company, User $user)
    {
        $user->companies()->updateExistingPivot($company->id, ['is_default' => false]);
        $user->companies()->each(function ($c) use ($user) {
            $user->companies()->updateExistingPivot($c->id, ['is_default' => false]);
        });
        $user->companies()->updateExistingPivot($company->id, ['is_default' => true]);

        return redirect()->route('companies.show', $company)
            ->with('success', 'Empresa por defecto actualizada.');
    }
}
