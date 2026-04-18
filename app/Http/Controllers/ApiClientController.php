<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\Company;
use App\Models\LabBranch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiClientController extends Controller
{
    public function index(Request $request): View
    {
        $query = ApiClient::with(['labBranch', 'company', 'createdBy']);

        if ($request->filled('lab_branch_id')) {
            $query->where('lab_branch_id', $request->lab_branch_id);
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->active);
        }

        // Activas (last_used_at NOT NULL) primero ordenadas desc; las nunca
        // usadas al final ordenadas por creación desc.
        $clients = $query
            ->orderByRaw('last_used_at IS NULL')
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $branches = LabBranch::orderBy('name')->get();

        return view('api-client.index', compact('clients', 'branches'));
    }

    public function create(): View
    {
        $branches = LabBranch::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('api-client.create', compact('branches', 'companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'lab_branch_id' => 'required|exists:lab_branches,id',
            'company_id' => 'required|exists:companies,id',
            'notes' => 'nullable|string|max:2000',
            'active' => 'nullable|boolean',
            'patient_data_level' => 'nullable|in:'.ApiClient::LEVEL_MINIMAL.','.ApiClient::LEVEL_STANDARD,
        ]);

        $plainKey = ApiClient::generateKey();

        $client = ApiClient::create([
            'name' => $data['name'],
            'lab_branch_id' => $data['lab_branch_id'],
            'company_id' => $data['company_id'],
            'notes' => $data['notes'] ?? null,
            'active' => $request->boolean('active', true),
            'patient_data_level' => $data['patient_data_level'] ?? ApiClient::LEVEL_MINIMAL,
            'api_key_hash' => ApiClient::hashKey($plainKey),
            'key_preview' => ApiClient::buildPreview($plainKey),
            'created_by' => auth()->id(),
        ]);

        $client->logAudit('create', "API client creado: {$client->name}");

        return redirect()
            ->route('api-clients.show', $client)
            ->with('api_key_just_created', $plainKey);
    }

    public function show(ApiClient $apiClient): View
    {
        $apiClient->load(['labBranch', 'company', 'createdBy', 'auditLogs.user']);

        return view('api-client.show', ['client' => $apiClient]);
    }

    public function edit(ApiClient $apiClient): View
    {
        $apiClient->load(['labBranch', 'company']);

        return view('api-client.edit', ['client' => $apiClient]);
    }

    public function update(Request $request, ApiClient $apiClient): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'active' => 'nullable|boolean',
            'patient_data_level' => 'required|in:'.ApiClient::LEVEL_MINIMAL.','.ApiClient::LEVEL_STANDARD,
        ]);

        $apiClient->update([
            'name' => $data['name'],
            'notes' => $data['notes'] ?? null,
            'active' => $request->boolean('active'),
            'patient_data_level' => $data['patient_data_level'],
        ]);

        $apiClient->logAudit('update', "API client actualizado: {$apiClient->name}");

        return redirect()
            ->route('api-clients.show', $apiClient)
            ->with('success', 'API client actualizado correctamente.');
    }

    public function destroy(ApiClient $apiClient): RedirectResponse
    {
        $name = $apiClient->name;
        $apiClient->logAudit('delete', "API client eliminado: {$name}");
        $apiClient->delete();

        return redirect()
            ->route('api-clients.index')
            ->with('success', "API client '{$name}' eliminado.");
    }

    public function regenerate(ApiClient $apiClient): RedirectResponse
    {
        $plainKey = ApiClient::generateKey();

        $apiClient->update([
            'api_key_hash' => ApiClient::hashKey($plainKey),
            'key_preview' => ApiClient::buildPreview($plainKey),
            'requests_count' => 0,
            'last_used_at' => null,
        ]);

        $apiClient->logAudit('regenerate', "Key regenerada para: {$apiClient->name}");

        return redirect()
            ->route('api-clients.show', $apiClient)
            ->with('api_key_just_created', $plainKey);
    }
}
