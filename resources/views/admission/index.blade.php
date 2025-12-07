<x-lab-layout>
    
    

    
    {{-- Selected Patient --}}
    @isset($current_patient)
        <form class="mx-4 md:mx-6" action="{{route('admission.store')}}" method="POST">
            @csrf
            <div class="bg-white pb-4 px-2 max-w-sm rounded-lg shadow-lg">
                <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">
                    Paciente: 
                    
                    {{ucfirst($current_patient->name).' '.ucfirst($current_patient->lastName) }}
                </h2>
                <p class="mt-1 text-sm leading-6 text-gray-400">Dni: 
                    <span class="text-gray-800 font-bold">{{$current_patient->patientId}}</span>
                    - Fecha de nacimiento: 
                    <span class="text-gray-800 font-bold"> {{$current_patient->birth}}</span>
                    - Teléfono:
                    <span class="text-gray-800 font-bold"> {{$current_patient->phone}}</span>
                </p>
                <p class="mt-1 text-sm leading-6 text-gray-600">Fecha de nacimiento: {{$current_patient->birth}}</p>
                <div class="mt-2 flex items-center justify-end gap-x-6">
                    
                    <a href="{{route('admission.index')}}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Modificar</a>
                </div>
                <input type="hidden" name="patient_id" value="{{$current_patient->id}}">
            </div>
            <div class="bg-white pb-4 px-2 mt-2 rounded-lg shadow-lg ">
                <h2 class=" text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">Datos del pedido:</h2>
                {{-- <p class="mt-1 text-sm leading-6 text-gray-600">Agregar análisis a la base de datos:</p> --}}
            
                <div class="text-sm mt-4 grid grid-cols-1 gap-x-6 gap-y-2 md:grid-cols-7">
                    <div class="w-full">
                        <label for="name" class="mb-2 text-sm font-medium text-gray-900">Fecha:</label>
                        <input type="date" placeholder="Fecha" name="date" autocomplete="off" value="<?php echo date("Y-m-d");?>" autofocus required 
                        class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                    </div>
                    <div class="w-full">
                        <label for="client" class="mb-2 text-sm font-medium text-gray-900">Cobertura:</label>
                        <select id="client_id" name="client_id" autocomplete="off" class="w-full form-input px-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm transition duration-150 ease-in-out">
                            <option disabled selected value="">Seleccionar</option>
                            @foreach ($insurances as $insurance)
                            <option value="{{$insurance->id}}">{{strtoupper($insurance->name)}}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="w-full">
                        <label for="client" class="mb-2 text-sm font-medium text-gray-900">Número de afiliado:</label>
                        <input type="text" placeholder="Nro. afiliado" name="insurance_number" autocomplete="off"  
                        class="w-full bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 block">
                    
                    </div>
    
                    <div class="w-full">
                        <label for="client" class="mb-2 text-sm font-medium text-gray-900">Sala:</label>
                        <select id="room" name="room" autocomplete="off" class="w-full form-input px-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-sm transition duration-150 ease-in-out">
                            
                            <option selected value="1">Sala 1</option>
                            <option value="2">Sala 2</option> 
                            <option value="3">Sala 3</option>   
                        </select>
                    </div>
    
                </div>
                
                <!-- Sección de análisis -->
                <div x-data="analisisManager()" class="mt-6">
                    <h2 class="text-xl font-bold mb-4">Análisis</h2>


                    <!-- Campo de búsqueda con detección del Enter -->
                    <div class="mb-4">
                        <input type="text" x-model="search" @input="filterAnalyses" @keydown.enter.prevent="addFirstAnalysis"
                            placeholder="Buscar análisis por nombre o código"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        
                        <!-- Lista desplegable de análisis filtrados -->
                        <ul class="max-h-40 overflow-y-auto bg-white border border-gray-300 mt-2" x-show="filteredAnalyses.length">
                            <template x-for="analysis in filteredAnalyses" :key="analysis.id">
                                <li class="px-2 py-1 hover:bg-gray-200 cursor-pointer" @click="addAnalysis(analysis)">
                                    <span x-text="analysis.code + ' - ' + analysis.name"></span>
                                </li>
                            </template>
                        </ul>
                    </div>


                    <!-- Tabla dinámica de análisis seleccionados -->
                    <table class="w-full border border-collapse">
                        <thead>
                            <tr class="bg-blue-200">
                                <th class="border px-4 py-2">Código</th>
                                <th class="border px-4 py-2">Nombre</th>
                                <th class="border px-4 py-2">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in selectedAnalyses" :key="item.id">
                                <tr>
                                    <td class="border px-4 py-2" x-text="item.code"></td>
                                    <td class="border px-4 py-2" x-text="item.name"></td>
                                    <td class="border px-4 py-2">
                                        <button type="button" @click="removeAnalysis(index)"
                                            class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>
                                    </td>
                                    <!-- Enviar datos al backend -->
                                    <input type="hidden" :name="'analyses[' + index + '][id]'" x-model="item.id">
                                    <input type="hidden" :name="'analyses[' + index + '][code]'" x-model="item.code">
                                    <input type="hidden" :name="'analyses[' + index + '][name]'" x-model="item.name">
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                    
                
                
            
                
        
                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Cancelar</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Guardar</button>
                </div>
            </div>
        </form>
    @else
        <div class="bg-white pb-4 px-2 rounded-lg shadow-lg ">
            <h2 class="text-base font-semibold leading-7 text-gray-200 bg-blue-500 rounded -ml-2 -mr-2 py-2 px-2 shadow-lg">
                Paciente:
            </h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Complete los datos:</p>
            @livewire('patient.find')
        </div>
    @endif

    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        const analyses = @json($analisis);
        function addAnalisis() {
            const table = document.getElementById('tablaAnalisis').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
    
            const cellCode = newRow.insertCell(0);
            const cellName = newRow.insertCell(1);
            const cellValue = newRow.insertCell(2);
            const cellParticular = newRow.insertCell(3);
            const cellActions = newRow.insertCell(4);
    
            // Campos de entrada vacíos para el nuevo análisis
            cellCode.innerHTML = `<input type="text" name="code[]" class="w-full text-gray-900 rounded-md">`;
            cellName.innerHTML = `<input type="text" name="name[]" class="w-full text-gray-900 rounded-md">`;
            cellValue.innerHTML = `<input type="text" name="value[]" class="w-full text-gray-900 rounded-md">`;
            cellParticular.innerHTML = `<input type="text" name="particular_price[]" class="w-full text-gray-900 rounded-md">`;
    
            // Botón de acción para eliminar el análisis de la fila
            cellActions.innerHTML = `<button type="button" onclick="removeRow(this)" class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>`;
        }

        function analisisManager() {
            return {
                search: '',
                analyses: analyses,
                filteredAnalyses: [],
                selectedAnalyses: [],

                // Filtra la lista en base al término ingresado
                filterAnalyses() {
                    if (this.search.length > 1) {
                        this.filteredAnalyses = this.analyses.filter(a => 
                            a.name.toLowerCase().includes(this.search.toLowerCase()) || 
                            a.code.includes(this.search)
                        );
                    } else {
                        this.filteredAnalyses = [];
                    }
                },

                // Agregar un análisis a la lista
                addAnalysis(analysis) {
                    if (!this.selectedAnalyses.some(a => a.id === analysis.id)) {
                        this.selectedAnalyses.push({...analysis});
                    }
                    this.search = '';
                    this.filteredAnalyses = [];
                },

                // Agregar el primer análisis al presionar Enter
                addFirstAnalysis() {
                    if (this.filteredAnalyses.length > 0) {
                        this.addAnalysis(this.filteredAnalyses[0]);
                    }
                },

                // Eliminar un análisis seleccionado
                removeAnalysis(index) {
                    this.selectedAnalyses.splice(index, 1);
                }
            };
        }


    </script>
                  
  
</x-lab-layout>