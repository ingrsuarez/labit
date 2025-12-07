<x-lab-layout>
  
    @if (session('error'))
      <div class="bg-white pb-4 px-2 rounded-lg shadow-lg">
        {{ session('error') }}
      </div>
    @endif

    
    <form class="ml-4 md:mx-6" action="{{route('patient.store')}}" method="POST">
        @csrf
        {{-- <div class="space-y-10 ">       --}}
        <div class="bg-white pb-4 px-2 rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Nuevo paciente:</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Ingrese los datos para buscar:</p>


            
            @livewire('patient.show')
            


        </div>

    </form>   
    
</x-lab-layout>