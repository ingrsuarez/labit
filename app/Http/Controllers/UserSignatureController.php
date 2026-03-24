<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserSignatureController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'signature' => 'required|image|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        $user = $request->user();

        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $path = $request->file('signature')->store('signatures', 'public');
        $user->update(['signature_path' => $path]);

        return back()->with('success', 'Firma actualizada correctamente.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $user->update(['signature_path' => null]);

        return back()->with('success', 'Firma eliminada.');
    }
}
