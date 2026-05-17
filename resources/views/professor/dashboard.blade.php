<x-app-layout>
    <x-slot name="header">
    </x-slot>
    <div class="fixed inset-0 flex bg-gray-100">


       <aside class="w-64 border-r border-gray-200 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)]">
    
    <div class="flex flex-col flex-grow overflow-y-auto">
        
        <nav class="mt-8 px-4 space-y-1">
            <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Workspace</div>

            <a href="{{ route('professor.dashboard') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('professor.dashboard') ? 'bg-[#383838] text-white font-black shadow-sm' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition duration-150">
                <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
            </a>
        </nav>

        <nav class="px-4 space-y-1 mb-6 border-t border-gray-100 pt-6">
            <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">My Classes</div>
            
            @php
                // Safe lookup: Use $sessions if available, fallback to $activeSessions from the dashboard
              $sidebarCourses = $sessions ?? $activeSessions ?? \App\Models\LabSession::where('faculty_id', auth()->id())->latest()->get();
            @endphp

            @forelse($sidebarCourses as $sideSession)
                <a href="{{ route('professor.classroom.show', $sideSession->id) }}"
                   class="flex items-start py-3 px-4 rounded-xl text-xs transition duration-150 group {{ request()->is('professor/classroom/' . $sideSession->id) ? 'bg-gray-100 border-gray-300 text-black font-bold' : 'text-gray-600 hover:bg-gray-50' }}">
                    
                    <i class="ri-book-3-line text-lg mr-3 flex-shrink-0 mt-0.5 {{ request()->is('professor/classroom/' . $sideSession->id) ? 'text-black' : 'text-gray-400 group-hover:text-black' }} transition"></i>
                    
                    <div class="flex flex-col min-w-0">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="truncate font-black text-xs tracking-tight uppercase text-gray-800">
                                {{ $sideSession->class_code }} | {{ $sideSession->program }} - {{ $sideSession->year_level }}{{ $sideSession->section }}
                            </span>
                            @if($sideSession->is_active)
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse flex-shrink-0"></span>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold text-gray-400 truncate mt-0.5 tracking-wide">
                            {{ $sideSession->schedule_day }} • {{ $sideSession->schedule_time }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="px-4 py-3 text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider italic">No laboratory sessions</p>
                </div>
            @endforelse
              <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Platform Support</div>

            <a href="#"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.about') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-information-line mr-3 text-lg"></i> About System
            </a>

            <a href="#"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.faqs') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-questionnaire-line mr-3 text-lg"></i> FAQs Hub
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-gray-100 bg-gray-50/50 relative" x-data="{ open: false }" @click.away="open = false">
        
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="transform opacity-0 scale-95 translate-y-2"
             class="absolute bottom-full left-4 right-4 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl p-1.5 z-50 flex flex-col gap-0.5"
             style="display: none;">
            
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full text-left flex items-center px-3.5 py-2.5 text-xs font-black text-red-600 hover:bg-red-50 rounded-xl transition duration-150 tracking-wide">
                    <i class="ri-logout-box-r-line mr-2.5 text-base"></i> Sign Out
                </button>
            </form>
        </div>

        <div @click="open = !open" class="flex items-center justify-between cursor-pointer group p-1 -m-1 rounded-xl hover:bg-gray-100/50 transition">
            <div class="flex items-center min-w-0">
                @php
                    $nameTokens = explode(' ', Auth::user()->name);
                    $firstInitial = substr($nameTokens[0], 0, 1);
                    $lastInitial = count($nameTokens) > 1 ? substr(end($nameTokens), 0, 1) : '';
                    $profileInitials = strtoupper($firstInitial . $lastInitial);
                @endphp
                <div class="h-8 w-8 rounded-xl bg-[#383838] group-hover:bg-black flex items-center justify-center text-white text-[10px] font-black uppercase shadow-sm mr-2.5 flex-shrink-0 transition">
                    {{ $profileInitials }}
                </div>
                
                <div class="min-w-0">
                    <p class="text-xs font-black text-gray-800 truncate leading-none">{{ Auth::user()->name }}</p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-1 leading-none">Professor</p>
                </div>
            </div>

            <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
               :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
        </div>
    </div>
</aside>

        <main class="flex-1 overflow-y-auto h-full">
            <div class="p-8 mt-[80px]">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Sessions</p>
                            <h3 class="text-3xl font-black text-gray-800">12</h3>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i class="ri-terminal-box-line text-2xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Connected Students</p>
                            <h3 class="text-3xl font-black text-gray-800">148</h3>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                            <i class="ri-user-line text-2xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">System Load</p>
                            <h3 class="text-3xl font-black text-gray-800">42%</h3>
                        </div>
                        <div class="p-3 bg-orange-50 text-orange-600 rounded-lg">
                            <i class="ri-dashboard-3-line text-2xl"></i>
                        </div>
                    </div>
                </div>


                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="font-bold text-lg text-gray-800">Student Monitoring</h2>
                            <span
                                class="text-xs font-bold bg-green-100 text-green-700 px-3 py-1 rounded-full">LIVE</span>
                        </div>
                        <div
                            class="h-64 flex items-center justify-center border-2 border-dashed border-gray-200 rounded-lg text-gray-400 font-medium">
                            Monitoring Active...
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h2 class="font-bold text-lg text-gray-800 mb-4">Recent Alerts</h2>
                        <div class="space-y-4">
                            <div class="border-l-2 border-red-500 pl-4">
                                <p class="text-sm font-bold text-gray-800">Attempted Blocked Site</p>
                                <p class="text-xs text-gray-500">Student #402 • 2m ago</p>
                            </div>
                            <div class="border-l-2 border-yellow-500 pl-4">
                                <p class="text-sm font-bold text-gray-800">Connection Latency</p>
                                <p class="text-xs text-gray-500">Room 301 • 15m ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>