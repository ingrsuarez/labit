<?php

namespace App\Http\Livewire\Patient;

use Livewire\Component;
use App\Models\Patient;

class Find extends Component
{
    public $name;
    public $dni;
    public $lastName;
    public $current_patient;
    
    public function render()
    {
        if($this->name <> ''){    
            $patients = Patient::whereRaw('lower(name) LIKE "'.strtolower($this->name).'%"')->paginate(3); 
            return view('livewire.patient.find',compact('patients'));
        }elseif($this->lastName <> ''){
            $patients = Patient::whereRaw('lower(lastName) LIKE "'.strtolower($this->lastName).'%"')->paginate(3); 
            return view('livewire.patient.find',compact('patients'));

        }elseif($this->dni <> ''){
            $patients = Patient::where('patientId','LIKE',$this->dni.'%')->paginate(3); 
            return view('livewire.patient.find',compact('patients'));

        }else
        {
            
        
            // $patients = DB::table('patients')
            // ->join('historialClinico', 'codPacienteHC', '=', 'patients.codPaciente')
            // ->join('users', 'users.id', '=', 'historialClinico.codUsuarioHC')
            // ->where('historialClinico.codUsuarioHC','=',Auth::user()->id)
            // ->orderBy('historialClinico.fechaHC','DESC')
            // ->paginate(10);
            $patients = Patient::latest()->take(3)->get();
            return view('livewire.patient.find',compact('patients'));
        
        }
        return view('livewire.patient.find');
    }
}
