<?php

namespace App\Http\Controllers;

use App\Models\CashFlowSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashFlowSettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('cash-flow.manage');

        $settings = CashFlowSetting::forCompany((int) active_company_id());

        return view('cash-flow.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('cash-flow.manage');

        $validated = $request->validate([
            'iva_due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'form931_due_day' => ['required', 'integer', 'min:1', 'max:28'],
        ]);

        $settings = CashFlowSetting::forCompany((int) active_company_id());
        $settings->update($validated);

        return redirect()
            ->route('cash-flow.index')
            ->with('success', 'Configuración de vencimientos actualizada.');
    }
}
