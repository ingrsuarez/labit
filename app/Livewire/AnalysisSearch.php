<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Test;

class AnalysisSearch extends Component
{
    public $search = '';      // Almacena el texto de búsqueda
    public $analyses = [];    // Almacena los resultados
    public $showDropdown = false; // Controla la visibilidad del menú desplegable
    public $highlightIndex = -1;   // Almacena el índice seleccionado para la navegación con el teclado

    public function updateSearch()
    {
        if (!empty($this->search)) {
            // Realiza la búsqueda solo si hay texto
            // dd($this->search);
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

    public function incrementHighlight()
    {
        // Incrementa el índice resaltado, pero se asegura de no exceder el límite de la lista
        if ($this->highlightIndex < count($this->analyses) - 1) {
            $this->highlightIndex++;
        }
        
    }

    public function decrementHighlight()
    {
        // Decrementa el índice resaltado, pero no permite valores negativos
        if ($this->highlightIndex > 0) {
            $this->highlightIndex--;
        }
    }

    public function selectHighlightedAnalysis()
    {
        // Selecciona el análisis actualmente resaltado
        $selectedAnalysis = $this->analyses[$this->highlightIndex] ?? null;
        if ($selectedAnalysis) {
            $this->selectAnalysis($selectedAnalysis->id);
        }
        
    }

    public function selectAnalysis($id)
    {
        $selectedAnalysis = Test::find($id);
        $this->search = $selectedAnalysis ? $selectedAnalysis->name : '';
        $this->analyses = [];
        $this->showDropdown = false; // Oculta el menú al seleccionar
        
    }

    public function hideDropdown()
    {
        // Método para ocultar el menú cuando el input pierde el enfoque
        $this->showDropdown = false;
        $this->highlightIndex = -1; // Reinicia el índice resaltado al ocultar el menú
    }

    public function render()
    {
        
        return view('livewire.analysis-search');
    }
}
