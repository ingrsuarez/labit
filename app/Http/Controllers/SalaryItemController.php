<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryItem;

class SalaryItemController extends Controller
{
    /**
     * Mostrar listado de ítems de sueldo
     */
    public function index()
    {
        $haberes = SalaryItem::haberes()->orderBy('order')->get();
        $deducciones = SalaryItem::deducciones()->orderBy('order')->get();
        
        $summary = [
            'total' => SalaryItem::count(),
            'haberes' => $haberes->count(),
            'deducciones' => $deducciones->count(),
            'activos' => SalaryItem::active()->count(),
        ];

        return view('salary.index', compact('haberes', 'deducciones', 'summary'));
    }

    /**
     * Mostrar formulario de nuevo ítem
     */
    public function create()
    {
        return view('salary.create');
    }

    /**
     * Guardar nuevo ítem
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:haber,deduccion'],
            'calculation_type' => ['required', 'in:percentage,fixed,hours'],
            'value' => ['required', 'numeric', 'min:0'],
            'base' => ['nullable', 'string', 'max:100'],
            'is_remunerative' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'period_type' => ['nullable', 'in:all_year,recurrent,specific'],
            'recurrent_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'specific_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'specific_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);

        $validated['is_remunerative'] = $request->has('is_remunerative');
        $validated['is_active'] = $request->has('is_active');
        $validated['base'] = $validated['base'] ?? 'basic_salary';
        $validated['order'] = $validated['order'] ?? SalaryItem::max('order') + 1;

        // Procesar período de aplicación
        $periodType = $request->input('period_type', 'all_year');
        $validated['applies_all_year'] = $periodType === 'all_year';
        $validated['recurrent_month'] = $periodType === 'recurrent' ? $request->input('recurrent_month') : null;
        $validated['specific_month'] = $periodType === 'specific' ? $request->input('specific_month') : null;
        $validated['specific_year'] = $periodType === 'specific' ? $request->input('specific_year') : null;

        // Limpiar campos no usados
        unset($validated['period_type']);

        SalaryItem::create($validated);

        return redirect()->route('salary.index')
            ->with('success', 'Concepto de sueldo creado correctamente.');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(SalaryItem $salaryItem)
    {
        return view('salary.edit', compact('salaryItem'));
    }

    /**
     * Actualizar ítem
     */
    public function update(Request $request, SalaryItem $salaryItem)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'in:haber,deduccion'],
            'calculation_type' => ['required', 'in:percentage,fixed,hours'],
            'value' => ['required', 'numeric', 'min:0'],
            'base' => ['nullable', 'string', 'max:100'],
            'is_remunerative' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'period_type' => ['nullable', 'in:all_year,recurrent,specific'],
            'recurrent_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'specific_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'specific_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);

        $validated['is_remunerative'] = $request->has('is_remunerative');
        $validated['is_active'] = $request->has('is_active');

        // Procesar período de aplicación
        $periodType = $request->input('period_type', 'all_year');
        $validated['applies_all_year'] = $periodType === 'all_year';
        $validated['recurrent_month'] = $periodType === 'recurrent' ? $request->input('recurrent_month') : null;
        $validated['specific_month'] = $periodType === 'specific' ? $request->input('specific_month') : null;
        $validated['specific_year'] = $periodType === 'specific' ? $request->input('specific_year') : null;

        // Limpiar campos no usados
        unset($validated['period_type']);

        $salaryItem->update($validated);

        return redirect()->route('salary.index')
            ->with('success', 'Concepto de sueldo actualizado correctamente.');
    }

    /**
     * Activar/Desactivar ítem
     */
    public function toggle(SalaryItem $salaryItem)
    {
        $salaryItem->is_active = !$salaryItem->is_active;
        $salaryItem->save();

        $status = $salaryItem->is_active ? 'activado' : 'desactivado';
        return redirect()->back()
            ->with('success', "Concepto {$status} correctamente.");
    }

    /**
     * Eliminar ítem
     */
    public function destroy(SalaryItem $salaryItem)
    {
        $salaryItem->delete();

        return redirect()->route('salary.index')
            ->with('success', 'Concepto de sueldo eliminado correctamente.');
    }
}

