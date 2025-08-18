<?php

namespace App\Livewire;

use Livewire\Component;

class AdmissionAnalysisTable extends Component
{
    public $rows = []; // Array para almacenar las filas de análisis

    protected $listeners = ['analysisSelected' => 'setAnalysis'];

    public function mount()
    {
        $this->addRow(); // Agrega la primera fila al inicializar
    }

    public function addRow()
    {
        // Cada fila es un array que guarda información de código, nombre, precio y precio particular
        $this->rows[] = ['code' => '', 'name' => '', 'precio' => '', 'particular' => ''];
    }

    public function removeRow($index)
    {
        // Elimina una fila específica
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows); // Reindexa el array
    }

    public function setAnalysis($index, $analysis)
    {
        // Asigna los datos del análisis seleccionado a la fila correspondiente
        $this->rows[$index] = [
            'code' => $analysis['code'],
            'name' => $analysis['name'],
            'precio' => $analysis['precio'],
            'precio_particular' => $analysis['precio_particular'],
        ];
    }
    public function render()
    {
        return view('livewire.admission-analysis-table');
    }
}
