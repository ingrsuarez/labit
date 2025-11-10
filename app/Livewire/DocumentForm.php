<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;


class DocumentForm extends Component
{
     use WithFileUploads;

    public $documentId;
    public $name;
    public $observaciones;
    public $fecha_creacion;
    public $fecha_vencimiento;
    public $status = 'activo';
    public $employee_id;
    public $files = [];

    public function mount($documentId = null)
    {
        $this->documentId = $documentId;

        if ($documentId) {
            $document = Document::with('files')->findOrFail($documentId);
            $this->name = $document->name;
            $this->observaciones = $document->observaciones;
            $this->fecha_creacion = $document->fecha_creacion;
            $this->fecha_vencimiento = $document->fecha_vencimiento;
            $this->status = $document->status;
            $this->employee_id = $document->employee_id;
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'fecha_creacion' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_creacion',
            'status' => 'required|in:activo,vencido,pendiente',
            'comments' => 'nullable|string',
            'files.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // max 5MB por archivo
        ];
        
    }

    public function save()
    {
        $this->validate();
        $document = Document::updateOrCreate(
            ['id' => $this->documentId],
            [
                'employee_id' => $this->employee_id,
                'name' => $this->name,
                'comments' => $this->observaciones,
                'fecha_creacion' => $this->fecha_creacion,
                'fecha_vencimiento' => $this->fecha_vencimiento,
                'status' => $this->status,
                'creator' => Auth::id(),
            ]
        );

        foreach ($this->files as $file) {
            $path = $file->store('documents', 'public');

            $document->files()->create([
                'filename' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        session()->flash('message', 'Documento guardado exitosamente.');
        return redirect()->route('documents.index'); 
    }

    public function render()
    {
        $employees = Employee::orderBy('name')->get();
        return view('livewire.document-form', compact('employees'));
    }
}
