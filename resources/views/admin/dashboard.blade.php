<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100 overflow-hidden" x-data="{ sidebarOpen: false }">

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div x-show="sidebarOpen" 
             x-transition.opacity 
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 md:hidden" 
             style="display: none;">
        </div>

        <!-- Fixed Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed md:static inset-y-0 left-0 z-50 w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)] transform transition-transform duration-300 ease-in-out md:translate-x-0">
            <nav class="mt-8 px-4 space-y-2 overflow-y-auto flex-1">
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
            </nav>

            <div class="p-4 border-t border-gray-200 bg-gray-50/50 relative flex-shrink-0" x-data="{ open: false }"
                @click.away="open = false">
                <div x-show="open" x-transition:enter="transition ease-out duration-100"
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

                <button @click="open = !open"
                    class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-gray-200/60 transition duration-150 text-left">
                    <div class="flex items-center min-w-0">
                        <div
                            class="h-9 w-9 rounded-xl bg-[#383838] flex items-center justify-center text-white uppercase font-black shadow-sm text-xs flex-shrink-0">
                            {{ substr(Auth::user()->name, 0, 1) }}{{ substr(strrchr(Auth::user()->name, " "), 1, 1) }}
                        </div>
                        <div class="ml-3 truncate">
                            <p class="text-xs font-black text-gray-800 truncate leading-snug">{{ Auth::user()->name }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider leading-none mt-0.5">
                                Administrator</p>
                        </div>
                    </div>
                    <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
                        :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
                </button>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full flex flex-col min-w-0">
            <!-- Mobile Header Toggle Bar -->
            <div class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 mt-[80px] flex-shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="ri-menu-2-line text-xl"></i>
                </button>
                <span class="text-xs font-black uppercase text-gray-700 tracking-wider">Navigation</span>
            </div>

            <div class="p-4 sm:p-8 md:mt-[80px]">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Users</p>
                            <h3 class="text-2xl font-black text-gray-800 mt-1">{{ $totalUsers }}</h3>
                        </div>
                        <div class="p-2.5 bg-orange-50 text-orange-600 rounded-lg">
                            <i class="ri-group-line text-xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Professors</p>
                            <h3 class="text-2xl font-black text-gray-800 mt-1">{{ $totalProfessors }}</h3>
                        </div>
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                            <i class="ri-shield-user-line text-xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Students</p>
                            <h3 class="text-2xl font-black text-gray-800 mt-1">{{ $totalStudents }}</h3>
                        </div>
                        <div class="p-2.5 bg-green-50 text-green-600 rounded-lg">
                            <i class="ri-user-line text-xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Classes</p>
                            <h3 class="text-2xl font-black text-gray-800 mt-1">{{ $allSessions->count() }}</h3>
                        </div>
                        <div class="p-2.5 bg-purple-50 text-purple-600 rounded-lg">
                            <i class="ri-folder-5-line text-xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between sm:col-span-2 lg:col-span-1">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Classes</p>
                            <h3 class="text-2xl font-black text-gray-800 mt-1">{{ $activeClassesCount }}</h3>
                        </div>
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i class="ri-terminal-box-line text-xl"></i>
                        </div>
                    </div>
                </div>

                @include('partials.dashboard-chart-panel', ['chartConfigs' => $chartConfigs])

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="col-span-1 lg:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between overflow-hidden" 
                         x-data="{ 
                            search: '', 
                            programFilter: '', 
                            yearFilter: '',
                            matchesFilter(subject, code, program, year) {
                                const matchSearch = subject.toLowerCase().includes(this.search.toLowerCase()) || code.toLowerCase().includes(this.search.toLowerCase());
                                const matchProgram = this.programFilter === '' || program.toUpperCase() === this.programFilter.toUpperCase();
                                const matchYear = this.yearFilter === '' || String(year) === String(this.yearFilter);
                                return matchSearch && matchProgram && matchYear;
                            }
                         }">
                        <div>
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                                <div>
                                    <h2 class="font-bold text-lg text-gray-800 uppercase">Upcoming Class Schedule</h2>
                                    <p class="text-xs text-gray-400 font-medium">Chronological roadmap of incoming lab periods</p>
                                </div>
                                <span class="self-start text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full uppercase tracking-wider">Pending Pipeline</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                <div class="relative">
                                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input type="text" x-model="search" placeholder="Search subject or code..." 
                                           class="w-full pl-9 pr-4 py-1.5 text-xs bg-white rounded-lg border border-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400 font-medium placeholder-gray-400" />
                                </div>
                                <div>
                                    <select x-model="programFilter" class="w-full py-1.5 px-3 text-xs bg-white rounded-lg border border-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 font-bold text-gray-600">
                                        <option value="">All Programs / Courses</option>
                                        <option value="BSCS">BSCS (Computer Science)</option>
                                        <option value="BSIT">BSIT (Information Technology)</option>
                                    </select>
                                </div>
                                <div>
                                    <select x-model="yearFilter" class="w-full py-1.5 px-3 text-xs bg-white rounded-lg border border-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 font-bold text-gray-600">
                                        <option value="">All Year Levels</option>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse min-w-[500px]">
                                    <thead>
                                        <tr class="text-[11px] font-black text-gray-400 uppercase border-b border-gray-100 tracking-wider">
                                            <th class="pb-3 pl-1 w-1/3">Subject Name</th>
                                            <th class="pb-3 w-1/6">Class Code</th>
                                            <th class="pb-3 w-1/3">Day & Time</th>
                                            <th class="pb-3 pr-1 text-right w-1/6">Program & Year</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @php $hasUpcoming = false; @endphp
                                        @forelse($upcomingClasses as $upcoming)
                                            @php $hasUpcoming = true; @endphp
                                            <tr x-show="matchesFilter('{{ $upcoming->subject_name }}', '{{ $upcoming->class_code }}', '{{ $upcoming->program }}', '{{ $upcoming->year_level }}')"
                                                x-transition:enter="transition ease-out duration-200"
                                                class="hover:bg-gray-50/60 transition-colors">
                                                <td class="py-3.5 pl-1 font-bold text-xs text-gray-800 leading-snug">
                                                    {{ $upcoming->subject_name }}
                                                </td>
                                                <td class="py-3.5 text-xs font-mono font-bold text-gray-500 uppercase tracking-tight">
                                                    {{ $upcoming->class_code }}
                                                </td>
                                                <td class="py-3.5 text-xs text-gray-600 font-medium">
                                                    <span class="inline-flex items-center gap-1.5 bg-gray-100 px-2 py-0.5 rounded text-gray-700 font-bold text-[10px] uppercase">
                                                        <i class="ri-calendar-event-line text-gray-400"></i>{{ $upcoming->schedule_day }}
                                                    </span>
                                                    <span class="text-gray-400 mx-1">•</span>
                                                    <span class="font-semibold text-gray-600">{{ $upcoming->schedule_time }}</span>
                                                </td>
                                                <td class="py-3.5 pr-1 text-right text-xs font-black text-gray-700">
                                                    <span class="bg-dark text-gray-800 border border-gray-200 px-2 py-0.5 rounded text-[10px] uppercase font-black tracking-wider">
                                                        {{ $upcoming->program }} - {{ $upcoming->year_level }}{{ $upcoming->section }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-12 text-gray-400 italic text-xs font-medium bg-gray-50/30 rounded-xl">
                                                    <i class="ri-calendar-todo-line text-2xl text-gray-300 block mb-2"></i>
                                                    No upcoming classes found on database roster.
                                                </td>
                                            </tr>
                                        @endforelse

                                        @if($hasUpcoming)
                                            <tr x-cloak x-show="document.querySelectorAll('tbody tr[style*=\'display: none\']').length === {{ count($upcomingClasses) }}" class="border-none">
                                                <td colspan="4" class="text-center py-12 text-gray-400 italic text-xs font-medium bg-gray-50/30 rounded-xl">
                                                    <i class="ri-filter-off-line text-2xl text-gray-300 block mb-2"></i>
                                                    No upcoming classes match your selected filter criteria.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                         x-data="{ 
                            timeframe: 'all',
                            serverNow: {{ time() }},
                            checkLogTimeframe(logTimestamp) {
                                if (this.timeframe === 'all') return true;
                                const diffMinutes = (this.serverNow - logTimestamp) / 60;
                                if (this.timeframe === 'hour') return diffMinutes <= 60;
                                if (this.timeframe === 'today') return diffMinutes <= 1440;
                                return true;
                            }
                         }">
                        <div>
                            <div class="flex justify-between items-start gap-2 mb-4">
                                <div>
                                    <h2 class="font-bold text-lg text-gray-800 uppercase">Student Monitoring Feed</h2>
                                    <p class="text-xs text-gray-400 font-medium">Real-time behavior tracking telemetry</p>
                                </div>
                                <select x-model="timeframe" class="py-1 px-2 text-[10px] font-black bg-gray-50 border border-gray-200 rounded-md uppercase tracking-wider text-gray-500 focus:outline-none">
                                    <option value="all">All Logs</option>
                                    <option value="hour">Last 1 Hour</option>
                                    <option value="today">Today (24h)</option>
                                </select>
                            </div>

                            <div class="space-y-4 overflow-y-auto max-h-[380px] pr-1.5 mt-4">
                                @forelse($logs as $log)
                                    @php 
                                        $logTimestamp = $log->created_at ? $log->created_at->timestamp : time(); 
                                    @endphp
                                    <div x-show="checkLogTimeframe({{ $logTimestamp }})"
                                         x-transition:enter="transition ease-out duration-150"
                                         class="group p-3 rounded-xl bg-gray-50/40 hover:bg-gray-50 border border-gray-100 flex gap-3 transition">
                                        
                                        <div class="flex flex-col items-center flex-shrink-0">
                                            <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs shadow-sm
                                                {{ ($log->log_type ?? '') == 'alert' || ($log->log_type ?? '') == 'violation' ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                                <i class="{{ ($log->log_type ?? '') == 'alert' || ($log->log_type ?? '') == 'violation' ? 'ri-error-warning-line' : 'ri-compass-3-line' }} text-sm"></i>
                                            </div>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                                <p class="text-xs font-black text-gray-800 truncate">
                                                    @if($log->user && $log->user->last_name)
                                                        {{ $log->user->last_name }}, {{ $log->user->first_name }} {{ $log->user->middle_name ? substr($log->user->middle_name, 0, 1) . '.' : '' }}
                                                    @else
                                                        {{ $log->user->name ?? $log->user_name ?? 'Anonymous Student' }}
                                                    @endif
                                                </p>
                                                <span class="text-[9px] font-bold text-gray-400 whitespace-nowrap uppercase tracking-tight">
                                                    {{ $log->created_at ? $log->created_at->diffForHumans() : 'Just now' }}
                                                </span>
                                            </div>

                                            <p class="text-xs font-medium text-gray-600 leading-snug break-words">
                                                {{ $log->content ?? $log->description ?? 'Interacted with digital ecosystem' }}
                                            </p>

                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-2 pt-1.5 border-t border-gray-100 text-[10px] font-bold text-gray-400">
                                                <span class="inline-flex items-center gap-1 text-gray-500 bg-white border border-gray-200 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wide truncate max-w-[190px]">
                                                    <i class="ri-door-lock-line text-gray-400 flex-shrink-0"></i>
                                                    @if($log->labSession)
                                                        {{ $log->labSession->class_code }} ({{ $log->labSession->program }} - {{ $log->labSession->year_level }}{{ $log->labSession->section }})
                                                    @else
                                                        General Workspace
                                                    @endif
                                                </span>
                                                <span class="text-gray-300">•</span>
                                                <span class="font-mono text-gray-400">
                                                    {{ $log->created_at ? $log->created_at->format('M d, Y • h:i A') : now()->format('M d, Y • h:i A') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-12 text-gray-400 italic text-xs font-medium">
                                        <i class="ri-radar-line text-2xl text-gray-300 block mb-2 animate-pulse"></i>
                                        No recent active logs recorded in the system hub.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</x-app-layout>