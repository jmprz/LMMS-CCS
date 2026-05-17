<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100">

   <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)] sticky top-[80px]">
   <nav class="mt-8 px-4 space-y-2">
            <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">System Admin</div>

            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.dashboard') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
            </a>

            <a href="{{ route('admin.classroom') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.classroom*') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-folder-5-line mr-3 text-lg"></i> Classroom
            </a>

            <a href="{{ route('admin.users.index') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.users*') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-user-line mr-3 text-lg"></i> Users
            </a>

            <a href="{{ route('profile.edit') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('profile.edit') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-settings-5-line mr-3 text-lg"></i> Settings
            </a>
            <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Platform Support</div>

            <a href="#"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.about') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-information-line mr-3 text-lg"></i> About System
            </a>

            <a href="#"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.faqs') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-questionnaire-line mr-3 text-lg"></i> FAQs Hub
            </a>
        </nav>

    <div class="p-4 border-t border-gray-200 bg-gray-50/50 relative" x-data="{ open: false }" @click.away="open = false">
        
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute bottom-full left-4 right-4 mb-2 w-56 rounded-xl md:w-auto bg-white border border-gray-200 shadow-xl z-50 divide-y divide-gray-100"
             style="display: none;">
            
            <div class="py-1">
                <form id="admin-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                    @csrf
                </form>
                <a href="{{ route('logout') }}" 
                   class="flex items-center px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 transition"
                   onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                    <i class="ri-logout-box-r-line mr-2.5 text-red-500 text-sm"></i> Sign Out
                </a>
            </div>
        </div>

        <button @click="open = !open" class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-gray-200/60 transition duration-150 text-left">
            <div class="flex items-center min-w-0">
                <div class="h-9 w-9 rounded-xl bg-[#383838] flex items-center justify-center text-white uppercase font-black shadow-sm text-xs flex-shrink-0">
                    {{ substr(Auth::user()->name, 0, 1) }}{{ substr(strrchr(Auth::user()->name, " "), 1, 1) }}
                </div>
                
                <div class="ml-3 truncate">
                    <p class="text-xs font-black text-gray-800 truncate leading-snug">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider leading-none mt-0.5">
                        Administrator
                    </p>
                </div>
            </div>
           <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
               :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
        </button>
    </div>
</aside>
        <main class="flex-1 overflow-y-auto h-full">
            <div class="p-8 mt-[80px]">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Classes</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $activeClassesCount }}</h3>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i class="ri-terminal-box-line text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Students</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $totalStudents }}</h3>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                            <i class="ri-user-line text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Professors</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $totalProfessors }}</h3>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                            <i class="ri-shield-user-line text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Users</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $totalUsers }}</h3>
                        </div>
                        <div class="p-3 bg-orange-50 text-orange-600 rounded-lg">
                            <i class="ri-group-line text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="font-bold text-lg text-gray-800">Upcoming Class Schedule</h2>
                            <span class="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full uppercase">Next 24 Hours</span>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-xs font-bold text-gray-400 uppercase border-b border-gray-100">
                                        <th class="pb-3">Subject</th>
                                        <th class="pb-3">Section</th>
                                        <th class="pb-3">Time</th>
                                        <th class="pb-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($upcomingClasses as $upcoming)
                                    <tr>
                                        <td class="py-4 font-bold text-gray-700">{{ $upcoming->subject_name }}</td>
                                        <td class="py-4 text-gray-600">{{ $upcoming->program }} {{ $upcoming->year_level }}-{{ $upcoming->section }}</td>
                                        <td class="py-4 text-gray-600">{{ $upcoming->schedule_time }}</td>
                                        <td class="py-4 text-right">
                                            <span class="text-[10px] font-bold bg-yellow-50 text-yellow-600 px-2 py-1 rounded-md uppercase">Scheduled</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-gray-400 italic">No upcoming classes found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h2 class="font-bold text-lg text-gray-800 mb-4">Activity Logs</h2>
                        <div class="space-y-6 overflow-y-auto max-h-[400px] pr-2">
                            @forelse($logs as $log)
                            <div class="relative pl-6 border-l-2 {{ $log->type == 'alert' ? 'border-red-400' : 'border-gray-200' }}">
                                <div class="absolute -left-[9px] top-0 w-4 h-4 bg-white border-2 {{ $log->type == 'alert' ? 'border-red-400' : 'border-gray-300' }} rounded-full"></div>
                                <p class="text-sm font-bold text-gray-800 leading-tight">{{ $log->description }}</p>
                                <p class="text-[11px] text-gray-400 mt-1 uppercase">{{ $log->created_at->diffForHumans() }} • {{ $log->user_name ?? 'System' }}</p>
                            </div>
                            @empty
                            <div class="text-center py-8 text-gray-400 italic text-sm">No recent logs.</div>
                            @endforelse
                        </div>
                        <a href="#" class="block text-center mt-6 text-xs font-bold text-gray-400 hover:text-[#383838] transition uppercase tracking-widest">View All Logs</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>