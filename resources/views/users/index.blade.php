<x-manage>
    <div class="flex flex-col justify-start">
        <div class="rounded-t border border-neutral-200 bg-color-transparent" id="accordionExample">
            <div class="rounded-t-lg border border-neutral-200 bg-white dark:border-neutral-600 dark:bg-neutral-800">
                <div
                class="rounded-t-lg border border-neutral-200 bg-white dark:border-neutral-600 dark:bg-neutral-800">
                <h2 class="mb-0" id="headingTwo">
                <button
                class="rounded-t-lg bg-blue-500 group relative flex w-full items-center border-0  px-5 py-4 text-left text-base
                transition [overflow-anchor:none] hover:z-[2] focus:z-[3] focus:outline-none text-white"
                    type="button"
                    data-te-collapse-init
                    data-te-collapse-collapsed
                    data-te-target="#collapseTwo"
                    aria-expanded="false"
                    aria-controls="collapseTwo">
                    Listado de usuarios
                    <span
                    class="-mr-1 ml-auto h-5 w-5 shrink-0 rotate-[-180deg] fill-[#336dec] transition-transform duration-200 
                    ease-in-out group-[[data-te-collapse-collapsed]]:mr-0 group-[[data-te-collapse-collapsed]]:rotate-0 
                    group-[[data-te-collapse-collapsed]]:fill-[#212529] motion-reduce:transition-none dark:fill-blue-300 
                    dark:group-[[data-te-collapse-collapsed]]:fill-white" id="iconTwo">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-6 w-6">
                        <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                    </span>
                </button>
                </h2>
                <div
                id="collapseTwo"
                class="!visible hidden"
                data-te-collapse-item
                aria-labelledby="headingTwo"
                data-te-parent="#accordionExample">
                <div class="px-5 py-4">
                    
                    @livewire('show-users',['users'=>$users])
                </div>
            </div>




            
        </div>
    </div>

    <script>


        var accordion2 = document.getElementById('headingTwo');
        var collapse2 = document.getElementById('collapseTwo');
        var iconRotate2 = document.getElementById('iconTwo');
        
        accordion2.addEventListener("click",function(){
            collapse2.classList.toggle("hidden");
            iconRotate2.classList.toggle("rotate-[-0deg]");
        });
    </script>
</x-manage>