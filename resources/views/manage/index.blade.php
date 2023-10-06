<x-manage>
    
    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
        <form class="ml-4 md:mx-6" action="{{route('patient.store')}}" method="POST">
            @csrf
              {{-- <div class="space-y-10 ">       --}}
            <div class="bg-white pb-4 px-2 rounded-lg shadow-lg ">


            </div>
        </form>
    </div>
</x-manage>