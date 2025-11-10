<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Document;
use App\Models\Employee;

class DocumentosGeneral extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $employee_id = '';

    public function applyFilters()
    {
        $this->resetPage(); // reinicia la paginación al filtrar
    }

    public function update()
    {
        $this->resetPage(); // reinicia la paginación al filtrar
    }


    public function render()
    {
        
        $documents = Document::with(['employee', 'user', 'files'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->employee_id, fn($q) => $q->where('employee_id', $this->employee_id))
            ->latest()
            ->paginate(10);

        $employees = Employee::orderBy('name')->get();

        return view('livewire.documentos-general', compact('documents', 'employees'));
    }
}
