<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\DocumentFile;

class DocumentController extends Controller
{
    public function index()
    {
        return view('documents.index');

    }

    public function create()
    {
        $employees = Employee::orderBy('name')->get();
        return view('documents.create', compact('employees'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'fecha_creacion' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_creacion',
            'status' => 'required|in:activo,vencido,pendiente',
            'comments' => 'nullable|string',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $document = Document::create([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'comments' => $request->comments,
            'fecha_creacion' => $request->fecha_creacion,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'status' => $request->status,
            'creator' => Auth::id(),
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                $path = $file->store('documents', 'public');

                $document->files()->create([
                    'filename' => $path,
                    'display_name' => $request->file_names[$index] ?? null,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }


        return redirect()->route('documents.index')->with('message', 'Documento creado con éxito.');
    }

    public function edit(Document $document)
    {
        $employees = Employee::orderBy('name')->get();
        return view('documents.edit', compact('employees','document'));
    }

    public function update(Request $request, Document $document)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'fecha_creacion' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_creacion',
            'status' => 'required|in:activo,vencido,pendiente',
            'observaciones' => 'nullable|string',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $document->update([
            'employee_id' => $request->employee_id,
            'name' => $request->name,
            'comments' => $request->observaciones,
            'fecha_creacion' => $request->fecha_creacion,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'status' => $request->status,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $index => $file) {
                $path = $file->store('documents', 'public');
                $document->files()->create([
                    'filename' => $path,
                    'display_name' => $request->file_names[$index] ?? null,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('documents.index')->with('message', 'Documento actualizado correctamente.');
    }
}
