<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;

class ShowUsers extends Component
{
    public $users;

    public function mount($users = null)
    {
        if ($users) {
            // Cargar usuarios con sus roles y empleados asociados
            $userIds = $users->pluck('id');
            $this->users = User::with(['roles', 'employee'])
                ->whereIn('id', $userIds)
                ->get();
        } else {
            $this->users = User::with(['roles', 'employee'])->get();
        }
    }

    public function render()
    {
        return view('livewire.show-users');
    }
}
