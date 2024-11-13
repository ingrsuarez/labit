<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Test;

class AnalysisSearchInput extends Component
{
    public $search = '';
    public $index; // Índice de la fila en la que se encuentra este componente
    public $analyses = [];
    public $showDropdown = false;
    public $highlightIndex = 0;

    public function mount($index)
    {
        $this->index = $index;
    }

    public function updateSearch()
    {
        if (!empty($this->search)) {
            // Realiza la búsqueda solo si hay texto
            dd($this->search);
            $this->analyses = Test::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('code', 'like', '%' . $this->search . '%')
                ->limit(5)
                ->get();
            
            $this->showDropdown = true; // Muestra el menú si hay resultados
            
        } else {
            $this->analyses = [];
            $this->showDropdown = false;
            $this->highlightIndex = -1; // Reinicia el índice resaltado si no hay resultados
        }
    }
    // public function updatedSearch()
    // {
    //     if (strlen($this->search) > 2) {
    //         $this->analyses = Test::where('name', 'like', '%' . $this->search . '%')
    //             ->orWhere('code', 'like', '%' . $this->search . '%')
    //             ->limit(10)
    //             ->get();
    //         $this->showDropdown = true;
    //     } else {
    //         $this->analyses = [];
    //         $this->showDropdown = false;
    //     }
    // }

    public function selectAnalysis($analysisId)
    {
        $analysis = Test::find($analysisId);

        if ($analysis) {
            // Emitimos los datos al componente padre con el índice para actualizar la fila correspondiente
            $this->emit('analysisSelected', $this->index, [
                'code' => $analysis->code,
                'name' => $analysis->name,
                'precio' => $analysis->precio,
                'precio_particular' => $analysis->precio_particular,
            ]);
        }

        $this->reset('search', 'analyses');
        $this->showDropdown = false;
    }

    // Función para incrementar el índice de resaltado en el menú desplegable
    public function incrementHighlight()
    {
        if ($this->highlightIndex === count($this->analyses) - 1) {
            $this->highlightIndex = 0;
        } else {
            $this->highlightIndex++;
        }
    }

    // Función para decrementar el índice de resaltado en el menú desplegable
    public function decrementHighlight()
    {
        if ($this->highlightIndex === 0) {
            $this->highlightIndex = count($this->analyses) - 1;
        } else {
            $this->highlightIndex--;
        }
    }

    // Función para seleccionar el análisis resaltado actualmente en el menú desplegable
    public function selectHighlightedAnalysis()
    {
        if (isset($this->analyses[$this->highlightIndex])) {
            $this->selectAnalysis($this->analyses[$this->highlightIndex]->id);
        }
    }

    public function hideDropdown()
    {
        // Método para ocultar el menú cuando el input pierde el enfoque
        $this->showDropdown = false;
        $this->highlightIndex = -1; // Reinicia el índice resaltado al ocultar el menú
    }

    public function render()
    {
        return view('livewire.analysis-search-input');
    }
}
