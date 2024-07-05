<?php

namespace App\Livewire;

use Livewire\Component;

class ShowAnalisis extends Component
{

    public $data;

    public function mount()
    {
        $this->data[] = ['', '', ''];
    }

    public function addRow()
    {
        $this->data[] = ['', '', ''];
    }

    public function render()
    {
        return view('livewire.show-analisis');
    }
}
