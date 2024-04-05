<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\User;

class ShowUsers extends Component
{
    public $users;

    public function render()
    {
        return view('livewire.show-users');
    }
}
