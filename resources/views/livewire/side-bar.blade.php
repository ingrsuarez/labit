<div>
    <!-- Sidenav -->
    <nav
        id="sidenav-8"
        class="fixed left-0 top-16 z-[1035] h-screen w-50 -translate-x-full overflow-hidden invisible md:visible bg-zinc-800 shadow-[0_4px_12px_0_rgba(0,0,0,0.07),_0_2px_4px_rgba(0,0,0,0.05)] data-[te-sidenav-hidden='false']:translate-x-0 dark:bg-zinc-800"
        data-te-sidenav-init
        data-te-sidenav-hidden="false"
        data-te-sidenav-position="absolute"
        data-te-sidenav-accordion="true">
        <ul
        class="relative m-0 list-none px-[0.2rem] pb-12"
        data-te-sidenav-menu-ref>
        
        <li class="relative pt-4">
            <button onclick="showMenu1(true)" class="focus:outline-none focus:text-indigo-400 text-left  text-white flex justify-between items-center w-full space-x-14 ">
                <span class="px-6 text-[0.8rem] font-bold uppercase text-gray-300 dark:text-gray-400">
                INGRESO:</span>
                <svg id="icon1" class="transform" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 15L12 9L6 15" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div id="menu1" class="flex justify-start  flex-col w-full md:w-auto">
                <a class="text-gray-300 flex cursor-pointer items-center truncate rounded-[5px] px-6 py-[0.45rem] text-[0.85rem]
                data-[active=active]:bg-slate-50  data-[active=active]:text-inherit
                hover:bg-slate-50 hover:text-inherit hover:outline-none
                focus:bg-slate-50 focus:text-inherit focus:outline-none"
                data-active="{{ Route::currentRouteName()=='admission.index' ? 'active' : ''}}"
                href="{{ route('admission.index') }}">
                    <span
                        class="mr-4 [&>svg]:h-5 [&>svg]:w-5 [&>svg]:text-gray-300 dark:[&>svg]:text-gray-300">
                        <svg fill="currentColor" height="800px" width="800px" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" xmlns:xlink="http://www.w3.org/1999/xlink" enable-background="new 0 0 512 512">
                            <g>
                            <path d="m495,396c-8-8-20.9-8-28.9,0l-20.6,20.6-25.5-25.5 68.3-68.3c8-8 8-20.9 0-28.9-8-8-20.9-8-28.9,0l-16.4,16.4-168.9-169c-17.7-17.7-63-44.9-116.7-12.9l-111.5-111.4c-8-8-20.9-8-28.9,0-8,8-8,20.9 0,28.9l111.5,111.5c-23.1,36.3-18.8,85 12.9,116.7l169.1,168.9-16.4,16.3c-8,8-8,20.9 0,28.9 4,4 17.7,11.2 28.9,0l30.8-30.8 37.5-37.5 25.5,25.5-20.7,20.6c-8,8-8,20.9 0,28.9 4,4 17.4,11.5 28.9,0l70-70c8-8 8-20.9 0-28.9zm-155.7,18.2l-43-42.9 25.4-25.4c8-8 8-20.9 0-28.9-8-8-20.9-8-28.9,0l-25.4,25.4-19.7-19.7 25.4-25.4c8-8 8-20.9 0-28.9-8-8-20.9-8-28.9,0l-25.4,25.4-19.6-19.8 25.4-25.4c8-8 8-20.9 0-28.9-8-8-20.9-8-28.9,0l-25.4,25.4-.1-.1c-20.7-20.7-20.7-54.3 0-74.9 14.7-14.7 49-25.9 75,0l169.1,169-75,75.1z"/>
                            </g>
                        </svg>
                    </span>
                    <span>Nuevo Pedido</span>
                </a>

                <a class="text-gray-300 flex cursor-pointer items-center truncate rounded-[5px] px-6 py-[0.45rem] text-[0.85rem]
                data-[active=active]:bg-slate-50  data-[active=active]:text-inherit
                hover:bg-slate-50 hover:text-inherit hover:outline-none
                focus:bg-slate-50 focus:text-inherit focus:outline-none"
                data-active="{{ Route::currentRouteName()=='patient.index' ? 'active' : ''}}"
                href="{{ route('patient.index') }}">
                    
                    <span
                        class="mr-4 [&>svg]:h-5 [&>svg]:w-5 [&>svg]:text-gray-400 dark:[&>svg]:text-gray-300">
                        <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-5 w-5">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </span>
                    <span>Nuevo Paciente </span>
                </a>

                <a class="text-gray-300 flex cursor-pointer items-center truncate rounded-[5px] px-6 py-[0.45rem] text-[0.85rem]
                data-[active=active]:bg-slate-50  data-[active=active]:text-inherit
                hover:bg-slate-50 hover:text-inherit hover:outline-none
                focus:bg-slate-50 focus:text-inherit focus:outline-none"
                data-active="{{ Route::currentRouteName()=='patient.show' ? 'active' : ''}}"
                href="{{ route('patient.show') }}">
                    
                    <span
                        class="mr-4 [&>svg]:h-5 [&>svg]:w-5 [&>svg]:text-gray-400 dark:[&>svg]:text-gray-300">
                        <svg 
                            xmlns="http://www.w3.org/2000/svg" 
                            fill="none" 
                            width="800px" 
                            height="800px" 
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor">
                            <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </span>
                    <span>Buscar Paciente </span>
                </a>
            </div>
        </li>


        <li class="relative pt-4">
            <button onclick="showMenu2(true)" class="focus:outline-none focus:text-indigo-400 text-left  text-white flex justify-between items-center w-full space-x-14 ">
                <span class="px-6 text-[0.8rem] font-bold uppercase text-gray-300 dark:text-gray-400">
                Laboratorio</span>
                <svg id="icon2" class="transform" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 15L12 9L6 15" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div id="menu2" class="flex justify-start  flex-col w-full md:w-auto">
                <a class="text-gray-300 flex cursor-pointer items-center truncate rounded-[5px] px-6 py-[0.45rem] text-[0.85rem]
                data-[active=active]:bg-slate-50  data-[active=active]:text-inherit
                hover:bg-slate-50 hover:text-inherit hover:outline-none
                focus:bg-slate-50 focus:text-inherit focus:outline-none"
                data-active="{{ Route::currentRouteName()=='tests.index' ? 'active' : ''}}"
                href="{{ route('tests.index') }}">
                <span
                    class="mr-4 [&>svg]:h-3.5 [&>svg]:w-3.5 [&>svg]:text-gray-300 dark:[&>svg]:text-gray-300">
                    <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="h-3.5 w-3.5">
                    <path fill="none" d="M0 0H24V24H0z"/> 
                    <path d="M17 2v2h-1v14c0 2.21-1.79 4-4 4s-4-1.79-4-4V4H7V2h10zm-3 8h-4v8c0 1.105.895 2 2 2s2-.895 2-2v-8zm-1 5c.552 0 1 .448 1 1s-.448 1-1 1-1-.448-1-1 .448-1 1-1zm-2-3c.552 0 1 .448 1 1s-.448 1-1 1-1-.448-1-1 .448-1 1-1zm3-8h-4v4h4V4z"/> 
            
                    </svg>
                </span>
                <span>Análisis</span>
                </a>

                <a class="text-gray-300 flex cursor-pointer items-center truncate rounded-[5px] px-6 py-[0.45rem] text-[0.85rem]
                data-[active=active]:bg-slate-50  data-[active=active]:text-inherit
                hover:bg-slate-50 hover:text-inherit hover:outline-none
                focus:bg-slate-50 focus:text-inherit focus:outline-none"
                data-active="{{ Route::currentRouteName()=='insurance.index' ? 'active' : ''}}"
                href="{{ route('insurance.index') }}">
                <span
                    class="mr-4 [&>svg]:h-3.5 [&>svg]:w-3.5 [&>svg]:text-gray-400 dark:[&>svg]:text-gray-300">
                    <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    class="h-3.5 w-3.5">
                    <path
                        fill-rule="evenodd"
                        d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625zM7.5 15a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 017.5 15zm.75 2.25a.75.75 0 000 1.5H12a.75.75 0 000-1.5H8.25z"
                        clip-rule="evenodd" />
                    <path
                        d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" />
                    </svg>
                </span>
                <span>Coberturas Médicas</span>
                </a>

               
            </div>
        </li>
        


        </ul>
    </nav>
    <!-- Sidenav -->

    <!-- Toggler -->
    <button
    class="mt-10 inline-block rounded bg-primary px-6 py-2.5 text-xs font-medium uppercase leading-tight text-white shadow-md transition duration-150 ease-in-out hover:bg-primary-700 hover:shadow-lg focus:bg-primary-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-primary-800 active:shadow-lg"
    data-te-sidenav-toggle-ref
    data-te-target="#sidenav-8"
    aria-controls="#sidenav-8"
    aria-haspopup="true">
    <span class="block [&>svg]:h-5 [&>svg]:w-5 [&>svg]:text-white">
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        class="h-5 w-5">
        <path
        fill-rule="evenodd"
        d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z"
        clip-rule="evenodd" />
    </svg>
    </span>
    </button>
    <!-- Toggler -->


    <script>
        let icon1 = document.getElementById("icon1");
        let menu1 = document.getElementById("menu1");
        const showMenu1 = (flag) => {
          if (flag) {
            icon1.classList.toggle("rotate-180");
            menu1.classList.toggle("hidden");
          }
        };

        let icon2 = document.getElementById("icon2");
        let menu2 = document.getElementById("menu2");
        const showMenu2 = (flag) => {
          if (flag) {
            icon2.classList.toggle("rotate-180");
            menu2.classList.toggle("hidden");
          }
        };



    </script>
</div>
