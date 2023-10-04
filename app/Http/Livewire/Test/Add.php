<?php

namespace App\Http\Livewire\Test;

use Livewire\Component;

class Add extends Component
{
    public $value;
    
    public function mount()
    {
        $this->value = '';
    }

    public function render()
    {
        return view('livewire.test.add');
    }

    public function tabPressed()
    {
        $this->value = 'Tabkey';
    }
}
