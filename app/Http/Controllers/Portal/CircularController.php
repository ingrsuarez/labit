<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Circular;
use App\Models\CircularSignature;
use Illuminate\Http\Request;

class CircularController extends Controller
{
    /**
     * Mostrar lista de circulares para el empleado
     */
    public function index(Request $request)
    {
        $employee = auth()->user()->employee;
        
        if (!$employee) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'No tienes un perfil de empleado asociado.');
        }

        $tab = $request->get('tab', 'pending');

        // Circulares pendientes de firma
        $pendingCirculars = Circular::pendingForEmployee($employee)->get();

        // Circulares firmadas
        $signedCirculars = Circular::active()
            ->whereHas('signatures', function($query) use ($employee) {
                $query->where('employee_id', $employee->id)
                      ->whereNotNull('signed_at');
            })
            ->orderBy('date', 'desc')
            ->get();

        return view('portal.circulars.index', [
            'pendingCirculars' => $pendingCirculars,
            'signedCirculars' => $signedCirculars,
            'tab' => $tab,
            'employee' => $employee,
        ]);
    }

    /**
     * Ver detalle de una circular
     */
    public function show(Circular $circular)
    {
        $employee = auth()->user()->employee;
        
        if (!$employee) {
            return redirect()->route('portal.dashboard');
        }

        // Registrar lectura si no existe
        $signature = CircularSignature::firstOrCreate(
            [
                'circular_id' => $circular->id,
                'employee_id' => $employee->id,
            ],
            [
                'read_at' => now(),
            ]
        );

        // Si ya existe pero no tiene read_at, actualizarlo
        if (!$signature->read_at) {
            $signature->update(['read_at' => now()]);
        }

        return view('portal.circulars.show', [
            'circular' => $circular,
            'signature' => $signature,
            'employee' => $employee,
        ]);
    }

    /**
     * Firmar una circular
     */
    public function sign(Request $request, Circular $circular)
    {
        $employee = auth()->user()->employee;
        
        if (!$employee) {
            return back()->with('error', 'No tienes un perfil de empleado asociado.');
        }

        // Buscar o crear el registro de firma
        $signature = CircularSignature::firstOrCreate(
            [
                'circular_id' => $circular->id,
                'employee_id' => $employee->id,
            ],
            [
                'read_at' => now(),
            ]
        );

        // Registrar la firma
        $signature->update([
            'signed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('portal.circulars.index')
            ->with('success', 'Circular firmada correctamente.');
    }

    /**
     * Obtener cantidad de circulares pendientes (para notificaciones)
     */
    public static function getPendingCount(): int
    {
        $employee = auth()->user()?->employee;
        
        if (!$employee) {
            return 0;
        }

        return Circular::pendingForEmployee($employee)->count();
    }
}

