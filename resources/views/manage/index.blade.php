<x-manage>
    
    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
        

        <!-- component -->
        @livewire('organization-chart',['employees'=>$employees, 'job'=>$job])


    </div>
</x-manage>