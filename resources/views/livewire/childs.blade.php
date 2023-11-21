<ul class="flex flex-row mt-10 justify-center">
    @foreach($childs as $child)
        <div class="-mt-10 border-l-2 absolute h-10 border-gray-400"></div>
        @if($loop->count == 1)
            <li class="relative p-6">

                {{-- <div class="border-t-2 absolute h-8 border-gray-400 top-0"
                    style="left: 50%; right: 0px;"></div> --}}
                <div class="relative flex justify-center">
                    <div class="-mt-6 border-l-2 absolute h-6 border-gray-400 top-0"></div>
                    <div class="text-center">
                        <div class="flex flex-col justify-center items-center">
                            {{-- <div class="w-16">
                                <img class="block rounded-full m-auto shadow-md" alt="Leo Six"
                                    src="https://randomuser.me/api/portraits/men/83.jpg" />
                            </div> --}}
                            <div class="text-gray-600">
                                <p class="text-xl font-bold">{{ucwords($child->name)}}</p>
                                <div class="border-2">
                                    @foreach($child->employees as $employee)
                                        <p>{{ucwords($employee->name." ".$employee->lastName)}}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @include('livewire.childs',['childs' => $child->childs])
                    </div>
                </div>
            </li>
            @break
        @endif
        @if($loop->last)
            <li class="relative p-6">
                <div class="border-t-2 absolute h-8 border-gray-400 top-0"
                    style="left: 0px; right: 50%;"></div>
                <div class="relative flex justify-center">
                    <div class="-mt-6 border-l-2 absolute h-6 border-gray-400 top-0"></div>
                    <div class="text-center">
                        <div class="flex flex-col justify-center items-center">
                            {{-- <div class="w-16">
                                <img class="block rounded-full m-auto shadow-md" alt="Leo Six"
                                    src="https://randomuser.me/api/portraits/men/83.jpg" />
                            </div> --}}
                            <div class="text-gray-600">
                                <p class="text-xl font-bold">{{ucwords($child->name)}}</p>
                                <div class="border-2">
                                    @foreach($child->employees as $employee)
                                        <p>{{ucwords($employee->name." ".$employee->lastName)}}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @include('livewire.childs',['childs' => $child->childs])
                    </div>
                </div>
            </li>
        @elseif ($loop->first)
            <li class="relative p-6">

                <div class="border-t-2 absolute h-8 border-gray-400 top-0"
                    style="left: 50%; right: 0px;"></div>
                <div class="relative flex justify-center">
                    <div class="-mt-6 border-l-2 absolute h-6 border-gray-400 top-0"></div>
                    <div class="text-center">
                        <div class="flex flex-col justify-center items-center">
                            <div class="text-gray-600">
                                <p class="text-xl font-bold">{{ucwords($child->name)}}</p>
                                <div class="border-2">
                                    @foreach($child->employees as $employee)
                                    <p>{{ucwords($employee->name." ".$employee->lastName)}}</p>    
                                    {{-- <div class="w-16">
                                        <img class="block rounded-full m-auto shadow-md" alt="Leo Six"
                                            src="https://randomuser.me/api/portraits/men/83.jpg" />
                                    </div> --}}
                                        
                                    @endforeach
                                <div class="border-2">
                            </div>
                        </div>
                        @include('livewire.childs',['childs' => $child->childs])
                    </div>
                </div>
            </li>
        @else
        <li class="relative p-6">

            <div class="border-t-2 absolute h-8 border-gray-400 top-0"
                style="left: 0px; right: 0px;"></div>
            <div class="relative flex justify-center">
                <div class="-mt-6 border-l-2 absolute h-6 border-gray-400 top-0"></div>
                <div class="text-center">
                    <div class="flex flex-col justify-center items-center">
                        
                        <div class="text-gray-600">
                            <p class="text-xl font-bold">{{ucwords($child->name)}}</p>
                            <div class="border-2">
                                @foreach($child->employees as $employee)
                                    {{-- <div class="w-16">
                                        <img class="block rounded-full m-auto shadow-md" alt="Leo Six"
                                            src="https://randomuser.me/api/portraits/men/83.jpg" />
                                    </div> --}}
                                    <p>{{ucwords($employee->name." ".$employee->lastName)}}</p>
                                @endforeach
                           
                            </div>
                        </div>
                    </div>
                    @include('livewire.childs',['childs' => $child->childs])
                </div>
            </div>
        </li>    
        @endif
    @endforeach
</ul>