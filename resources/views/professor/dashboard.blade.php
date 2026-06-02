<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="fixed inset-0 flex bg-gray-100" x-data="{ sidebarOpen: false }">

        <aside
            class="w-64 border-r border-gray-200 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)]">
            <div class="flex flex-col flex-grow overflow-y-auto">
                <nav class="mt-8 px-4 space-y-1">
                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Workspace
                    </div>
                    <a href="{{ route('professor.dashboard') }}"
                        class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('professor.dashboard') ? 'bg-[#383838] text-white font-black shadow-sm' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition duration-150">
                        <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                    </a>
                </nav>

                <nav class="px-4 space-y-1 mb-6 border-t border-gray-100 pt-6">
                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">My Classes
                    </div>

                    @foreach($sessions as $sideSession)
                        <a href="{{ route('professor.classroom.show', $sideSession->id) }}"
                            class="flex items-start py-3 px-4 rounded-xl text-xs transition duration-150 group {{ request()->is('professor/classroom/' . $sideSession->id) ? 'bg-gray-100 border-gray-300 text-black font-bold' : 'text-gray-600 hover:bg-gray-50' }}">

                            <i
                                class="ri-book-3-line text-lg mr-3 flex-shrink-0 mt-0.5 {{ request()->is('professor/classroom/' . $sideSession->id) ? 'text-black' : 'text-gray-400 group-hover:text-black' }} transition"></i>

                            <div class="flex flex-col min-w-0">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="truncate font-black text-xs tracking-tight uppercase text-gray-800">
                                        {{ $sideSession->class_code }} | {{ $sideSession->program }} -
                                        {{ $sideSession->year_level }}{{ $sideSession->section }}
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
                    @endforeach

                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 mt-6">Platform
                        Support</div>
                    <a href="#"
                        class="flex items-center py-2.5 px-4 rounded-xl text-xs text-gray-600 font-bold hover:bg-gray-100 transition">
                        <i class="ri-information-line mr-3 text-lg"></i> About System
                    </a>
                    <a href="#"
                        class="flex items-center py-2.5 px-4 rounded-xl text-xs text-gray-600 font-bold hover:bg-gray-100 transition">
                        <i class="ri-questionnaire-line mr-3 text-lg"></i> FAQs Hub
                    </a>
                </nav>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50/50 relative" x-data="{ open: false }"
                @click.away="open = false">
                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                    class="absolute bottom-full left-4 right-4 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl p-1.5 z-50 flex flex-col gap-0.5"
                    style="display: none;">
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                            class="w-full text-left flex items-center px-3.5 py-2.5 text-xs font-black text-red-600 hover:bg-red-50 rounded-xl transition tracking-wide">
                            <i class="ri-logout-box-r-line mr-2.5 text-base"></i> Sign Out
                        </button>
                    </form>
                </div>

                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group p-1 -m-1 rounded-xl hover:bg-gray-100/50 transition">
                    <div class="flex items-center min-w-0">
                        @php
                            $nameTokens = explode(' ', Auth::user()->name);
                            $profileInitials = strtoupper(substr($nameTokens[0], 0, 1) . (count($nameTokens) > 1 ? substr(end($nameTokens), 0, 1) : ''));
                        @endphp
                        <div
                            class="h-8 w-8 rounded-xl bg-[#383838] group-hover:bg-black flex items-center justify-center text-white text-[10px] font-black uppercase shadow-sm mr-2.5 flex-shrink-0 transition">
                            {{ $profileInitials }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-black text-gray-800 truncate leading-none">{{ Auth::user()->name }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-1 leading-none">
                                Professor</p>
                        </div>
                    </div>
                    <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
                        :class="open ? 'transform rotate-180' : ''"></i>
                </div>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full">
            <div class="p-8 mt-[80px]">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Class
                                Monitors</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1">
                                {{ $activeSessions->count() }}
                            </h3>
                        </div>
                        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-lg">
                            <i class="ri-terminal-box-line text-xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Courses
                                Assigned</p>
                            <h3 class="text-2xl font-black text-gray-800 mt-1">
                                {{ $sessions->count() }}
                            </h3>
                        </div>
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                            <i class="ri-folder-5-line text-xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Rostered
                                Students</p>
                            <h3 class="text-2xl font-black text-indigo-600 mt-1">
                                {{ $totalStudentsCount ?? 0 }}
                            </h3>
                        </div>
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i class="ri-user-shared-line text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div
                        class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="font-bold text-sm text-gray-800 uppercase tracking-wider">Student Activity
                                Densities</h2>
                            <span
                                class="text-[10px] font-bold bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-md uppercase">Session
                                Volume Logistics</span>
                        </div>
                        <div class="relative h-64 w-full">
                            <canvas id="studentEngagementChart"></canvas>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div class="mb-4">
                            <h2 class="font-bold text-sm text-gray-800 uppercase tracking-wider">Attendance Status
                                Matrix</h2>
                        </div>
                        <div class="relative h-64 w-full flex items-center justify-center">
                            <canvas id="facultyAttendanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <div class="col-span-1 lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between"
                        x-data="{ 
                            search: '', 
                            programFilter: '', 
                            dayFilter: '',
                            matchesFilter(code, program, day) {
                                const matchSearch = code.toLowerCase().includes(this.search.toLowerCase()) || program.toLowerCase().includes(this.search.toLowerCase());
                                const matchProgram = this.programFilter === '' || program.toUpperCase() === this.programFilter.toUpperCase();
                                const matchDay = this.dayFilter === '' || day.toLowerCase().includes(this.dayFilter.toLowerCase());
                                return matchSearch && matchProgram && matchDay;
                            }
                         }">
                        <div>
                            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                                <div>
                                    <h2 class="font-bold text-base text-gray-800 uppercase tracking-wide">Laboratory
                                        Schedules Directory</h2>
                                    <p class="text-xs text-gray-400 font-medium">Interactive pipeline of your assigned
                                        sections</p>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                <div class="relative">
                                    <i
                                        class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    <input type="text" x-model="search" placeholder="Search code or program..."
                                        class="w-full pl-9 pr-4 py-1.5 text-xs bg-white rounded-lg border border-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400 font-medium placeholder-gray-400" />
                                </div>
                                <div>
                                    <select x-model="programFilter"
                                        class="w-full py-1.5 px-3 text-xs bg-white rounded-lg border border-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 font-bold text-gray-600">
                                        <option value="">All Degree Programs</option>
                                        <option value="BSCS">BSCS</option>
                                        <option value="BSIT">BSIT</option>
                                    </select>
                                </div>
                                <div>
                                    <select x-model="dayFilter"
                                        class="w-full py-1.5 px-3 text-xs bg-white rounded-lg border border-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-400 font-bold text-gray-600">
                                        <option value="">All Days</option>
                                        <option value="Monday">Mondays</option>
                                        <option value="Tuesday">Tuesdays</option>
                                        <option value="Wednesday">Wednesdays</option>
                                        <option value="Thursday">Thursdays</option>
                                        <option value="Friday">Fridays</option>
                                        <option value="Saturday">Saturdays</option>
                                    </select>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr
                                            class="text-[11px] font-black text-gray-400 uppercase border-b border-gray-100 tracking-wider">
                                            <th class="pb-3 pl-1 w-1/3">Subject Name</th>
                                            <th class="pb-3 w-1/6">Class Code</th>
                                            <th class="pb-3 w-1/3">Day & Time</th>
                                            <th class="pb-3 pr-1 text-right w-1/6">Program & Year</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @forelse($sessions as $sessionItem)
                                            <tr x-show="matchesFilter('{{ $sessionItem->class_code }}', '{{ $sessionItem->program }}', '{{ $sessionItem->schedule_day }}')"
                                                x-transition:enter="transition ease-out duration-200"
                                                class="hover:bg-gray-50/60 transition-colors">

                                                <td class="py-3.5 pl-1 font-bold text-xs leading-snug">
                                                    <a href="{{ route('professor.classroom.show', $sessionItem->id) }}"
                                                        class="text-gray-800 hover:text-indigo-600 hover:underline transition duration-150 block">
                                                        {{ $sessionItem->subject_name }}
                                                    </a>
                                                </td>

                                                <td
                                                    class="py-3.5 text-xs font-mono font-bold text-gray-500 uppercase tracking-tight">
                                                    {{ $sessionItem->class_code }}
                                                </td>

                                                <td class="py-3.5 text-xs text-gray-600 font-medium">
                                                    <span
                                                        class="inline-flex items-center gap-1.5 bg-gray-100 px-2 py-0.5 rounded text-gray-700 font-bold text-[10px] uppercase">
                                                        <i
                                                            class="ri-calendar-event-line text-gray-400"></i>{{ $sessionItem->schedule_day }}
                                                    </span>
                                                    <span class="text-gray-400 mx-1">•</span>
                                                    <span
                                                        class="font-semibold text-gray-600">{{ $sessionItem->schedule_time }}</span>
                                                </td>

                                                <td class="py-3.5 pr-1 text-right text-xs font-black text-gray-700">
                                                    <span
                                                        class="bg-dark text-gray-800 border border-gray-200 px-2 py-0.5 rounded text-[10px] uppercase font-black tracking-wider">
                                                        {{ $sessionItem->program }} -
                                                        {{ $sessionItem->year_level }}{{ $sessionItem->section }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4"
                                                    class="text-center py-12 text-gray-400 italic text-xs font-medium bg-gray-50/30 rounded-xl">
                                                    No laboratory courses assigned.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div
                        class="col-span-1 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                            <div class="mb-4">
                                <h2 class="font-bold text-lg text-gray-800 uppercase">Student Monitoring Feed</h2>
                                <p class="text-xs text-gray-400 font-medium">Real-time behavior tracking telemetry</p>
                            </div>

                            <div class="space-y-4 overflow-y-auto max-h-[380px] pr-1.5 mt-4 custom-scrollbar">
                                @forelse($logs as $logItem)
                                    @php
                                        // Set specific theme colors based on the activity logging context
                                        $typeTheme = match ($logItem->log_type) {
                                            'attendance' => ['icon' => 'ri-checkbox-circle-line', 'box' => 'bg-emerald-50 text-emerald-600 border-emerald-100 text-emerald-700'],
                                            'submission' => ['icon' => 'ri-file-upload-line', 'box' => 'bg-blue-50 text-blue-600 border-blue-100 text-blue-700'],
                                            'material' => ['icon' => 'ri-book-open-line', 'box' => 'bg-purple-50 text-purple-600 border-purple-100 text-purple-700'],
                                            'quiz' => ['icon' => 'ri-task-line', 'box' => 'bg-indigo-50 text-indigo-600 border-indigo-100 text-indigo-700'],
                                            'violation' => ['icon' => 'ri-error-warning-line', 'box' => 'bg-red-50 text-red-600 border-red-100 text-red-700'],
                                            default => ['icon' => 'ri-global-line', 'box' => 'bg-amber-50 text-amber-600 border-amber-100 text-amber-700']
                                        };

                                        // Format student name securely 
                                        $displayStudentName = 'System Context';
                                        if ($logItem->user) {
                                            $middleInitial = $logItem->user->middle_name ? ' ' . strtoupper(substr($logItem->user->middle_name, 0, 1)) . '.' : '';
                                            $displayStudentName = strtoupper($logItem->user->last_name) . ', ' . $logItem->user->first_name . $middleInitial;
                                        }
                                    @endphp

                                    <div
                                        class="group p-3 rounded-xl border border-gray-100/70 hover:bg-gray-50/50 flex gap-3 transition">
                                        <div class="flex flex-col items-center flex-shrink-0">
                                            <div
                                                class="w-7 h-7 rounded-lg flex items-center justify-center {{ $typeTheme['box'] }} border font-bold text-xs shadow-sm">
                                                <i class="{{ $typeTheme['icon'] }} text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-2 mb-0.5">
                                                <p class="text-xs font-black text-gray-800 truncate">
                                                    {{ $displayStudentName }}</p>
                                                <span
                                                    class="text-[9px] font-bold text-gray-400 whitespace-nowrap uppercase tracking-tight">
                                                    {{ $logItem->created_at ? $logItem->created_at->diffForHumans() : 'Just now' }}
                                                </span>
                                            </div>
                                            <p class="text-xs font-medium text-gray-600 leading-snug break-words">
                                                {{ $logItem->content ?? 'Interacted with laboratory workspace' }}
                                            </p>
                                            <div
                                                class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-2 pt-1.5 border-t border-gray-100 text-[10px] font-bold text-gray-400">
                                                <span
                                                    class="inline-flex items-center gap-1 text-gray-500 bg-gray-50 border border-gray-200 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-wide">
                                                    <i class="ri-terminal-box-line flex-shrink-0 text-[11px]"></i>
                                                    {{ $logItem->labSession?->class_code ?? 'General' }} ( {{ $logItem->labSession?->program }} - {{ $logItem->labSession?->year_level }}{{ $logItem->labSession?->section}})
                                                </span>
                                                <span class="text-gray-300">•</span>
                                                <span class="font-mono text-gray-400">
                                                    {{ $logItem->created_at ? $logItem->created_at->format('M d, Y • h:i A') : now()->format('M d, Y • h:i A') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="text-center py-12 text-gray-400 text-xs font-bold uppercase tracking-wider italic bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                                        <i class="ri-history-line text-xl block mb-1 text-gray-300"></i> No activity logs
                                        recorded yet
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctxEngagement = document.getElementById('studentEngagementChart').getContext('2d');
            new Chart(ctxEngagement, {
                type: 'bar',
                data: {
                    labels: ['Navigation Logs', 'Quiz Interactions', 'Code Submissions'],
                    datasets: [{
                        label: 'Logs Collected',
                        // 🟢 Driven dynamically from backend aggregates
                        data: [
                            {{ $navigationLogsCount ?? 0 }},
                            {{ $quizLogsCount ?? 0 }},
                            {{ $submissionLogsCount ?? 0 }}
                        ],
                        backgroundColor: ['rgba(99, 102, 241, 0.85)', 'rgba(245, 158, 11, 0.85)', 'rgba(59, 130, 246, 0.85)'],
                        borderColor: ['#4f46e5', '#f59e0b', '#3b82f6'],
                        borderWidth: 1.5,
                        borderRadius: 8,
                        barThickness: 26
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { display: false }, ticks: { font: { weight: 'bold', size: 11 } } },
                        x: { grid: { color: '#f3f4f6' } }
                    }
                }
            });

            const ctxAttendance = document.getElementById('facultyAttendanceChart').getContext('2d');
            new Chart(ctxAttendance, {
                type: 'doughnut',
                data: {
                    labels: ['Present Today', 'Absent / Inactive'],
                    datasets: [{
                        // 🟢 Driven dynamically from pivot rows
                        data: [
                            {{ $presentCount ?? 0 }},
                            {{ $absentCount ?? 0 }}
                        ],
                        backgroundColor: ['#22c55e', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 14, font: { size: 10, weight: 'bold' }, usePointStyle: true }
                        }
                    },
                    cutout: '72%'
                }
            });
        });
    </script>
</x-app-layout>