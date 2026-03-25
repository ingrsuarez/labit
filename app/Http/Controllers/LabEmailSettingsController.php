<?php

namespace App\Http\Controllers;

use App\Models\LabSetting;
use Illuminate\Http\Request;

class LabEmailSettingsController extends Controller
{
    private array $settingKeys = [
        'results_email',
        'results_from_name',
        'results_default_subject',
        'results_signature',
        'notifications_email',
        'notifications_from_name',
        'notifications_default_subject',
        'notifications_signature',
    ];

    public function edit()
    {
        $settings = [];
        foreach ($this->settingKeys as $key) {
            $settings[$key] = LabSetting::get($key, '');
        }

        return view('lab.email-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'results_email' => 'required|email',
            'results_from_name' => 'required|string|max:255',
            'results_default_subject' => 'required|string|max:255',
            'results_signature' => 'nullable|string',
            'notifications_email' => 'required|email',
            'notifications_from_name' => 'required|string|max:255',
            'notifications_default_subject' => 'required|string|max:255',
            'notifications_signature' => 'nullable|string',
        ]);

        foreach ($this->settingKeys as $key) {
            if ($request->has($key)) {
                LabSetting::set($key, $request->input($key));
            }
        }

        return back()->with('success', 'Configuración de correos actualizada correctamente.');
    }
}
