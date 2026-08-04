<x-app-layout>
    <div class="fixed inset-0 flex bg-gray-100"
        x-data="{ showModal: false, isActive: {{ $session->is_active ? 'true' : 'false' }} }">

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

                    @php
                        // Safe lookup: Use $sessions if available, fallback to $activeSessions from the dashboard
                        $sidebarCourses = $sessions ?? $activeSessions ?? \App\Models\LabSession::where('faculty_id', auth()->id())->latest()->get();
                    @endphp

                    @forelse($sidebarCourses as $sideSession)
                        @php
                            // Check once if this specific route is active to keep code clean
                            $isCurrentClass = request()->is('professor/classroom/' . $sideSession->id);
                        @endphp

                        <a href="{{ route('professor.classroom.show', $sideSession->id) }}"
                            class="flex items-start py-3 px-4 rounded-xl text-xs transition duration-150 group {{ $isCurrentClass ? 'bg-[#383838] text-white font-black shadow-sm' : 'text-gray-600 hover:bg-gray-50' }}">

                            <i
                                class="ri-book-3-line text-lg mr-3 flex-shrink-0 mt-0.5 {{ $isCurrentClass ? 'text-white' : 'text-gray-400 group-hover:text-gray-900' }} transition"></i>

                            <div class="flex flex-col min-w-0">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span
                                        class="truncate font-black text-xs tracking-tight uppercase {{ $isCurrentClass ? 'text-white' : 'text-gray-800 group-hover:text-black' }}">
                                        {{ $sideSession->class_code }} | {{ $sideSession->program }} -
                                        {{ $sideSession->year_level }}{{ $sideSession->section }}
                                    </span>
                                    @if($sideSession->is_active)
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse flex-shrink-0"></span>
                                    @endif
                                </div>
                                <span
                                    class="text-[10px] font-bold truncate mt-0.5 tracking-wide {{ $isCurrentClass ? 'text-gray-300' : 'text-gray-400 group-hover:text-gray-500' }}">
                                    {{ $sideSession->schedule_day }} • {{ $sideSession->schedule_time }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider italic">No laboratory
                                sessions</p>
                        </div>
                    @endforelse
                </nav>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50/50 relative" x-data="{ open: false }"
                @click.away="open = false">

                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="transform opacity-0 scale-95 translate-y-2"
                    class="absolute bottom-full left-4 right-4 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl p-1.5 z-50 flex flex-col gap-0.5"
                    style="display: none;">

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                            class="w-full text-left flex items-center px-3.5 py-2.5 text-xs font-black text-red-600 hover:bg-red-50 rounded-xl transition duration-150 tracking-wide">
                            <i class="ri-logout-box-r-line mr-2.5 text-base"></i> Sign Out
                        </button>
                    </form>
                </div>

                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group p-1 -m-1 rounded-xl hover:bg-gray-100/50 transition">
                    <div class="flex items-center min-w-0">
                        @php
                            $nameTokens = explode(' ', Auth::user()->name);
                            $firstInitial = substr($nameTokens[0], 0, 1);
                            $lastInitial = count($nameTokens) > 1 ? substr(end($nameTokens), 0, 1) : '';
                            $profileInitials = strtoupper($firstInitial . $lastInitial);
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
                        :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
                </div>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full">
            <div class="p-6 mt-[80px]">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div
                        class="md:col-span-2 bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6 flex flex-col justify-between">
                        <div>
                            <h1 class="text-4xl font-black text-gray-900 mb-3">{{ $session->subject_name }} |
                                {{ $session->program }} - {{ $session->year_level }}{{ $session->section }}
                            </h1>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-4">
                            <span
                                class="inline-flex items-center px-4 py-1.5 rounded-xl text-xs font-bold bg-gray-50 border border-gray-100 text-gray-600 uppercase tracking-wider">
                                <i class="ri-calendar-line mr-2 text-gray-400"></i> {{ $session->schedule_day }}
                            </span>

                            <span
                                class="inline-flex items-center px-4 py-1.5 rounded-xl text-xs font-bold bg-gray-50 border border-gray-100 text-gray-600 uppercase tracking-wider">
                                <i class="ri-time-line mr-2 text-gray-400"></i> {{ $session->schedule_time }}
                            </span>
                            <span
                                class="inline-flex items-center px-4 py-1.5 rounded-xl text-xs font-bold bg-gray-50 border border-gray-100 text-gray-600 uppercase tracking-wider">
                                <i class="ri-code-s-line mr-2 text-gray-400"></i> {{ $session->class_code }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6 flex flex-col justify-center min-h-[160px]">

                        <div id="broadcast-wrapper" class="w-full flex flex-col items-center justify-center text-center"
                            x-data="{ 
             isActive: {{ $class->is_active ? 'true' : 'false' }},
             isBroadcasting: {{ $class->is_broadcasting ? 'true' : 'false' }} 
         }">

                            <div class="flex items-center gap-2 mb-4 justify-center">
                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Session
                                    Control</span>
                                <span
                                    class="inline-flex items-center text-[10px] font-black tracking-wide px-2 py-0.5 rounded-full"
                                    :class="isActive ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    <span class="h-1.5 w-1.5 rounded-full mr-1.5"
                                        :class="isActive ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"></span>
                                    <span x-text="isActive ? 'LIVE ACTIVE' : 'CLOSED'"></span>
                                </span>
                            </div>

                            <div class="flex items-center justify-center gap-4 w-full">

                                <button @click.prevent="toggleSession()" x-show="!isActive" title="Start Lab Session"
                                    class="w-16 h-16 rounded-full flex items-center justify-center text-white bg-green-600 hover:bg-green-700 shadow-lg shadow-green-100 transition-all duration-200 transform hover:scale-105 active:scale-95 text-2xl pl-1">
                                    <i class="ri-play-fill"></i>
                                </button>

                                <button @click.prevent="toggleSession()" x-show="isActive" style="display: none;"
                                    title="Stop Lab Session"
                                    class="w-16 h-16 rounded-full flex items-center justify-center text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-100 transition-all duration-200 transform hover:scale-105 active:scale-95 text-2xl">
                                    <i class="ri-stop-fill"></i>
                                </button>

                                <div class="h-10 w-[1px] bg-gray-200 mx-1" x-show="isActive" style="display: none;">
                                </div>

                                <div class="flex items-center gap-3" x-show="isActive" style="display: none;">

                                    <button type="button" x-show="!isBroadcasting" title="Share Screen Broadcast"
                                        @click.prevent="toggleBroadcast()"
                                        class="w-12 h-12 rounded-full flex items-center justify-center text-gray-600 bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 transform hover:scale-105 text-xl">
                                        <i class="ri-computer-line"></i>
                                    </button>

                                    <button type="button" x-show="isBroadcasting" style="display: none;"
                                        title="Stop Screen Broadcast" @click.prevent="toggleBroadcast()"
                                        class="w-12 h-12 rounded-full flex items-center justify-center text-white bg-orange-500 hover:bg-orange-600 shadow-md shadow-orange-100 transition-all duration-200 transform hover:scale-105 text-xl">
                                        <i class="ri-broadcast-line animate-pulse"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="mt-8" x-data="{ activeTab: 'monitoring' }">
                    <div class="flex space-x-1 border-b border-gray-200">
                        <button @click="activeTab = 'monitoring'"
                            :class="activeTab === 'monitoring' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Monitoring</button>
                        <button @click="activeTab = 'materials'"
                            :class="activeTab === 'materials' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Materials</button>
                        <button @click="activeTab = 'tasks'"
                            :class="activeTab === 'tasks' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Tasks</button>
                        <button @click="activeTab = 'quizzes'"
                            :class="activeTab === 'quizzes' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Quizzes</button>
                        <button @click="activeTab = 'students'"
                            :class="activeTab === 'students' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Students</button>
                        <button @click="activeTab = 'browser-security'"
                            :class="activeTab === 'browser-security' ? 'border-b-2 border-black text-black' : 'text-gray-500'"
                            class="px-6 py-3 transition">Security</button>
                    </div>

                    <div class="mt-6">
                        <div x-show="activeTab === 'monitoring'" x-data="{
        searchQuery: '',
        statusFilter: 'all',
        gridCols: 'lg:grid-cols-4 md:grid-cols-3 grid-cols-2',
        perPage: 12,
        currentPage: 1,
        
        // Evaluates visibility without breaking PeerJS bindings
        shouldShow(studentId, isPresent, nameSearch) {
            // 1. Search filter
            if (this.searchQuery && !nameSearch.toLowerCase().includes(this.searchQuery.toLowerCase())) {
                return false;
            }
            // 2. Status filter
            if (this.statusFilter === 'active' && !isPresent) return false;
            if (this.statusFilter === 'offline' && isPresent) return false;
            
            return true;
        }
     }">

                            @if(isset($class) && $class->is_active)
                                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex flex-col">
                                            <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">
                                                LIVE MONITORING
                                            </h2>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">
                                                Manage student screens
                                            </p>
                                        </div>

                                        <span
                                            class="inline-flex items-center bg-blue-50 text-blue-600 border border-blue-100 text-[10px] font-black px-2.5 py-1 rounded-xl uppercase tracking-wider shadow-sm">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-1.5 animate-pulse"></span>
                                            Connected: <span id="connected-counter"
                                                class="ml-0.5 font-black">0</span>/{{ count($activeStudents) }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-3 bg-gray-50 p-2 rounded-2xl border border-gray-200">
                                        <div class="relative">
                                            <i class="ri-search-line absolute left-3 top-2.5 text-gray-400 text-sm"></i>
                                            <input x-model="searchQuery" type="text" placeholder="Search student..."
                                                class="pl-9 pr-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-black w-44">
                                        </div>

                                        <select x-model="statusFilter"
                                            class="bg-white border border-gray-200 rounded-xl px-2 py-1.5 text-xs font-bold text-gray-700 focus:outline-none">
                                            <option value="all">All Statuses</option>
                                            <option value="active">🟢 Active Only</option>
                                            <option value="offline">⚪ Offline Only</option>
                                        </select>

                                        <div class="flex items-center border-l border-gray-200 pl-2 gap-1">
                                            <button @click="gridCols = 'lg:grid-cols-2 md:grid-cols-2 grid-cols-1'"
                                                :class="gridCols.includes('lg:grid-cols-2') ? 'bg-white shadow-sm text-black border border-gray-200' : 'text-gray-400'"
                                                class="p-1.5 rounded-lg text-sm transition" title="Focus Mode (Large)">
                                                <i class="ri-layout-grid-line"></i>
                                            </button>
                                            <button @click="gridCols = 'lg:grid-cols-4 md:grid-cols-3 grid-cols-2'"
                                                :class="gridCols.includes('lg:grid-cols-4') ? 'bg-white shadow-sm text-black border border-gray-200' : 'text-gray-400'"
                                                class="p-1.5 rounded-lg text-sm transition" title="Standard View">
                                                <i class="ri-grid-fill"></i>
                                            </button>
                                            <button @click="gridCols = 'lg:grid-cols-6 md:grid-cols-4 grid-cols-3'"
                                                :class="gridCols.includes('lg:grid-cols-6') ? 'bg-white shadow-sm text-black border border-gray-200' : 'text-gray-400'"
                                                class="p-1.5 rounded-lg text-sm transition" title="Compact View (Small)">
                                                <i class="ri-apps-2-line"></i>
                                            </button>
                                        </div>

                                        <button onclick="openMonitoringWall()"
                                            class="flex items-center gap-1.5 pl-3 pr-3.5 py-1.5 ml-1 rounded-xl text-xs font-black bg-[#383838] text-white hover:bg-black transition shadow-sm uppercase tracking-wide"
                                            title="Open fullscreen monitoring wall">
                                            <i class="ri-fullscreen-line"></i>
                                            <span class="hidden sm:inline">Wall View</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 px-4 items-start">

                                    <div
                                        class="lg:col-span-3 h-[calc(100vh-250px)] overflow-y-auto pr-2 custom-scrollbar pb-10">
                                        <div class="grid gap-4 transition-all duration-300" :class="gridCols"
                                            id="student-grid">
                                            @forelse($activeStudents as $student)
                                                @php
                                                    $fullNameFormat = strtoupper($student->last_name) . ', ' . $student->first_name . ($student->middle_name ? ' ' . strtoupper(substr($student->middle_name, 0, 1)) . '.' : '');
                                                    $isPresent = ($student->pivot && $student->pivot->is_present) ? 'true' : 'false';
                                                @endphp

                                                <div x-show="shouldShow({{ $student->id }}, {{ $isPresent }}, '{{ addslashes($fullNameFormat) }}')"
                                                    class="border bg-white rounded-[24px] p-4 transition-all duration-300 shadow-sm hover:shadow-md flex flex-col justify-between"
                                                    id="student-card-{{ $student->id }}" data-student-id="{{ $student->id }}">

                                                    <div class="flex items-center justify-between mb-3">
                                                        <span class="font-bold text-xs text-gray-800 truncate pr-2"
                                                            title="{{ $fullNameFormat }}">
                                                            {{ $fullNameFormat }}
                                                        </span>
                                                        <div class="w-2.5 h-2.5 rounded-full bg-gray-300"
                                                            id="status-dot-{{ $student->id }}"></div>
                                                    </div>

                                                    <div class="bg-gray-100 aspect-video rounded-xl flex items-center justify-center mb-4 relative overflow-hidden"
                                                        id="video-container-{{ $student->id }}">
                                                        <video id="video-{{ $student->id }}"
                                                            class="w-full h-full object-cover hidden z-10" muted
                                                            playsinline></video>
                                                        <span
                                                            class="text-[11px] font-bold text-gray-400 tracking-wide uppercase absolute text-center z-0"
                                                            id="video-overlay-{{ $student->id }}">
                                                            Offline
                                                        </span>
                                                    </div>

                                                    <div class="flex" id="btn-container-{{ $student->id }}">
                                                        <button id="btn-{{ $student->id }}" disabled
                                                            onclick="openFullscreenViewer('{{ $student->id }}', '{{ addslashes($fullNameFormat) }}')"
                                                            class="w-full text-[11px] bg-gray-100 text-gray-400 py-2 rounded-xl font-bold cursor-not-allowed tracking-wide uppercase transition shadow-sm">
                                                            Waiting...
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-span-full py-20 text-center">
                                                    <p class="text-gray-400 italic">No students are currently active in this
                                                        session.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="lg:col-span-1" x-data="{
                                                                                logs: [],
                                                                                loading: false,
                                                                                refreshInterval: null,
                                                                                fetchSessionLogs() {
                                                                                    this.loading = true;
                                                                                    fetch('/professor/classroom/{{ $class->id }}/activity-logs')
                                                                                        .then(res => res.json())
                                                                                        .then(data => { this.logs = data; this.loading = false; })
                                                                                        .catch(err => { console.error('Failed to sync live logs:', err); this.loading = false; });
                                                                                },
                                                                                init() {
                                                                                    this.fetchSessionLogs();
                                                                                    this.refreshInterval = setInterval(() => this.fetchSessionLogs(), 3000);
                                                                                },
                                                                                destroy() {
                                                                                    if (this.refreshInterval) clearInterval(this.refreshInterval);
                                                                                },
                                                                                getIcon(type, content = '') {
                                                                                    const icons = {
                                                                                        'attendance': 'ri-checkbox-circle-line',
                                                                                        'navigation': 'ri-global-line',
                                                                                        'submission': 'ri-file-upload-line',
                                                                                        'material': 'ri-book-open-line',
                                                                                        'quiz': 'ri-task-line',
                                                                                        'professor_session': 'ri-broadcast-line',
                                                                                        'screen_share': 'ri-projector-2-line'
                                                                                    };

                                                                                    if (type === 'professor_activity') {
                                                                                        if (content.includes('Posted')) return 'ri-add-circle-line';
                                                                                        if (content.includes('Updated') || content.includes('Edited')) return 'ri-edit-circle-line';
                                                                                        if (content.includes('Deleted')) return 'ri-delete-bin-line';
                                                                                        return 'ri-briefcase-line';
                                                                                    }
                                                                                    return icons[type] || 'ri-cursor-line';
                                                                                },
                                                                                getIconClass(type, content = '') {
                                                                                    const classes = {
                                                                                        'attendance': 'bg-green-50 text-green-600 border border-green-200',
                                                                                        'navigation': 'bg-amber-50 text-amber-600 border border-amber-200',
                                                                                        'submission': 'bg-blue-50 text-blue-600 border border-blue-200',
                                                                                        'material': 'bg-purple-50 text-purple-600 border border-purple-200',
                                                                                        'quiz': 'bg-indigo-50 text-indigo-600 border border-indigo-200',
                                                                                        'professor_session': 'bg-red-50 text-red-600 border border-red-200',
                                                                                        'screen_share': 'bg-orange-50 text-orange-600 border border-orange-200'
                                                                                    };

                                                                                    if (type === 'professor_activity') {
                                                                                        if (content.includes('Posted')) return 'bg-green-50 text-green-600 border border-green-200';
                                                                                        if (content.includes('Updated') || content.includes('Edited')) return 'bg-blue-50 text-blue-600 border border-blue-200';
                                                                                        if (content.includes('Deleted')) return 'bg-red-50 text-red-600 border border-red-200';
                                                                                        return 'bg-cyan-50 text-cyan-600 border border-cyan-200';
                                                                                    }
                                                                                    return classes[type] || 'bg-gray-100 text-gray-600 border-gray-200';
                                                                                },
                                                                                formatTime(dateStr) {
                                                                                    return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                                                                                }
                                                                            }">
                                        <div
                                            class="bg-white border border-gray-200 shadow-sm rounded-[24px] p-5 flex flex-col h-[calc(100vh-250px)]">
                                            <div
                                                class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                                                <div>
                                                    <h3 class="font-black text-xs text-gray-800 uppercase tracking-wider">
                                                        Activity Stream</h3>
                                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wide">
                                                        Real-time session logs</p>
                                                </div>
                                                <button @click="fetchSessionLogs()"
                                                    class="text-gray-400 hover:text-black transition">
                                                    <i class="ri-refresh-line text-lg"
                                                        :class="loading ? 'animate-spin block' : ''"></i>
                                                </button>
                                            </div>

                                            <div class="flex-grow overflow-y-auto pr-1 space-y-3.5 custom-scrollbar">
                                                <template x-if="loading && logs.length === 0">
                                                    <div
                                                        class="text-center py-8 text-gray-400 text-xs font-bold uppercase tracking-widest animate-pulse">
                                                        Syncing logs...</div>
                                                </template>
                                                <template x-if="!loading && logs.length === 0">
                                                    <div
                                                        class="text-center py-12 text-gray-400 text-xs font-bold uppercase tracking-wider italic">
                                                        No activity recorded yet</div>
                                                </template>

                                                <template x-for="log in logs" :key="log.id">
                                                    <div class="flex gap-3 items-start relative group">
                                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm text-sm"
                                                            :class="getIconClass(log.log_type, log.content)">
                                                            <i :class="getIcon(log.log_type, log.content)"></i>
                                                        </div>
                                                        <div
                                                            class="flex-grow min-w-0 bg-gray-50/50 hover:bg-gray-50 border border-gray-100/80 rounded-xl p-2.5 transition">
                                                            <div class="flex justify-between items-start gap-1">
                                                                <span class="text-[11px] font-black text-gray-800 truncate"
                                                                    x-text="log.student_name.trim().includes(' ') ? log.student_name.trim().split(' ').pop() + ', ' + log.student_name.trim().split(' ').slice(0, -1).join(' ') : log.student_name">
                                                                </span>
                                                                <span
                                                                    class="text-[9px] font-bold text-gray-400 whitespace-nowrap"
                                                                    x-text="formatTime(log.created_at)"></span>
                                                            </div>
                                                            <p class="text-[11px] text-gray-600 font-bold mt-0.5 leading-tight"
                                                                x-text="log.content"></p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif(isset($class) && !$class->is_active)
                                <div
                                    class="col-span-full py-20 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 mx-4">
                                    <div
                                        class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="ri-shield-user-line text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-gray-600 font-bold">Proctoring Paused</h3>
                                    <p class="text-gray-400 text-sm">Monitoring is disabled. Click "Start Lab Session" to
                                        begin.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Fullscreen Modal Viewer (Kept outside main layout wrapper for z-index protection) --}}
                        <template x-teleport="body">
                            <div x-data="{ open: false, studentName: '' }"
                                @open-modal.window="open = true; studentName = $event.detail.name" x-show="open"
                                class="fixed inset-0  z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
                                x-cloak>
                                <div class="relative w-full max-w-7xl bg-white rounded-2xl overflow-hidden shadow-2xl">
                                    <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                                        <h3 class="font-bold text-gray-900 flex items-center">
                                            <span class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                                            Monitoring: <span x-text="studentName" class="ml-1"></span>
                                        </h3>
                                        <button
                                            @click="open = false; document.getElementById('modal-video').srcObject = null"
                                            class="text-gray-400 hover:text-gray-600 transition">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="aspect-video bg-black flex items-center justify-center">
                                        <video id="modal-video" autoplay playsinline
                                            class="w-full h-full object-contain"></video>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Fullscreen Monitoring Wall (all connected student screens, auto-adjusting grid) --}}
                        <template x-teleport="body">
                            <div id="monitoring-wall" class="fixed inset-0 z-[9998] bg-[#0a0a0a] hidden flex-col">

                                <div
                                    class="flex items-center justify-between px-6 py-4 bg-[#111111] border-b border-white/10 flex-shrink-0">
                                    <div class="flex items-center gap-3">
                                        <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></span>
                                        <h3 class="text-white font-black text-sm uppercase tracking-widest">
                                            Live Monitoring Wall
                                        </h3>
                                        <span id="wall-counter-label"
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-white/5 px-2.5 py-1 rounded-lg">
                                            0 connected
                                        </span>
                                    </div>
                                    <button onclick="closeMonitoringWall()"
                                        class="flex items-center gap-1.5 text-gray-300 hover:text-white text-xs font-bold uppercase tracking-wide transition px-3 py-1.5 rounded-lg hover:bg-white/10">
                                        <i class="ri-close-line text-xl"></i>
                                        <span class="hidden sm:inline">Exit (Esc)</span>
                                    </button>
                                </div>

                                <div class="flex-1 overflow-auto p-4">
                                    <div id="wall-grid" class="grid gap-3 w-full h-full"></div>

                                    <div id="wall-empty-state"
                                        class="hidden h-full flex-col items-center justify-center text-center">
                                        <i class="ri-tv-2-line text-5xl text-gray-700 mb-3"></i>
                                        <p class="text-gray-500 font-bold text-xs uppercase tracking-widest">
                                            No students connected yet
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="activeTab === 'materials'" x-cloak class="animate-fade-in" x-data="{
        // Central Modal Variables Control Blocks
        previewOpen: false,
        previewTitle: '',
        previewType: '',
        previewUrl: '',

        editOpen: false,
        editId: '',
        editTitle: '',
        editType: '',
        editContentUrl: '',
        editActionUrl: '',

        viewersOpen: false,
        viewersTitle: '',
        viewersList: [],
        loadingViewers: false,

        // Fetch viewer metrics using Query Builder API endpoint target
        fetchViewers(materialId, materialTitle) {
            this.viewersTitle = materialTitle;
            this.viewersOpen = true;
            this.loadingViewers = true;
            this.viewersList = [];
            
            fetch(`/professor/materials/${materialId}/viewers`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                this.viewersList = data;
                this.loadingViewers = false;
            })
            .catch(err => {
                console.error('Data pipeline exception:', err);
                this.loadingViewers = false;
            });
        },

        // Clean layout timing formats
        formatDuration(seconds) {
            if (!seconds) return '0s';
            if (seconds < 60) return seconds + 's';
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`;
        }
     }">

                            <div class="flex justify-between items-center mb-8 ms-4 me-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">LEARNING
                                        MATERIALS MANAGEMENT</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Manage
                                        academic resources</p>
                                </div>
                                <button @click="$dispatch('open-material-modal')"
                                    class="bg-[#383838] text-white px-8 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200 active:scale-95">
                                    + Post Material
                                </button>
                            </div>

                            <div id="materials-list-container"
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                @forelse($class->materials as $material)
                                    @php
                                        // Build dynamic web asset resolution matching controller's upload locations
                                        $cleanUrl = $material->type === 'youtube' ? $material->content : url('/' . $material->content);
                                    @endphp
                                    <div
                                        class="bg-white p-6 rounded-[32px] border border-gray-100 flex flex-col justify-between group hover:border-black transition-all duration-300 shadow-sm hover:shadow-xl hover:shadow-gray-100">
                                        <div>
                                            <div class="flex items-center justify-between mb-4">
                                                <div
                                                    class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors duration-300">
                                                    @if($material->type == 'pdf')
                                                        <i class="ri-file-pdf-line text-xl"></i>
                                                    @elseif($material->type == 'youtube')
                                                        <i class="ri-youtube-line text-xl"></i>
                                                    @else
                                                        <i class="ri-presentation-line text-xl"></i>
                                                    @endif
                                                </div>
                                                <span
                                                    class="text-[9px] font-black text-gray-300 uppercase tracking-widest group-hover:text-gray-400 transition-colors">
                                                    {{ $material->type }}
                                                </span>
                                            </div>

                                            <h4
                                                class="font-black text-[#383838] text-lg leading-tight tracking-tight mb-2 group-hover:text-black">
                                                {{ $material->title }}
                                            </h4>
                                        </div>

                                        <div class="mt-6 pt-4 border-t border-gray-50 flex flex-col space-y-3">
                                            <button
                                                @click="fetchViewers({{ $material->id }}, '{{ addslashes($material->title) }}')"
                                                class="w-full bg-gray-50 hover:bg-black hover:text-white text-[#383838] py-2.5 px-4 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center transition-all duration-200">
                                                <i class="ri-group-line mr-2 text-sm"></i> View Logs & Duration
                                            </button>

                                            <div class="flex justify-between items-center">
                                                <button
                                                    @click="previewOpen = true; previewTitle = '{{ addslashes($material->title) }}'; previewType = '{{ $material->type }}'; previewUrl = '{{ $cleanUrl }}'"
                                                    class="text-[10px] font-black uppercase text-gray-400 hover:text-black tracking-widest transition-colors flex items-center">
                                                    <i class="ri-eye-line mr-1 text-sm"></i> Preview Content
                                                </button>

                                                <div class="flex items-center space-x-3">
                                                    <button
                                                        @click="editOpen = true; editId = '{{ $material->id }}'; editTitle = '{{ addslashes($material->title) }}'; editType = '{{ $material->type }}'; editContentUrl = '{{ $material->type === 'youtube' ? $material->content : '' }}'; editActionUrl = '/professor/materials/' + {{ $material->id }}"
                                                        class="text-gray-300 hover:text-blue-500 transition-colors">
                                                        <i class="ri-edit-line text-base"></i>
                                                    </button>

                                                    <form action="/professor/materials/{{ $material->id }}" method="POST"
                                                        @submit.prevent="if(confirm('Are you sure you want to completely erase this file?')) submitAjaxForm($event)">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="text-gray-300 hover:text-red-500 transition-colors pt-1">
                                                            <i class="ri-delete-bin-line text-base"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full py-20 bg-white border-2 border-dashed border-gray-100 rounded-[32px] text-center">
                                        <i class="ri-folder-open-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 font-bold text-sm">No learning materials have been
                                            posted
                                            yet.</p>
                                    </div>
                                @endforelse
                            </div>

                            <template x-teleport="body">
                                <div>
                                    <div x-data="{ open: false, type: 'pdf' }" @open-material-modal.window="open = true"
                                        x-show="open"
                                        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                        x-transition:leave="transition ease-in duration-200"
                                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                        x-cloak>

                                        <div @click.away="open = false"
                                            class="bg-white w-full max-w-lg rounded-[40px] shadow-2xl overflow-hidden animate-modal-in">
                                            <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                                                <div>
                                                    <h3
                                                        class="text-2xl font-black text-[#383838] tracking-tighter uppercase">
                                                        Upload Content</h3>
                                                    <p
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">
                                                        Add materials to student view</p>
                                                </div>
                                                <button @click="open = false"
                                                    class="w-10 h-10 flex items-center justify-center bg-gray-50 rounded-full hover:bg-black hover:text-white transition-all">
                                                    <i class="ri-close-line text-xl"></i>
                                                </button>
                                            </div>

                                            <form action="{{ route('professor.materials.store', $session->id) }}"
                                                method="POST" enctype="multipart/form-data" class="p-8 space-y-6"
                                                @submit.prevent="submitAjaxForm($event, () => open = false)">
                                                @csrf
                                                <div class="space-y-2">
                                                    <label
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Title</label>
                                                    <input type="text" name="title" required
                                                        placeholder="e.g., Introduction to Neural Networks"
                                                        class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-black outline-none transition-all font-bold text-[#383838]">
                                                </div>

                                                <div class="space-y-2">
                                                    <label
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Content
                                                        Category</label>
                                                    <select name="type" x-model="type"
                                                        class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl outline-none cursor-pointer focus:ring-2 focus:ring-black font-bold text-[#383838]">
                                                        <option value="pdf">PDF Document</option>
                                                        <option value="pptx">PowerPoint Presentation</option>
                                                        <option value="youtube">YouTube Video</option>
                                                    </select>
                                                </div>

                                                <div
                                                    class="p-6 bg-gray-50 rounded-[24px] border-2 border-dashed border-gray-200">
                                                    <template x-if="type === 'youtube'">
                                                        <div class="space-y-2">
                                                            <label
                                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Video
                                                                URL</label>
                                                            <input type="url" name="content_url" required
                                                                placeholder="https://www.youtube.com/watch?v=..."
                                                                class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl outline-none font-medium">
                                                        </div>
                                                    </template>
                                                    <template x-if="type === 'pdf' || type === 'pptx'">
                                                        <div class="space-y-2 text-center">
                                                            <label
                                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-4">Select
                                                                Source File</label>
                                                            <input type="file" name="content_file" required
                                                                :accept="type === 'pdf' ? '.pdf' : '.ppt,.pptx'"
                                                                class="block w-full text-xs text-gray-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-black file:text-white hover:file:bg-gray-800 transition-all">
                                                        </div>
                                                    </template>
                                                </div>

                                                <button type="submit"
                                                    class="w-full bg-[#383838] text-white py-5 rounded-[24px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200">
                                                    Publish to Classroom
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div x-show="editOpen"
                                        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
                                        x-cloak>
                                        <div @click.away="editOpen = false"
                                            class="bg-white w-full max-w-lg rounded-[40px] shadow-2xl overflow-hidden">
                                            <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                                                <div>
                                                    <h3
                                                        class="text-2xl font-black text-[#383838] tracking-tighter uppercase">
                                                        Edit Resource</h3>
                                                    <p
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">
                                                        Modify learning material entries</p>
                                                </div>
                                                <button @click="editOpen = false"
                                                    class="w-10 h-10 flex items-center justify-center bg-gray-50 rounded-full hover:bg-black hover:text-white">
                                                    <i class="ri-close-line text-xl"></i>
                                                </button>
                                            </div>

                                            <form :action="editActionUrl" method="POST" enctype="multipart/form-data"
                                                class="p-8 space-y-6"
                                                @submit.prevent="submitAjaxForm($event, () => editOpen = false)">
                                                @csrf
                                                @method('PUT')

                                                <div class="space-y-2">
                                                    <label
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Title</label>
                                                    <input type="text" name="title" x-model="editTitle" required
                                                        class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-black outline-none font-bold text-[#383838]">
                                                </div>

                                                <div
                                                    class="p-6 bg-gray-50 rounded-[24px] border-2 border-dashed border-gray-200">
                                                    <template x-if="editType === 'youtube'">
                                                        <div class="space-y-2">
                                                            <label
                                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Update
                                                                Video URL</label>
                                                            <input type="url" name="content_url"
                                                                x-model="editContentUrl" required
                                                                class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl outline-none">
                                                        </div>
                                                    </template>
                                                    <template x-if="editType === 'pdf' || editType === 'pptx'">
                                                        <div class="space-y-2 text-center">
                                                            <label
                                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Replace
                                                                File Asset (Optional)</label>
                                                            <p
                                                                class="text-[10px] text-amber-500 font-bold mb-3 uppercase tracking-wider">
                                                                Leave blank to retain original resource</p>
                                                            <input type="file" name="content_file"
                                                                :accept="editType === 'pdf' ? '.pdf' : '.ppt,.pptx'"
                                                                class="block w-full text-xs text-gray-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-black file:text-white hover:file:bg-gray-800">
                                                        </div>
                                                    </template>
                                                </div>

                                                <button type="submit"
                                                    class="w-full bg-[#383838] text-white py-5 rounded-[24px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">
                                                    Save Resource Changes
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div x-show="previewOpen"
                                        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-md p-4"
                                        x-cloak>
                                        <div
                                            class="bg-white w-full max-w-5xl h-[85vh] rounded-[40px] shadow-2xl flex flex-col overflow-hidden">
                                            <div
                                                class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                                <div>
                                                    <span
                                                        class="text-[9px] font-black px-2.5 py-1 bg-black text-white rounded-md uppercase tracking-widest mb-1 inline-block"
                                                        x-text="previewType"></span>
                                                    <h3 class="text-xl font-black text-gray-900 tracking-tight"
                                                        x-text="previewTitle"></h3>
                                                </div>
                                                <button @click="previewOpen = false; previewUrl = ''"
                                                    class="w-10 h-10 flex items-center justify-center bg-white rounded-full hover:bg-black hover:text-white shadow-sm border transition-all">
                                                    <i class="ri-close-line text-xl"></i>
                                                </button>
                                            </div>

                                            <div
                                                class="flex-grow bg-gray-100 flex items-center justify-center relative">
                                                <template x-if="previewOpen && previewType === 'youtube'">
                                                    <iframe class="w-full h-full"
                                                        :src="previewUrl.replace('watch?v=', 'embed/')" frameborder="0"
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                        allowfullscreen></iframe>
                                                </template>

                                                <template x-if="previewOpen && previewType === 'pdf'">
                                                    <iframe class="w-full h-full" :src="previewUrl"
                                                        frameborder="0"></iframe>
                                                </template>

                                                <template x-if="previewOpen && previewType === 'pptx'">
                                                    <div
                                                        class="text-center p-8 bg-white max-w-md rounded-2xl border border-gray-100 shadow-sm">
                                                        <i
                                                            class="ri-presentation-line text-6xl text-gray-300 mb-4 block"></i>
                                                        <p class="text-sm font-bold text-gray-800 mb-4">Direct
                                                            inline
                                                            web rendering is unavailable for direct PowerPoint
                                                            formats.
                                                        </p>
                                                        <a :href="previewUrl" download
                                                            class="inline-block px-6 py-3 bg-[#383838] text-white font-black silverware uppercase text-[10px] tracking-widest rounded-xl hover:bg-black transition-all">
                                                            <i class="ri-download-cloud-line mr-1 text-sm"></i>
                                                            Download
                                                            Presentation
                                                        </a>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div x-show="viewersOpen"
                                        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
                                        x-cloak>
                                        <div @click.away="viewersOpen = false"
                                            class="bg-white w-full max-w-2xl rounded-[40px] shadow-2xl flex flex-col max-h-[80vh] overflow-hidden">
                                            <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                                                <div>
                                                    <h3
                                                        class="text-2xl font-black text-[#383838] tracking-tighter uppercase">
                                                        Material Viewers</h3>
                                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1"
                                                        x-text="'Engagement Analysis For: ' + viewersTitle"></p>
                                                </div>
                                                <button @click="viewersOpen = false"
                                                    class="w-10 h-10 flex items-center justify-center bg-gray-50 rounded-full hover:bg-black hover:text-white">
                                                    <i class="ri-close-line text-xl"></i>
                                                </button>
                                            </div>

                                            <div class="p-6 overflow-y-auto flex-grow bg-gray-50/50">
                                                <div x-show="loadingViewers" class="py-12 text-center">
                                                    <div
                                                        class="w-8 h-8 border-4 border-black border-t-transparent rounded-full animate-spin mx-auto mb-2">
                                                    </div>
                                                    <p
                                                        class="text-xs text-gray-400 font-bold uppercase tracking-widest">
                                                        Querying analytics logs...</p>
                                                </div>

                                                <div x-show="!loadingViewers">
                                                    <table
                                                        class="w-full text-left bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                                                        <thead>
                                                            <tr
                                                                class="bg-gray-50 border-b border-gray-100 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                                                                <th class="py-4 px-6">Student Name</th>
                                                                <th class="py-4 px-4">Opened Date/Time</th>
                                                                <th class="py-4 px-6 text-right">View Duration</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody
                                                            class="divide-y divide-gray-50 text-xs font-semibold text-gray-700">
                                                            <template x-for="log in viewersList" :key="log.id">
                                                                <tr class="hover:bg-gray-50/80 transition-colors">
                                                                    <td class="py-4 px-6">
                                                                        <div class="font-bold text-gray-900"
                                                                            x-text="log.student_name.trim().includes(' ') ? log.student_name.trim().split(' ').pop() + ', ' + log.student_name.trim().split(' ').slice(0, -1).join(' ') : log.student_name">
                                                                        </div>
                                                                    </td>
                                                                    <td class="py-4 px-4 text-gray-500"
                                                                        x-text="log.viewed_at"></td>
                                                                    <td
                                                                        class="py-4 px-6 text-right font-mono text-black font-bold">
                                                                        <span
                                                                            class="inline-block px-2.5 py-1 bg-gray-100 rounded-md"
                                                                            :class="log.seconds_spent > 60 ? 'bg-green-50 text-green-700' : 'text-gray-700'"
                                                                            x-text="formatDuration(log.seconds_spent)"></span>
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                            <template x-if="viewersList.length === 0">
                                                                <tr>
                                                                    <td colspan="3"
                                                                        class="py-12 text-center text-gray-400 font-bold uppercase text-[11px] tracking-wider">
                                                                        No tracking logs generated for this material
                                                                        entry.
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        @php
                            $preparedTasks = $tasks->map(function ($task) {
                                $alpineCriteria = [];
                                if ($task->rubric && $task->rubric->criteria) {
                                    $uid = 1;
                                    $levelUid = 1000;
                                    foreach ($task->rubric->criteria as $c) {
                                        $levelsWithUids = [];
                                        foreach ($c->checking_rules['levels'] ?? [] as $lvl) {
                                            $levelsWithUids[] = [
                                                'uid' => $levelUid++,
                                                'label' => $lvl['label'] ?? 'Level',
                                                'points' => (int) ($lvl['points'] ?? 0),
                                                'description' => $lvl['description'] ?? '',
                                            ];
                                        }
                                        $alpineCriteria[] = [
                                            'uid' => $uid++,
                                            'name' => $c->criterion_name,
                                            'description' => $c->description ?? '',
                                            'levels' => $levelsWithUids,
                                        ];
                                    }
                                }

                                return [
                                    'id' => $task->id,
                                    'title' => $task->title,
                                    'description' => $task->description,
                                    'deadline' => $task->deadline,
                                    'deadline_formatted' => \Carbon\Carbon::parse($task->deadline)->format('M d, Y h:i A'),
                                    'points' => $task->points ?? 0,
                                    'submissions_count' => $task->submissions->count(),
                                    'rubric' => $task->rubric,
                                    'criteria' => $alpineCriteria,
                                    'submissions' => $task->submissions()->with(['user', 'submissionGrade.criterionScores.criterion'])->get()
                                ];
                            });
                        @endphp
                        <div x-show="activeTab === 'tasks'" class="space-y-6" x-cloak
                            x-data="taskManager({{ json_encode($preparedTasks) }}, {{ $session->subject_id ?? $session->id }})">

                            <div>
                                <div class="flex justify-between items-center mb-8 ms-4 me-4">
                                    <div>
                                        <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">
                                            Laboratory Tasks</h2>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                            Manage activities, rubrics, and grades</p>
                                    </div>
                                    <button @click="openEditor()"
                                        class="bg-[#383838] text-white px-8 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200 active:scale-95">
                                        + Create New Task
                                    </button>
                                </div>

                                {{-- Dynamic Reactive Task Grid --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                    <template x-for="task in taskList" :key="task.id">
                                        <div
                                            class="bg-white p-6 rounded-3xl border border-gray-100 flex flex-col justify-between shadow-sm hover:shadow-md hover:border-[#383838] transition-all group">

                                            <div>
                                                <div class="flex justify-between items-start mb-5">
                                                    <div
                                                        class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-[#383838] group-hover:text-white transition-colors duration-300">
                                                        <i class="ri-flask-line text-xl"></i>
                                                    </div>
                                                    <span
                                                        class="bg-gray-100 text-[#383838] px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border border-gray-200"
                                                        x-text="(task.points || 0) + ' PTS'">
                                                    </span>
                                                </div>

                                                <h4 class="font-black text-gray-900 text-lg mb-2 leading-tight"
                                                    x-text="task.title"></h4>

                                                <div class="space-y-2 mt-3 mb-6">
                                                    <div
                                                        class="flex items-center text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                                        <i class="ri-time-line mr-2 text-gray-300"></i>
                                                        <span
                                                            x-text="task.deadline_formatted || formatDate(task.deadline)"></span>
                                                    </div>
                                                    <div
                                                        class="flex items-center text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                                        <i class="ri-group-line mr-2 text-gray-300"></i>
                                                        <span
                                                            x-text="(task.submissions_count !== undefined ? task.submissions_count : (task.submissions ? task.submissions.length : 0)) + ' Submissions'"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-3">
                                                    {{-- Edit Trigger --}}
                                                    <button type="button"
                                                        @click="openEditor(task, task.rubric, task.criteria)"
                                                        class="text-gray-500 hover:text-[#383838] text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1">
                                                        <i class="ri-pencil-line text-xs"></i> Edit
                                                    </button>

                                                    {{-- Submissions Panel Trigger --}}
                                                    <button type="button" @click="openGrading(task, task.submissions)"
                                                        class="text-gray-500 hover:text-[#383838] text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1">
                                                        <i class="ri-check-double-line text-xs"></i> Submissions
                                                    </button>
                                                </div>

                                                {{-- Delete Control --}}
                                                <button type="button" @click="deleteTask(task.id)"
                                                    class="text-red-500 hover:text-red-700 text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1">
                                                    <i class="ri-delete-bin-line text-xs"></i> Delete
                                                </button>
                                            </div>

                                        </div>
                                    </template>

                                    <template x-if="taskList.length === 0">
                                        <div
                                            class="col-span-full py-24 border-2 border-dashed border-gray-200 rounded-[2rem] text-center bg-gray-50/50">
                                            <i class="ri-inbox-2-line text-5xl text-gray-300 mb-4 block"></i>
                                            <p class="text-gray-500 font-black uppercase tracking-widest text-xs">No
                                                laboratory tasks created yet.</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Task Editor Modal --}}
                            <template x-teleport="body">
                                <div x-show="showEditorModal"
                                    class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4"
                                    x-cloak x-transition.opacity>
                                    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col overflow-hidden transform transition-all"
                                        @click.away="showEditorModal = false">

                                        <div
                                            class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center shrink-0">
                                            <div>
                                                <h3 class="font-black text-xl text-gray-900 uppercase tracking-tight"
                                                    x-text="isEditing ? 'Edit Laboratory Task' : 'Create New Task'">
                                                </h3>
                                                <p
                                                    class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                                    Configure details & scoring rubric</p>
                                            </div>
                                            <div class="flex items-center gap-6">
                                                <div class="text-right hidden sm:block border-r border-gray-200 pr-6">
                                                    <p
                                                        class="text-[9px] text-gray-400 font-black uppercase tracking-widest">
                                                        Auto-Calculated Max Points</p>
                                                    <p class="text-2xl font-black text-[#383838]"
                                                        x-text="computedMaxPoints"></p>
                                                </div>
                                                <button @click="saveTask()" :disabled="saving"
                                                    class="bg-[#383838] hover:bg-black text-white px-8 py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-md transition flex items-center gap-2 disabled:opacity-50">
                                                    <i class="ri-save-line text-sm" x-show="!saving"></i>
                                                    <i class="ri-loader-4-line animate-spin text-sm" x-show="saving"
                                                        x-cloak></i>
                                                    <span
                                                        x-text="saving ? 'Saving...' : (isEditing ? 'Update Task' : 'Save & Publish')"></span>
                                                </button>
                                                <button @click="showEditorModal = false"
                                                    class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-100 transition text-gray-600">
                                                    <i class="ri-close-line text-lg"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-white">
                                            <form @submit.prevent="saveTask()"
                                                class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
                                                <input type="hidden" name="subject_id" :value="subjectId">
                                                <input type="hidden" name="points" :value="computedMaxPoints">
                                                <input type="hidden" name="rubric[criteria]"
                                                    :value="JSON.stringify(criteria)">
                                                <div class="lg:col-span-4 space-y-6">
                                                    <div class="bg-gray-50 rounded-3xl p-6 border border-gray-100">
                                                        <h4
                                                            class="font-black text-[#383838] uppercase tracking-widest text-xs mb-5 flex items-center gap-2">
                                                            <i class="ri-information-line"></i> Task Details
                                                        </h4>

                                                        <div class="space-y-4">
                                                            <div>
                                                                <label
                                                                    class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 px-1">Activity
                                                                    Title *</label>
                                                                <input type="text" x-model="taskForm.title" required
                                                                    placeholder="e.g., Python Basics Lab"
                                                                    class="w-full border-gray-200 bg-white rounded-xl p-3.5 text-sm font-bold focus:ring-2 focus:ring-[#383838] outline-none transition-all shadow-sm">
                                                            </div>

                                                            <div>
                                                                <label
                                                                    class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 px-1">Deadline
                                                                    *</label>
                                                                <input type="datetime-local" x-model="taskForm.deadline"
                                                                    required
                                                                    class="w-full border-gray-200 bg-white rounded-xl p-3.5 text-sm font-bold focus:ring-2 focus:ring-[#383838] outline-none transition-all shadow-sm text-gray-600">
                                                            </div>

                                                            <div>
                                                                <label
                                                                    class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 px-1">Instructions</label>
                                                                <textarea x-model="taskForm.description" rows="5"
                                                                    placeholder="Provide clear instructions for the students..."
                                                                    class="w-full border-gray-200 bg-white rounded-xl p-3.5 text-sm focus:ring-2 focus:ring-[#383838] outline-none transition-all shadow-sm resize-none"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="lg:col-span-8 space-y-4">
                                                    <h4
                                                        class="font-black text-[#383838] uppercase tracking-widest text-xs mb-2 flex items-center gap-2 px-1">
                                                        <i class="ri-list-check-2"></i> Grading Criteria (Rubric)
                                                    </h4>

                                                    <template x-for="(criterion, cIdx) in criteria"
                                                        :key="criterion.uid">
                                                        <div
                                                            class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-sm transition-all focus-within:border-[#383838]">
                                                            <div
                                                                class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 bg-gray-50">
                                                                <div class="w-7 h-7 rounded-full bg-[#383838] text-white flex items-center justify-center text-[10px] font-black shrink-0"
                                                                    x-text="cIdx + 1"></div>
                                                                <input x-model="criterion.name" type="text"
                                                                    placeholder="Criterion Name (e.g., Code Logic)"
                                                                    class="flex-1 bg-transparent border-b-2 border-dashed border-gray-300 focus:border-[#383838] outline-none text-sm font-black text-gray-900 py-1 transition">
                                                                <div class="flex items-center gap-4 shrink-0">
                                                                    <span
                                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Max:
                                                                        <span class="text-[#383838] text-sm"
                                                                            x-text="getMaxPoints(criterion)"></span>
                                                                    </span>
                                                                    <button type="button"
                                                                        @click="removeCriterion(criterion.uid)"
                                                                        x-show="criteria.length > 1"
                                                                        class="text-gray-400 hover:text-red-500 transition p-1.5 rounded-lg hover:bg-red-50">
                                                                        <i class="ri-delete-bin-line text-lg"></i>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="p-5 overflow-x-auto">
                                                                <div class="flex gap-4 min-w-max pb-2">
                                                                    <template x-for="level in criterion.levels"
                                                                        :key="level.uid">
                                                                        <div
                                                                            class="w-64 border border-gray-200 rounded-2xl overflow-hidden bg-white hover:border-gray-400 transition group flex flex-col shadow-sm">
                                                                            <div
                                                                                class="border-b border-gray-100 p-3 bg-gray-50">
                                                                                <input x-model="level.label" type="text"
                                                                                    placeholder="Level label"
                                                                                    class="w-full text-xs font-black text-gray-800 bg-transparent border-none outline-none mb-2 p-0 focus:ring-0">
                                                                                <div class="flex items-center gap-2">
                                                                                    <input x-model.number="level.points"
                                                                                        type="number" min="0"
                                                                                        class="w-16 text-sm font-black text-[#383838] bg-white border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-1 focus:ring-[#383838] outline-none text-center">
                                                                                    <span
                                                                                        class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Points</span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="p-3 flex-grow bg-white">
                                                                                <textarea x-model="level.description"
                                                                                    placeholder="Requirements for this level..."
                                                                                    rows="3"
                                                                                    class="w-full text-xs text-gray-600 bg-transparent border-none outline-none resize-none p-0 focus:ring-0"></textarea>
                                                                            </div>
                                                                            <div class="px-3 pb-3 text-right bg-white">
                                                                                <button type="button"
                                                                                    @click="removeLevel(criterion, level.uid)"
                                                                                    x-show="criterion.levels.length > 1"
                                                                                    class="text-[9px] text-gray-400 hover:text-red-500 font-black uppercase tracking-widest">Remove</button>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                    <div class="w-40 flex-shrink-0">
                                                                        <button type="button"
                                                                            @click="addLevel(criterion)"
                                                                            class="w-full h-full min-h-[160px] border-2 border-dashed border-gray-200 rounded-2xl text-gray-400 hover:border-[#383838] hover:text-[#383838] transition flex flex-col items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest bg-gray-50/50 hover:bg-gray-50">
                                                                            <i class="ri-add-circle-line text-2xl"></i>
                                                                            Add Level
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            {{-- AI Context Instructions --}}
                                                            <div
                                                                class="px-5 pb-5 border-t border-gray-100 pt-4 bg-white">
                                                                <label
                                                                    class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                                                    <i class="ri-robot-line text-sm text-[#383838]"></i>
                                                                    AI Context Instructions (Optional)
                                                                </label>
                                                                <textarea x-model="criterion.description"
                                                                    placeholder="Tell the AI exactly what to check for regarding this criterion..."
                                                                    rows="2"
                                                                    class="w-full text-xs border-gray-200 bg-gray-50 rounded-xl p-3 resize-none focus:ring-2 focus:ring-[#383838] outline-none transition"></textarea>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <button type="button" @click="addCriterion()"
                                                        class="w-full py-5 border-2 border-dashed border-gray-200 rounded-3xl text-gray-500 hover:border-[#383838] hover:text-[#383838] font-black text-[10px] uppercase tracking-widest transition flex items-center justify-center gap-2 bg-gray-50 hover:bg-gray-100">
                                                        <i class="ri-add-circle-fill text-xl"></i> Add New Criterion
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Submissions & Grading Modal --}}
                            <template x-teleport="body">
                                <div x-show="showGradingModal"
                                    class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4"
                                    x-cloak x-transition.opacity>
                                    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-7xl h-[90vh] flex flex-col overflow-hidden transform transition-all"
                                        @click.away="showGradingModal = false">

                                        <div
                                            class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center shrink-0">
                                            <div>
                                                <h3 class="font-black text-xl text-gray-900 uppercase tracking-tight"
                                                    x-text="gradingTask?.title"></h3>
                                                <p
                                                    class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                                    Reviewing Submissions</p>
                                            </div>

                                            <div class="flex items-center gap-6">
                                                {{-- Auto-Checker Toggle Switch --}}
                                                <div
                                                    class="flex items-center gap-3 bg-white border border-gray-200 px-4 py-2.5 rounded-2xl shadow-sm">
                                                    <p
                                                        class="text-xs font-black text-[#383838] uppercase tracking-widest">
                                                        Auto-Checker</p>
                                                    <button @click="toggleAiGrading()"
                                                        :class="aiGradingEnabled ? 'bg-[#383838]' : 'bg-gray-200'"
                                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                                        <span
                                                            :class="aiGradingEnabled ? 'translate-x-5' : 'translate-x-0'"
                                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                                    </button>
                                                </div>

                                                <button @click="showGradingModal = false"
                                                    class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-100 transition text-gray-600">
                                                    <i class="ri-close-line text-lg"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            class="px-6 py-4 border-b border-gray-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                                            <div class="relative w-full sm:w-96">
                                                <i
                                                    class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                                <input type="text" x-model="searchQuery"
                                                    placeholder="Search by student name..."
                                                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sort
                                                    By:</span>
                                                <select x-model="sortBy"
                                                    class="bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-[#383838] outline-none cursor-pointer">
                                                    <option value="name_asc">Name (A-Z)</option>
                                                    <option value="name_desc">Name (Z-A)</option>
                                                    <option value="score_desc">Highest Score</option>
                                                    <option value="score_asc">Lowest Score</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex-1 overflow-y-auto p-6 bg-white">
                                            <table class="w-full text-left border-separate border-spacing-y-4">
                                                <thead>
                                                    <tr
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        <th class="px-6 pb-2">Student</th>
                                                        <th class="px-6 pb-2">Submitted</th>
                                                        <th class="px-6 pb-2">Duration</th>
                                                        <th class="px-6 pb-2">File</th>
                                                        <th class="px-6 pb-2 text-right">Grading & Feedback</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="sub in filteredSubmissions" :key="sub.id">
                                                        <tr
                                                            class="bg-gray-50/50 hover:bg-gray-50 transition-all rounded-3xl group border border-gray-100">
                                                            <td
                                                                class="px-6 py-6 font-bold text-gray-900 rounded-l-3xl border-y border-l border-gray-100 align-top">
                                                                <span class="block text-sm text-[#383838]"
                                                                    x-text="sub.user ? `${sub.user.last_name}, ${sub.user.first_name}` : 'N/A'"></span>
                                                            </td>
                                                            <td class="px-6 py-6 text-xs font-bold text-gray-600 border-y border-gray-100 align-top"
                                                                x-text="formatDate(sub.created_at)"></td>
                                                            <td class="px-6 py-6 text-xs font-bold text-gray-600 border-y border-gray-100 align-top"
                                                                x-text="formatDuration(sub.duration_seconds ?? sub.duration ?? sub.time_taken) || '--'">
                                                            </td>
                                                            <td class="px-6 py-6 border-y border-gray-100 align-top">
                                                                <a :href="'{{ url('/') }}/' + sub.file_path"
                                                                    target="_blank"
                                                                    class="inline-flex items-center text-[10px] font-black text-gray-700 bg-white px-4 py-2.5 rounded-xl hover:bg-[#383838] hover:text-white transition-all uppercase tracking-widest border border-gray-200 shadow-sm">
                                                                    <i class="ri-download-2-line mr-2 text-sm"></i>
                                                                    Download
                                                                </a>
                                                            </td>
                                                            <td
                                                                class="px-6 py-6 rounded-r-3xl border-y border-r border-gray-100 align-top">
                                                                <div
                                                                    class="mb-5 flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                                                                    <div class="flex items-center gap-4">
                                                                        <div>
                                                                            <span
                                                                                class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Grading
                                                                                Status</span>
                                                                            <p class="text-xs font-black mt-1 text-[#383838]"
                                                                                x-text="(sub.auto_graded && aiGradingEnabled) ? 'Auto Evaluated' : (sub.grade !== null ? 'Manual Entry' : 'Pending')">
                                                                            </p>
                                                                        </div>
                                                                        <button x-show="aiGradingEnabled"
                                                                            @click="regradeSubmission(sub, $event)"
                                                                            class="ml-2 bg-gray-100 hover:bg-gray-200 text-[#383838] text-[9px] font-black px-3 py-2 rounded-xl transition flex items-center gap-1.5 uppercase tracking-widest border border-gray-200">
                                                                            <i class="ri-magic-line text-sm"></i> Auto
                                                                            Grade
                                                                        </button>
                                                                    </div>
                                                                    <div class="text-right">
                                                                        <span
                                                                            class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Achieved
                                                                            Score</span>
                                                                        <p class="text-lg font-black text-[#383838]">
                                                                            <span x-text="sub.grade ?? '0'"></span>
                                                                            <span
                                                                                class="text-xs text-gray-400 font-bold tracking-widest"
                                                                                x-text="'/ ' + (gradingTask ? gradingTask.points : 0)"></span>
                                                                        </p>
                                                                    </div>
                                                                </div>

                                                                {{-- Rubric Breakdown --}}
                                                                <template
                                                                    x-if="sub.submission_grade && sub.submission_grade.criterion_scores">
                                                                    <div class="mb-5 space-y-3">
                                                                        <h4
                                                                            class="text-[9px] font-black uppercase tracking-widest text-gray-400">
                                                                            Rubric Breakdown</h4>
                                                                        <template
                                                                            x-for="score in sub.submission_grade.criterion_scores"
                                                                            :key="score.id">
                                                                            <div
                                                                                class="p-4 border border-gray-200 bg-white rounded-2xl shadow-sm">
                                                                                <p class="text-[11px] text-gray-500 leading-relaxed font-medium"
                                                                                    x-text="score.feedback"></p>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                                <!-- Front-End Interactive Rubric Selector -->
                                                                <template
                                                                    x-if="gradingTask && (gradingTask.rubric || gradingTask.criteria)">
                                                                    <div
                                                                        class="mb-5 space-y-4 bg-white p-4 rounded-2xl border border-gray-200">
                                                                        <h4
                                                                            class="text-[9px] font-black uppercase tracking-widest text-gray-400">
                                                                            Select Criteria Levels
                                                                        </h4>

                                                                        <template
                                                                            x-for="(criterion, cIdx) in (gradingTask.criteria || (gradingTask.rubric ? JSON.parse(gradingTask.rubric.criteria_json || '[]') : []))"
                                                                            :key="cIdx">
                                                                            <div
                                                                                class="space-y-2 border-b border-gray-100 pb-3 last:border-none last:pb-0">
                                                                                <div
                                                                                    class="flex justify-between items-center">
                                                                                    <span
                                                                                        class="text-xs font-black text-gray-800"
                                                                                        x-text="criterion.name || criterion.criterion_name"></span>
                                                                                    <span
                                                                                        class="text-[9px] font-black text-gray-400 uppercase"
                                                                                        x-text="'Max: ' + getMaxPoints(criterion) + ' PTS'"></span>
                                                                                </div>

                                                                                <!-- Level Buttons -->
                                                                                <div class="flex flex-wrap gap-2">
                                                                                    <template
                                                                                        x-for="level in criterion.levels"
                                                                                        :key="level.label || level.uid">
                                                                                        <button type="button"
                                                                                            @click="selectCriterionLevel(sub, cIdx, level.points)"
                                                                                            :class="isCriterionLevelSelected(sub, cIdx, level.points) 
                                ? 'bg-[#383838] text-white border-[#383838]' 
                                : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'"
                                                                                            class="px-3 py-1.5 rounded-xl border text-xs font-bold transition flex items-center gap-2">
                                                                                            <span
                                                                                                x-text="level.label"></span>
                                                                                            <span
                                                                                                class="text-[10px] opacity-75"
                                                                                                x-text="'(' + level.points + 'p)'"></span>
                                                                                        </button>
                                                                                    </template>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                                {{-- Non-refreshing Ajax Grade Form --}}
                                                                <form :action="'/professor/grade/' + sub.id"
                                                                    method="POST"
                                                                    class="flex flex-col gap-3 mt-2 border-t border-gray-200 pt-5"
                                                                    @submit.prevent="submitGrade(sub, $event)">
                                                                    @csrf
                                                                    <div class="flex items-center justify-between">
                                                                        <span
                                                                            class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Final
                                                                            Score Override</span>
                                                                        <div
                                                                            class="flex items-center bg-white border border-gray-200 rounded-xl px-3 py-1 shadow-sm focus-within:ring-2 focus-within:ring-[#383838] transition">
                                                                            <input type="number" name="grade"
                                                                                :value="sub.grade"
                                                                                class="w-14 bg-transparent border-none p-0 text-sm font-black text-center focus:ring-0 text-[#383838]"
                                                                                placeholder="0">
                                                                            <span
                                                                                class="text-[10px] font-black text-gray-400 ml-1"
                                                                                x-text="'/ ' + (gradingTask ? gradingTask.points : 0)"></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex items-stretch gap-2 h-12">
                                                                        <input type="text" name="feedback"
                                                                            :value="sub.feedback"
                                                                            class="w-full border-gray-200 rounded-xl text-xs px-4 focus:ring-2 focus:ring-[#383838] transition-all bg-white"
                                                                            placeholder="Enter manual comments...">
                                                                        <button type="submit"
                                                                            class="bg-[#383838] text-white px-5 rounded-xl hover:bg-black transition shadow-sm h-full flex items-center justify-center w-16 shrink-0">
                                                                            <i class="ri-check-line text-lg"></i>
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>

                                            <template x-if="filteredSubmissions.length === 0">
                                                <div
                                                    class="text-center py-24 bg-gray-50/50 rounded-[2rem] border border-gray-100 shadow-inner mt-4">
                                                    <i class="ri-search-eye-line text-6xl text-gray-200 mb-4 block"></i>
                                                    <p
                                                        class="text-gray-400 font-black text-xs uppercase tracking-widest">
                                                        No submissions match your query.</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="activeTab === 'quizzes'" x-data="{ 
        selectedQuiz: null, 
        scores: [],
        searchQuery: '',
        sortBy: 'name_asc', // Default sorting
        
        closeResults() { 
            this.selectedQuiz = null; 
            this.searchQuery = '';
            this.sortBy = 'name_asc';
        },
        
        // Reactive getter for sorting and searching
        get filteredScores() {
            let result = this.scores;
            
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(attempt => {
                    const fullName = `${attempt.user?.first_name || ''} ${attempt.user?.last_name || ''}`.toLowerCase();
                    return fullName.includes(q);
                });
            }

            return result.sort((a, b) => {
                const nameA = `${a.user?.last_name || ''} ${a.user?.first_name || ''}`.toLowerCase();
                const nameB = `${b.user?.last_name || ''} ${b.user?.first_name || ''}`.toLowerCase();
                const scoreA = parseFloat(a.score) || 0;
                const scoreB = parseFloat(b.score) || 0;

                if (this.sortBy === 'name_asc') return nameA.localeCompare(nameB);
                if (this.sortBy === 'name_desc') return nameB.localeCompare(nameA);
                if (this.sortBy === 'score_desc') return scoreB - scoreA;
                if (this.sortBy === 'score_asc') return scoreA - scoreB;
                
                return 0;
            });
        }
    }" class="space-y-6" x-cloak>

                            <div class="flex justify-between items-center mb-8 ms-4 me-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Quiz
                                        Management</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Control laboratory quiz & results</p>
                                </div>
                                <a href="{{ route('professor.quizzes.create', ['session_id' => $session->id]) }}"
                                    target="_blank"
                                    class="bg-[#383838] text-white px-8 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200 active:scale-95">
                                    + Create Quiz
                                </a>
                            </div>

                            <div id="quizzes-list-container"
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-data="{ 
        deletedQuizzes: [],
        async removeQuiz(quizId, routeUrl) {
            if (!confirm('Are you sure you want to permanently wipe this quiz and all logged student attempts? This cannot be undone.')) {
                return;
            }
            
            try {
                let response = await fetch(routeUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ _method: 'DELETE' })
                });
                
                let data = await response.json();
                this.deletedQuizzes.push(quizId);
            } catch (error) {
                console.error('Quiz destruction failed:', error);
                this.deletedQuizzes.push(quizId);
            }
        }
     }">

                                @forelse($session->quizzes ?? [] as $quiz)
                                    <div class="bg-white p-5 rounded-2xl border border-gray-100 flex flex-col justify-between group hover:border-[#383838] transition-all duration-300 shadow-sm"
                                        x-show="!deletedQuizzes.includes({{ $quiz->id }})"
                                        x-transition:leave="transition ease-in duration-200"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95">

                                        <div>
                                            <div class="flex justify-between items-start mb-4">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center border group-hover:bg-black group-hover:text-white transition">
                                                    <i class="ri-timer-line text-lg"></i>
                                                </div>
                                                <span
                                                    class="bg-gray-100 text-[#383838] px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                                                    {{ $quiz->questions->count() }} PTS
                                                </span>
                                            </div>

                                            <h4 class="font-bold text-gray-900 mb-1 group-hover:text-black transition">
                                                {{ $quiz->title }}
                                            </h4>

                                            <div class="space-y-2 mt-4">
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-calendar-todo-line mr-2"></i>
                                                    {{ \Carbon\Carbon::parse($quiz->deadline)->format('M d, h:i A') }}
                                                </div>
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-time-line mr-2"></i>
                                                    {{ $quiz->time_limit }} Mins Duration
                                                </div>
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-group-line mr-2"></i>
                                                    {{ $quiz->attempts->count() }} Answered
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="mt-6 flex items-center justify-between gap-3 pt-4 border-t border-gray-50">
                                            {{-- View Results Button (Placed prominently on the left side) --}}
                                            <button type="button"
                                                @click="selectedQuiz = {{ json_encode($quiz) }}; scores = {{ json_encode($quiz->attempts()->with('user')->get()) }}"
                                                class="flex-1 bg-gray-50 text-[#383838] border border-gray-200 py-2.5 px-4 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest cursor-pointer text-center">
                                                View Results
                                            </button>

                                            <a href="{{ route('professor.quizzes.export-scores', $quiz) }}"
                                                class="flex-shrink-0 bg-[#383838] text-white py-2.5 px-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all flex items-center gap-1.5"
                                                title="Export quiz scores to Excel">
                                                <i class="ri-file-excel-2-line text-xs"></i>
                                                Export
                                            </a>

                                            {{-- Inline Asynchronous Delete Control (Aligned right) --}}
                                            <button type="button"
                                                @click="removeQuiz({{ $quiz->id }}, '{{ route('professor.quizzes.destroy', $quiz->id) }}')"
                                                class="text-red-500 hover:text-red-700 text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1 bg-transparent border-0 cursor-pointer p-2 rounded-lg hover:bg-red-50/50 flex-shrink-0">
                                                <i class="ri-delete-bin-line text-xs"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center">
                                        <i class="ri-timer-flash-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 italic text-sm">No quizzes available for this session.</p>
                                    </div>
                                @endforelse
                            </div>

                            <template x-teleport="body">
                                <div x-show="selectedQuiz"
                                    class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4 sm:p-6"
                                    x-cloak x-transition.opacity>

                                    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-4xl h-[85vh] flex flex-col overflow-hidden transform transition-all"
                                        @click.away="closeResults()" x-transition:enter="ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                                        <div
                                            class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                                            <div>
                                                <h3 class="font-black text-2xl text-gray-900 uppercase tracking-tight"
                                                    x-text="selectedQuiz?.title"></h3>
                                                <p
                                                    class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mt-1">
                                                    Quiz Performance & Score Overview</p>
                                            </div>
                                            <button @click="closeResults()"
                                                class="w-10 h-10 flex items-center justify-center bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-900 rounded-xl transition-all">
                                                <i class="ri-close-line text-xl"></i>
                                            </button>
                                        </div>

                                        <div
                                            class="px-8 py-4 border-b border-gray-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                                            <div class="relative w-full sm:w-80">
                                                <i
                                                    class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                                <input type="text" x-model="searchQuery"
                                                    placeholder="Search student name..."
                                                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a x-bind:href="selectedQuiz ? '{{ url('/professor/quizzes') }}/' + selectedQuiz.id + '/export-scores' : '#'"
                                                    class="inline-flex items-center gap-2 bg-[#383838] text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all">
                                                    <i class="ri-file-excel-2-line text-sm"></i>
                                                    Export to Excel
                                                </a>
                                                <span
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sort
                                                    By:</span>
                                                <select x-model="sortBy"
                                                    class="bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-[#383838] outline-none cursor-pointer">
                                                    <option value="name_asc">Name (A-Z)</option>
                                                    <option value="name_desc">Name (Z-A)</option>
                                                    <option value="score_desc">Highest Score</option>
                                                    <option value="score_asc">Lowest Score</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-gray-50/50">

                                            <template x-if="scores.length > 0">
                                                <div
                                                    class="hidden sm:flex items-center justify-between px-6 mb-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                    <div class="w-1/2">Student Name</div>
                                                    <div class="w-1/4 text-center">Time Taken</div>
                                                    <div class="w-1/4 text-right">Final Score</div>
                                                </div>
                                            </template>

                                            <div class="space-y-3">
                                                <template x-for="attempt in filteredScores" :key="attempt.id">
                                                    <div
                                                        class="flex flex-col sm:flex-row sm:items-center justify-between bg-white p-4 sm:px-6 rounded-2xl border border-gray-100 shadow-sm hover:border-[#383838] transition-all group gap-4">

                                                        <div class="w-full sm:w-1/2 flex items-center gap-4">
                                                            <span
                                                                class="font-bold text-gray-900 text-sm group-hover:text-black transition-colors"
                                                                x-text="attempt.user ? `${attempt.user.last_name}, ${attempt.user.first_name}` : 'N/A'"></span>
                                                        </div>

                                                        <div class="w-full sm:w-1/4 flex sm:justify-center">
                                                            <div
                                                                class="inline-flex items-center text-[10px] font-black text-gray-500 bg-gray-50 px-3 py-2 rounded-xl border border-gray-100 uppercase tracking-widest">
                                                                <i class="ri-timer-line mr-2"></i>
                                                                <span
                                                                    x-text="Math.floor(attempt.time_spent / 60) + 'm ' + (attempt.time_spent % 60) + 's'"></span>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="w-full sm:w-1/4 flex items-center sm:justify-end gap-2">
                                                            <div class="px-3 py-2 border-2 border-gray-50 rounded-xl">
                                                                <span
                                                                    class="text-xs font-black text-gray-400 group-hover:text-[#383838] transition-colors"
                                                                    x-text="Math.round((attempt.score / attempt.total_questions) * 100) + '%'"></span>
                                                            </div>
                                                            <div
                                                                class="px-4 py-2 bg-[#383838] text-white rounded-xl shadow-sm">
                                                                <span class="text-xs font-black tracking-wide"
                                                                    x-text="attempt.score + ' / ' + attempt.total_questions"></span>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </template>
                                            </div>

                                            <template x-if="scores.length === 0">
                                                <div
                                                    class="text-center py-20 bg-white border-2 border-dashed border-gray-100 rounded-[2rem] flex flex-col items-center justify-center mt-2">
                                                    <div
                                                        class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                                                        <i class="ri-inbox-line text-2xl text-gray-300"></i>
                                                    </div>
                                                    <h4 class="text-gray-900 font-bold mb-1">No Submissions Yet</h4>
                                                    <p class="text-gray-400 text-xs font-bold">Students haven't
                                                        completed this quiz.</p>
                                                </div>
                                            </template>

                                            <template x-if="scores.length > 0 && filteredScores.length === 0">
                                                <div
                                                    class="text-center py-20 flex flex-col items-center justify-center mt-2">
                                                    <i class="ri-search-eye-line text-6xl text-gray-200 mb-4 block"></i>
                                                    <p
                                                        class="text-gray-400 font-black text-xs uppercase tracking-widest">
                                                        No submissions match your search.</p>
                                                </div>
                                            </template>

                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="activeTab === 'students'" x-data="studentManager()" x-cloak
                            class="space-y-6 animate-fade-in">

                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 ms-4 me-4 gap-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Enrolled
                                        Students</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Monitor presence and activity history
                                    </p>
                                </div>

                                <div
                                    class="bg-gray-100 text-[#383838] px-5 py-2.5 rounded-xl flex items-center gap-3 border border-gray-200 shadow-sm">
                                    <i class="ri-team-line text-lg"></i>
                                    <span class="text-[10px] font-black uppercase tracking-widest">
                                        Total Enrolled: <span x-text="students.length"></span>
                                    </span>
                                </div>
                            </div>

                            {{-- Reactive Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <template x-for="student in students" :key="student.id">
                                    <div @click="viewStudentActivity(student.id, student.first_name, student.last_name, student.attendances, {{ $class->id }})"
                                        :class="student.is_screen_blocked ? 'border-red-300 bg-red-50/30' : 'border-gray-100'"
                                        class="bg-white p-5 rounded-3xl border flex items-center justify-between group hover:border-[#383838] hover:shadow-lg transition-all duration-300 cursor-pointer">

                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-[#383838] font-black text-sm border border-gray-100 group-hover:bg-[#383838] group-hover:text-white transition-colors">
                                                <span
                                                    x-text="(student.first_name[0] + student.last_name[0]).toUpperCase()"></span>
                                            </div>

                                            <div class="flex flex-col min-w-0">
                                                <h4
                                                    class="font-black text-gray-900 text-sm truncate leading-tight group-hover:text-black transition">
                                                    <span
                                                        x-text="student.last_name.toUpperCase() + ', ' + student.first_name"></span>
                                                </h4>
                                                <p class="text-[10px] text-gray-400 font-bold tracking-widest mt-1"
                                                    x-text="student.school_id"></p>

                                                <template
                                                    x-if="student.violation_count > 0 || student.is_screen_blocked">
                                                    <p class="text-[9px] font-black uppercase tracking-widest mt-1"
                                                        :class="student.is_screen_blocked ? 'text-red-600' : 'text-amber-600'">
                                                        <span
                                                            x-text="student.is_screen_blocked ? 'Screen Locked' : 'Warnings'"></span>:
                                                        <span
                                                            x-text="student.violation_count + '/' + warningThreshold"></span>
                                                    </p>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            {{-- Unblock Button --}}
                                            <button type="button" x-show="student.is_screen_blocked"
                                                @click.stop="unblockStudent(student.id)"
                                                class="text-[9px] font-black uppercase tracking-widest bg-red-600 text-white px-3 py-2 rounded-lg hover:bg-red-700 transition">
                                                Unblock
                                            </button>

                                            {{-- Presence Indicator --}}
                                            <div x-show="student.is_present"
                                                class="w-3 h-3 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)] animate-pulse"
                                                title="Active"></div>
                                            <div x-show="!student.is_present" class="w-3 h-3 rounded-full bg-gray-200"
                                                title="Offline"></div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="students.length === 0">
                                    <div
                                        class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center bg-white">
                                        <i class="ri-user-unfollow-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 font-bold text-sm">No students enrolled in this session.
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </div>



                        <div x-show="activeTab === 'browser-security'" x-data="browserSecurityManager()" x-cloak
                            class="space-y-6 animate-fade-in">

                            <!-- Header Section -->
                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 ms-4 me-4 gap-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Browser
                                        Security</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Restrict access to specific websites and keywords during sessions
                                    </p>
                                </div>
                                <div
                                    class="flex items-center gap-3 bg-white px-6 py-3 rounded-2xl border border-gray-100 shadow-sm">
                                    <span class="flex h-2 w-2 relative">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#383838]"></span>
                                    </span>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-[#383838]">
                                        Blacklist Mode Active
                                    </span>
                                </div>
                            </div>

                            <!-- Main Grid Workspace -->
                            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                                <!-- Left Column: Add Blocked Domain Form -->
                                <div class="xl:col-span-1">
                                    <div
                                        class="bg-white rounded-[2rem] border border-gray-100 p-8 shadow-sm sticky top-6">
                                        <h3
                                            class="text-xs font-black text-[#383838] uppercase tracking-widest mb-6 flex items-center">
                                            <i class="ri-forbid-2-line mr-2 text-lg text-[#383838]"></i> Block a
                                            Website
                                        </h3>

                                        <div class="mb-6 p-4 bg-amber-50 border border-amber-100 rounded-2xl">
                                            <label
                                                class="text-[10px] font-black text-amber-700 uppercase tracking-widest block mb-2">
                                                Violation Warning Limit
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <input type="number" min="1" max="10"
                                                    x-model.number="violationWarningThreshold"
                                                    class="w-20 border-none bg-white rounded-xl p-3 text-sm font-black text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none text-center">
                                                <button type="button" @click="saveViolationSettings()"
                                                    :disabled="savingThreshold"
                                                    class="flex-1 py-3 bg-[#383838] text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition disabled:opacity-50">
                                                    <span x-show="!savingThreshold">Save Limit</span>
                                                    <span x-show="savingThreshold">Saving...</span>
                                                </button>
                                            </div>
                                            <p class="text-[9px] text-amber-700/80 font-bold mt-2 leading-relaxed">
                                                Students are warned on each violation. After this many violations, their
                                                screen is locked until you unblock them.
                                            </p>
                                        </div>

                                        <form @submit.prevent="addSite()" class="space-y-5">
                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">
                                                    Domain / Keyword *
                                                </label>
                                                <input type="text" x-model="newSite.domain" placeholder="facebook.com"
                                                    required
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold mt-2 px-1 uppercase tracking-widest">
                                                    Exclude http://, https://, or www.
                                                </p>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">
                                                    Display Name *
                                                </label>
                                                <input type="text" x-model="newSite.name" placeholder="Facebook"
                                                    required
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                            </div>

                                            <div x-show="newSite.scope === 'task'" x-collapse>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">
                                                    Target Task Restrictions
                                                </label>
                                                <select x-model="newSite.task_id"
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none cursor-pointer transition-all">
                                                    <option value="">Select a task target...</option>
                                                    <template x-for="task in tasks" :key="task.id">
                                                        <option :value="task.id" x-text="task.title"></option>
                                                    </template>
                                                </select>
                                            </div>

                                            <button type="submit" :disabled="adding"
                                                class="w-full py-4 bg-[#383838] text-white rounded-2xl font-black uppercase text-[10px] hover:bg-black transition-all shadow-md tracking-widest mt-2 flex justify-center items-center gap-2">
                                                <span x-show="!adding">+ Blacklist Domain</span>
                                                <span x-show="adding" class="flex items-center gap-2">
                                                    <i class="ri-loader-4-line animate-spin text-sm"></i> Updating
                                                    Blocklist...
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Right Column: Active Blacklist Restrictions Registry -->
                                <div class="xl:col-span-2 space-y-6">

                                    <!-- Quick Counters Panel -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div
                                            class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm flex items-center justify-between">
                                            <div>
                                                <p
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                    Blocked Targets</p>
                                                <p class="text-2xl font-black text-[#383838] mt-1"
                                                    x-text="sessionSites.length"></p>
                                            </div>
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-[#383838] text-xl">
                                                <i class="ri-shield-flash-line"></i>
                                            </div>
                                        </div>
                                        <div
                                            class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm flex items-center justify-between">
                                            <div>
                                                <p
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                    Monitored Tasks</p>
                                                <p class="text-2xl font-black text-[#383838] mt-1"
                                                    x-text="tasks.length"></p>
                                            </div>
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 text-xl">
                                                <i class="ri-task-line"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Main Rules Card -->
                                    <div class="bg-white rounded-[2rem] border border-gray-100 p-8 shadow-sm">
                                        <div class="flex justify-between items-center mb-6">
                                            <h3
                                                class="text-xs font-black text-[#383838] uppercase tracking-widest flex items-center">
                                                <i class="ri-list-check-2 mr-2 text-lg text-[#383838]"></i> Active
                                                Restrictions Registry
                                            </h3>
                                            <span
                                                class="text-[9px] bg-gray-100 font-black uppercase tracking-widest text-[#383838] px-3 py-1.5 rounded-lg"
                                                x-text="sessionSites.length + ' blocked sites'"></span>
                                        </div>

                                        <!-- Scrollable Container -->
                                        <div class="space-y-3 max-h-[420px] overflow-y-auto pr-2 custom-scrollbar">
                                            <template x-for="site in sessionSites" :key="site.id">
                                                <div
                                                    class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-transparent group transition-all hover:bg-white hover:border-gray-200 hover:shadow-sm">
                                                    <div class="flex items-center gap-4">
                                                        <div class="w-10 h-10 rounded-xl bg-gray-200 text-[#383838] flex items-center justify-center font-black text-xs uppercase"
                                                            x-text="site.name.substring(0,2)">
                                                        </div>
                                                        <div>
                                                            <div class="flex items-center gap-2">
                                                                <h4 class="font-black text-sm text-[#383838]"
                                                                    x-text="site.name"></h4>
                                                                <span
                                                                    class="text-[8px] font-black bg-gray-100 text-gray-500 uppercase tracking-widest px-2 py-0.5 rounded-md border border-gray-200"
                                                                    x-text="site.scope || 'global'"></span>
                                                            </div>
                                                            <p class="text-[10px] text-gray-400 font-bold tracking-widest mt-0.5"
                                                                x-text="site.domain"></p>
                                                        </div>
                                                    </div>

                                                    <button @click="deleteSite(site.id)"
                                                        class="text-gray-300 hover:text-black transition-colors p-2 rounded-xl hover:bg-gray-100"
                                                        title="Remove Restriction">
                                                        <i class="ri-delete-bin-line text-lg"></i>
                                                    </button>
                                                </div>
                                            </template>

                                            <!-- Empty State Configuration -->
                                            <div x-show="sessionSites.length === 0"
                                                class="text-center py-16 text-gray-400 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                                <i class="ri-shield-check-line text-4xl mb-3 block text-gray-300"></i>
                                                <p class="text-xs font-black uppercase tracking-widest text-gray-400">
                                                    No
                                                    Custom Blocks Set</p>
                                                <p
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1 px-4">
                                                    All standard web paths are accessible to students by default.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function browserSecurityManager() {
                        return {
                            preApprovedSites: [],
                            sessionSites: [],
                            taskSites: [],
                            tasks: [],
                            blockedAttempts: [],
                            blockedStats: {},
                            adding: false,
                            savingThreshold: false,
                            violationWarningThreshold: {{ $session->violation_warning_threshold ?? config('lmms.violation_warning_threshold', 3) }},
                            newSite: {
                                domain: '',
                                name: '',
                                scope: 'global',
                                task_id: '',
                                description: '',
                                lab_session_id: '{{ $session->id }}'
                            },

                            init() {
                                this.loadSites();
                                this.loadTasks();
                                this.loadBlockedAttempts();
                            },

                            async loadSites() {
                                try {
                                    const res = await fetch('/professor/classroom/{{ $session->id }}/allowed-sites');
                                    const data = await res.json();
                                    this.preApprovedSites = data.pre_approved;
                                    this.sessionSites = data.session_sites;
                                    this.taskSites = data.task_sites;
                                } catch (error) {
                                    console.error('Error loading sites:', error);
                                }
                            },

                            async loadTasks() {
                                try {
                                    const res = await fetch('{{ route("professor.classroom.tasks", $session->id) }}');
                                    if (res.ok) {
                                        this.tasks = await res.json();
                                    } else {
                                        this.tasks = [];
                                    }
                                } catch (error) {
                                    console.error('Error loading tasks:', error);
                                    this.tasks = []; // Prevent undefined errors
                                }
                            },

                            async addSite() {
                                if (!this.newSite.domain || !this.newSite.name) {
                                    alert('❌ Please fill in domain and name');
                                    return;
                                }

                                this.adding = true;

                                try {
                                    const res = await fetch('/professor/allowed-sites', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify(this.newSite)
                                    });

                                    const data = await res.json();

                                    if (res.ok) {
                                        alert('✅ Website added successfully!');
                                        this.loadSites();

                                        // Reset form
                                        this.newSite = {
                                            domain: '',
                                            name: '',
                                            scope: 'global',
                                            task_id: '',
                                            description: '',
                                            lab_session_id: '{{ $session->id }}'
                                        };
                                    } else {
                                        alert('❌ ' + (data.error || 'Failed to add site'));
                                    }
                                } catch (error) {
                                    console.error('Error adding site:', error);
                                    alert('❌ Network error');
                                } finally {
                                    this.adding = false;
                                }
                            },

                            async deleteSite(id) {
                                if (!confirm('❌ Remove this site from the whitelist?')) return;

                                try {
                                    const res = await fetch(`/professor/allowed-sites/${id}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    });

                                    if (res.ok) {
                                        alert('✅ Site removed from whitelist');
                                        this.loadSites();
                                    } else {
                                        alert('❌ Failed to remove site');
                                    }
                                } catch (error) {
                                    console.error('Error deleting site:', error);
                                }
                            },

                            async loadBlockedAttempts() {
                                try {
                                    const [attemptsRes, statsRes] = await Promise.all([
                                        fetch('/professor/classroom/{{ $session->id }}/blocked-attempts'),
                                        fetch('/professor/classroom/{{ $session->id }}/blocked-stats')
                                    ]);

                                    this.blockedAttempts = await attemptsRes.json();
                                    this.blockedStats = await statsRes.json();
                                } catch (error) {
                                    console.error('Error loading blocked attempts:', error);
                                }
                            },

                            refreshBlockedAttempts() {
                                this.loadBlockedAttempts();
                            },

                            async saveViolationSettings() {
                                this.savingThreshold = true;
                                try {
                                    const res = await fetch('{{ route('professor.classroom.violation-settings', $session->id) }}', {
                                        method: 'PUT',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            violation_warning_threshold: this.violationWarningThreshold,
                                        }),
                                    });

                                    if (!res.ok) throw new Error('Failed to save');
                                    alert('Violation warning limit updated.');
                                } catch (error) {
                                    alert('Failed to update violation warning limit.');
                                } finally {
                                    this.savingThreshold = false;
                                }
                            },

                            formatTime(timestamp) {
                                return new Date(timestamp).toLocaleString();
                            }
                        }
                    }
                </script>

            </div>
        </main>
    </div>

    <script>
        function toggleSession() {
            fetch("{{ route('professor.sessions.toggle', $class->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json' // Tell Laravel we want JSON
                }
            })
                .then(() => {
                    // We don't even wait for the JSON parsing. 
                    // As soon as the server says 'OK', we refresh the whole page.
                    window.location.reload();
                })
                .catch(err => {
                    console.error("Session toggle failed:", err);
                    // Even if there's an error, refresh anyway to show current DB state
                    window.location.reload();
                });
        }
    </script>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>



    <script type="module">
        // Shared local server configuration options

        const localPeerOptions = {
            host: 'localhost',
            port: 9000,          // Default port for local PeerJS server
            path: '/myapp',
            secure: false,
            config: {
                iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
            },
            pingInterval: 5000,
            debug: 3
        };

        let receiverPeer = null;         // Phone 1: For receiving student screens
        let broadcastPeer = null;        // Phone 2: For sending professor screen to students
        let profLocalStream = null;
        let activeBroadcastCalls = [];
        const enrolledStudentIds = @json($class->students->pluck('id'));

        window.addEventListener('beforeunload', () => {
            if (receiverPeer) { receiverPeer.disconnect(); receiverPeer.destroy(); }
            if (broadcastPeer) { broadcastPeer.disconnect(); broadcastPeer.destroy(); }
        });

        // ================================================================
        // 🖥️ FULLSCREEN MONITORING WALL — shows every connected student
        // screen at once in a grid that auto-adjusts to how many are live.
        // ================================================================
        let wallIsOpen = false;

        // Computes a near-square grid (cols x rows) for N tiles, the same
        // way Zoom/Meet-style "gallery view" walls size themselves.
        function computeWallDimensions(count) {
            if (count <= 0) return { cols: 1, rows: 1 };
            if (count === 1) return { cols: 1, rows: 1 };
            if (count === 2) return { cols: 2, rows: 1 };
            if (count === 3) return { cols: 3, rows: 1 };
            const cols = Math.ceil(Math.sqrt(count));
            const rows = Math.ceil(count / cols);
            return { cols, rows };
        }

        // Rebuilds the wall grid from whichever students are currently
        // connected. Safe to call anytime — it's a no-op if the wall is
        // closed, and reuses the *same* MediaStream objects already
        // attached to the dashboard cards (a stream can back more than
        // one <video> element at once, so no extra bandwidth is used).
        function renderMonitoringWall() {
            const wallGrid = document.getElementById('wall-grid');
            const emptyState = document.getElementById('wall-empty-state');
            const counterLabel = document.getElementById('wall-counter-label');
            if (!wallGrid) return;

            const ids = Array.from(connectedStudents);

            // Sort students alphabetically by name
            ids.sort((a, b) => {
                const cardA = document.getElementById(`student-card-${a}`);
                const nameElA = cardA ? cardA.querySelector('[title]') : null;
                const nameA = nameElA ? nameElA.getAttribute('title') : `Student ${a}`;

                const cardB = document.getElementById(`student-card-${b}`);
                const nameElB = cardB ? cardB.querySelector('[title]') : null;
                const nameB = nameElB ? nameElB.getAttribute('title') : `Student ${b}`;

                return nameA.localeCompare(nameB);
            });

            if (counterLabel) {
                counterLabel.innerText = ids.length === 1 ? '1 connected' : `${ids.length} connected`;
            }

            // Keep tiles for students who are still connected, drop the rest,
            // add tiles for newcomers — avoids tearing down video elements
            // that don't need to change (prevents stream flicker).
            const existingTileIds = Array.from(wallGrid.children).map(el => el.dataset.studentId);

            existingTileIds.forEach(id => {
                if (!ids.includes(id)) {
                    const tile = document.getElementById(`wall-tile-${id}`);
                    if (tile) tile.remove();
                }
            });

            if (ids.length === 0) {
                wallGrid.classList.add('hidden');
                if (emptyState) emptyState.classList.remove('hidden');
                return;
            }

            wallGrid.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');

            const { cols, rows } = computeWallDimensions(ids.length);
            wallGrid.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
            wallGrid.style.gridTemplateRows = `repeat(${rows}, minmax(0, 1fr))`;

            ids.forEach(studentId => {
                let tile = document.getElementById(`wall-tile-${studentId}`);
                const sourceVideo = document.getElementById(`video-${studentId}`);
                const card = document.getElementById(`student-card-${studentId}`);
                const nameEl = card ? card.querySelector('[title]') : null;
                const fullName = nameEl ? nameEl.getAttribute('title') : `Student ${studentId}`;

                if (!tile) {
                    tile = document.createElement('div');
                    tile.id = `wall-tile-${studentId}`;
                    tile.dataset.studentId = studentId;
                    tile.className = 'relative bg-black rounded-xl overflow-hidden border border-white/10';
                    tile.innerHTML = `
                        <video id="wall-video-${studentId}" autoplay muted playsinline
                            class="w-full h-full object-cover"></video>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/85 to-transparent px-3 py-2 flex items-center justify-between">
                            <span class="text-white text-[11px] font-black truncate tracking-wide uppercase"></span>
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse flex-shrink-0 ml-2"></span>
                        </div>
                    `;
                    wallGrid.appendChild(tile);
                }

                tile.querySelector('span.truncate').innerText = fullName;

                const wallVideo = tile.querySelector('video');
                if (sourceVideo && sourceVideo.srcObject && wallVideo.srcObject !== sourceVideo.srcObject) {
                    wallVideo.srcObject = sourceVideo.srcObject;
                    wallVideo.play().catch(() => { });
                }
            });
        }

        window.openMonitoringWall = function () {
            const wall = document.getElementById('monitoring-wall');
            if (!wall) return;

            wall.classList.remove('hidden');
            wall.classList.add('flex');
            wallIsOpen = true;
            renderMonitoringWall();

            const requestFs = wall.requestFullscreen || wall.webkitRequestFullscreen;
            if (requestFs) {
                requestFs.call(wall).catch(err => console.warn('Fullscreen request denied:', err));
            }
        };

        window.closeMonitoringWall = function () {
            const wall = document.getElementById('monitoring-wall');
            if (!wall) return;

            if (document.fullscreenElement || document.webkitFullscreenElement) {
                (document.exitFullscreen || document.webkitExitFullscreen).call(document).catch(() => { });
            }

            wall.classList.add('hidden');
            wall.classList.remove('flex');
            wallIsOpen = false;
        };

        // Exiting fullscreen via Esc/browser chrome should close the wall too.
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement && wallIsOpen) {
                window.closeMonitoringWall();
            }
        });
        document.addEventListener('webkitfullscreenchange', () => {
            if (!document.webkitFullscreenElement && wallIsOpen) {
                window.closeMonitoringWall();
            }
        });

        // Re-flow tile sizing on resize/orientation change.
        window.addEventListener('resize', () => {
            if (wallIsOpen) renderMonitoringWall();
        });

        // Explicitly exposed to window scope to fix ReferenceError
        window.openFullscreenViewer = function (studentId, fullNameFormat) {
            console.log("Opening custom proctoring modal for student ID:", studentId);

            const cardVideo = document.getElementById(`video-${studentId}`);
            const modalVideo = document.getElementById('modal-video');

            if (cardVideo && cardVideo.srcObject) {
                if (modalVideo) {
                    modalVideo.srcObject = cardVideo.srcObject;
                }

                window.dispatchEvent(new CustomEvent('open-modal', {
                    detail: { name: fullNameFormat }
                }));
            } else {
                console.error(`No active proctoring stream found for: video-${studentId}`);
                alert("Cannot view screen: This student is currently connecting or offline.");
            }
        };

        // 🛠️ FIXED: Exposed to window scope to prevent ReferenceError if called from an HTML onclick button
        window.handleEndSession = function () {
            console.log("🔴 Terminating lab session. Disconnecting all local student monitors...");

            if (receiverPeer) {
                receiverPeer.disconnect();
                receiverPeer.destroy();
                receiverPeer = null;
            }

            if (broadcastPeer) {
                if (profLocalStream) {
                    profLocalStream.getTracks().forEach(track => track.stop());
                }
                activeBroadcastCalls.forEach(call => call.close());
                activeBroadcastCalls = [];
                broadcastPeer.disconnect();
                broadcastPeer.destroy();
                broadcastPeer = null;
            }

            // Reset whole grid counter state
            connectedStudents.clear();
            updateConnectedCounter();
            if (wallIsOpen) renderMonitoringWall();
            console.log("✅ Local peer hardware links closed successfully.");
        }

        // Global set to keep track of actively streaming student IDs
        let connectedStudents = new Set();

        // Helper to push counter values to view element safely
        function updateConnectedCounter() {
            const counterEl = document.getElementById('connected-counter');
            if (counterEl) {
                counterEl.innerText = connectedStudents.size;
            }
        }

        function initReceiverPeer() {
            if (receiverPeer) {
                receiverPeer.destroy();
                receiverPeer = null;
            }

            const profId = 'PROF_{{ auth()->user()->id }}';
            receiverPeer = new Peer(profId, localPeerOptions);

            receiverPeer.on('open', (id) => console.log("✅ Local Receiver Peer Ready (Fixed ID):", id));

            receiverPeer.on('call', (call) => {
                const studentId = call.metadata?.studentId || call.peer.replace('STUDENT_', '');
                console.log("📞 Incoming student monitor from:", studentId);

                call.answer();

                call.on('stream', (stream) => {
                    console.log("🟢 Stream received for student ID:", studentId);
                    const videoElement = document.getElementById(`video-${studentId}`);
                    const videoOverlay = document.getElementById(`video-overlay-${studentId}`);

                    if (videoElement) {
                        videoElement.srcObject = stream;
                        videoElement.classList.remove('hidden');

                        if (videoOverlay) {
                            videoOverlay.classList.add('hidden');
                        }

                        const statusDot = document.getElementById(`status-dot-${studentId}`);
                        if (statusDot) {
                            statusDot.classList.remove('bg-gray-300', 'bg-red-500');
                            statusDot.classList.add('bg-green-500', 'animate-pulse');
                        }

                        // UPGRADE BUTTON STATE TO ACTIVE
                        const btn = document.getElementById(`btn-${studentId}`);
                        if (btn) {
                            btn.innerText = "View Screen";
                            btn.disabled = false;
                            btn.className = "w-full text-[11px] bg-[#383838] text-white py-2 rounded-xl font-bold hover:bg-black transition shadow-sm tracking-wide uppercase cursor-pointer";
                        }

                        // UPDATE STREAM TRACKING COUNTER
                        connectedStudents.add(studentId);
                        updateConnectedCounter();
                        if (wallIsOpen) renderMonitoringWall();

                        videoElement.play().catch(err => {
                            console.error("Video playback failed:", err);
                        });
                    }
                });

                const handleStudentDisconnect = () => {
                    console.log(`🔴 Student ${studentId} has disconnected.`);

                    const videoElement = document.getElementById(`video-${studentId}`);
                    const videoOverlay = document.getElementById(`video-overlay-${studentId}`);

                    if (videoElement) {
                        videoElement.srcObject = null;
                        videoElement.classList.add('hidden');
                    }

                    if (videoOverlay) {
                        videoOverlay.classList.remove('hidden');
                        videoOverlay.innerText = "Offline";
                    }

                    const statusDot = document.getElementById(`status-dot-${studentId}`);
                    if (statusDot) {
                        statusDot.classList.remove('bg-green-500', 'animate-pulse');
                        statusDot.classList.add('bg-gray-300');
                    }

                    // REVERT BUTTON TO WAITING STATE
                    const btn = document.getElementById(`btn-${studentId}`);
                    if (btn) {
                        btn.innerText = "Waiting...";
                        btn.disabled = true;
                        btn.className = "w-full text-[11px] bg-gray-100 text-gray-400 py-2 rounded-xl font-bold cursor-not-allowed tracking-wide uppercase transition shadow-sm";
                    }

                    // REMOVE FROM STREAM TRACKING COUNTER
                    connectedStudents.delete(studentId);
                    updateConnectedCounter();
                    if (wallIsOpen) renderMonitoringWall();
                };

                call.on('close', handleStudentDisconnect);
                call.on('error', handleStudentDisconnect);
            });

            receiverPeer.on('error', (err) => {
                console.error("Receiver Peer Server Error:", err);
            });
        }

        // 🟢 BROADCASTER: Broadcasts professor's screen using an auto-generated instance ID
        function initBroadcastPeer() {
            if (broadcastPeer) {
                broadcastPeer.destroy();
                broadcastPeer = null;
            }

            broadcastPeer = new Peer(localPeerOptions);

            broadcastPeer.on('open', (id) => console.log("✅ Local Broadcast Peer Ready (Random ID):", id));
            broadcastPeer.on('disconnected', () => {
                if (broadcastPeer && !broadcastPeer.destroyed) broadcastPeer.reconnect();
            });
            broadcastPeer.on('error', (err) => console.error("Broadcast Peer Error:", err));
        }

        function resetStudentUI(studentId) {
            const video = document.getElementById('video-' + studentId);
            const overlay = document.getElementById('video-overlay-' + studentId);
            const btn = document.getElementById('btn-' + studentId);

            if (video) { video.srcObject = null; video.classList.add('hidden'); }
            if (overlay) { overlay.classList.remove('hidden'); overlay.innerText = "Offline"; }

            // Reset Button
            if (btn) {
                btn.innerText = "Waiting...";
                btn.disabled = true;
                btn.className = "w-full text-[11px] bg-gray-100 text-gray-400 py-2 rounded-xl font-bold cursor-not-allowed tracking-wide uppercase transition shadow-sm";
            }

            connectedStudents.delete(studentId);
            updateConnectedCounter();
            if (wallIsOpen) renderMonitoringWall();
        }

        // 🟢 BROADCASTING ACTIONS (Professor -> Everyone)
        window.toggleBroadcast = async function () {
            const wrapper = document.getElementById('broadcast-wrapper');
            if (!wrapper) return;
            const alpine = Alpine.$data(wrapper);

            if (!alpine.isBroadcasting) {
                if (!broadcastPeer || broadcastPeer.destroyed) {
                    initBroadcastPeer();
                    await new Promise(resolve => setTimeout(resolve, 1500));
                } else if (broadcastPeer.disconnected) {
                    broadcastPeer.reconnect();
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }

                try {
                    profLocalStream = await navigator.mediaDevices.getDisplayMedia({
                        video: { cursor: "always" }, audio: false
                    });

                    activeBroadcastCalls = [];
                    enrolledStudentIds.forEach(studentId => {
                        const targetPeerId = 'STUDENT_' + studentId;
                        console.log("📡 Broadcasting lecture feed to:", targetPeerId);

                        const call = broadcastPeer.call(targetPeerId, profLocalStream);
                        if (call) {
                            activeBroadcastCalls.push(call);
                            call.on('error', err => console.error("Broadcast call error:", err));
                        }
                    });

                    profLocalStream.getVideoTracks()[0].onended = function () {
                        if (alpine.isBroadcasting) window.toggleBroadcast();
                    };

                } catch (err) {
                    console.error("Screen capture failed:", err);
                    return;
                }
            } else {
                if (profLocalStream) profLocalStream.getTracks().forEach(track => track.stop());
                activeBroadcastCalls.forEach(call => call.close());
                activeBroadcastCalls = [];
            }

            fetch("{{ route('professor.sessions.broadcast', $class->id) }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => {
                    document.querySelectorAll('[x-data]').forEach(el => {
                        const d = Alpine.$data(el);
                        if (d.isBroadcasting !== undefined) d.isBroadcasting = data.is_broadcasting;
                    });
                });
        }

        // 🛠️ FIXED: Added page-load initialization trigger.
        // Since modules are deferred by default, we can trigger initialization immediately.
        initReceiverPeer();
        initBroadcastPeer();

    </script>

    <script>
        // 🟢 1. Silent Refresh with Alpine.js support
        async function silentRefresh() {
            try {
                const response = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Helper to update and re-initialize Alpine components
                const updateContainer = (id) => {
                    const newContent = doc.getElementById(id);
                    const target = document.getElementById(id);
                    if (newContent && target) {
                        target.innerHTML = newContent.innerHTML;
                        // 🟢 CRITICAL: This makes the new buttons clickable again!
                        Alpine.initTree(target);
                    }
                };

                updateContainer('tasks-list-container');
                updateContainer('quizzes-list-container');
                updateContainer('materials-list-container'); // Add this if needed

            } catch (err) {
                console.error("Silent refresh failed:", err);
            }
        }

        // Polling (Every 3 seconds)
        setInterval(silentRefresh, 3000);

        // 🟢 2. Corrected submitAjaxForm
        function submitAjaxForm(event, closeAlpineModal) {
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';
            btn.disabled = true;

            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(async res => {
                    if (res.ok) {
                        closeAlpineModal();
                        form.reset();
                        await silentRefresh(); // Update UI immediately after success
                    } else {
                        const data = await res.json();
                        alert(data.message || "Something went wrong.");
                    }
                })
                .catch(err => console.error("Upload failed:", err))
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        // 🟢 4. The function that grades submissions silently
        function submitGrade(event) {
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML; // Saves the floppy disk icon
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Show loading state
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>';
            btn.disabled = true;

            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(async res => {
                    if (res.ok) {
                        // Success! Show a green checkmark briefly
                        btn.innerHTML = '<i class="ri-check-double-line"></i>';
                        btn.classList.remove('bg-[#383838]', 'hover:bg-black');
                        btn.classList.add('bg-green-500');

                        // Revert back to normal after 1.5 seconds
                        setTimeout(() => {
                            btn.innerHTML = originalContent;
                            btn.classList.remove('bg-green-500');
                            btn.classList.add('bg-[#383838]', 'hover:bg-black');
                            btn.disabled = false;
                        }, 1500);

                    } else {
                        const data = await res.json();
                        alert(data.message || "Failed to save grade.");
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error("Grading failed:", err);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
        }
    </script>

    @php
        $studentsData = $class->students->map(function ($s) use ($session) {
            return [
                'id' => $s->id,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'middle_name' => $s->middle_name,
                'school_id' => $s->school_id,
                'is_present' => (bool) ($s->pivot->is_present ?? false),
                'is_screen_blocked' => (bool) ($s->pivot->is_screen_blocked ?? false),
                'violation_count' => (int) ($s->pivot->violation_count ?? 0),
                'attendances' => $s->attendances ?? []
            ];
        });
    @endphp

    <script>
        function studentManager() {
            return {
                // Hydrate initial state cleanly from Blade variable
                students: @json($studentsData),
                warningThreshold: {{ $session->violation_warning_threshold ?? config('lmms.violation_warning_threshold', 3) }},
                logModalOpen: false,
                pollInterval: null,

                // Modal state variables
                selectedUserName: '',
                selectedUserId: null,
                selectedClassId: null,
                selectedAttendances: [],
                logs: [],
                studentFiles: [],
                modalTab: 'logs',
                loading: false,
                logRefreshInterval: null,

                init() {
                    // Poll for student status updates every 3 seconds
                    this.pollInterval = setInterval(() => this.fetchStudentStatuses(), 3000);
                },

                destroy() {
                    if (this.pollInterval) clearInterval(this.pollInterval);
                    this.stopActivityRefresh();
                },

                async fetchStudentStatuses() {
                    try {
                        let response = await fetch('/professor/classroom/{{ $class->id }}/students-status');
                        if (response.ok) {
                            let data = await response.json();

                            data.forEach(updatedStudent => {
                                let student = this.students.find(s => s.id === updatedStudent.id);
                                if (student) {
                                    student.is_screen_blocked = updatedStudent.is_screen_blocked;
                                    student.violation_count = updatedStudent.violation_count;
                                    student.is_present = updatedStudent.is_present;
                                }
                            });
                        }
                    } catch (err) {
                        console.error('Failed to sync student statuses:', err);
                    }
                },

                async unblockStudent(studentId) {
                    try {
                        let response = await fetch(`/professor/classroom/{{ $class->id }}/students/${studentId}/unblock`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            let student = this.students.find(s => s.id === studentId);
                            if (student) {
                                student.is_screen_blocked = false;
                            }
                        } else {
                            alert('Failed to unblock student. Please try again.');
                        }
                    } catch (err) {
                        console.error('Error during unblock request:', err);
                    }
                },

                async fetchStudentWorkspace(userId, classId, attendances = [], showLoader = false) {
                    if (showLoader) {
                        this.loading = true;
                    }
                    try {
                        const [logsRes, filesRes] = await Promise.all([
                            fetch(`/professor/students/${userId}/activity-logs/${classId}`),
                            fetch(`/professor/students/${userId}/files/${classId}`)
                        ]);

                        if (logsRes.ok) {
                            let fetchedLogs = await logsRes.json();
                            if (attendances && attendances.length > 0) {
                                attendances.forEach(att => {
                                    fetchedLogs.push({
                                        id: 'att-' + att.id,
                                        log_type: 'attendance',
                                        content: 'Official Attendance Marked',
                                        class_name: (att.lab_session ? att.lab_session.subject_name : null) || (att.labSession ? att.labSession.subject_name : null) || 'Academic Session',
                                        duration_seconds: 0,
                                        created_at: `${att.attendance_date} ${att.joined_at}`
                                    });
                                });
                            }
                            this.logs = fetchedLogs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                        }

                        if (filesRes.ok) {
                            let fetchedFiles = await filesRes.json();
                            this.studentFiles = fetchedFiles.sort((a, b) => b.id - a.id);
                        }

                    } catch (e) {
                        console.error("Fetch Execution Failed", e);
                    } finally {
                        if (showLoader) {
                            this.loading = false;
                        }
                    }
                },

                async viewStudentActivity(userId, firstName, lastName, attendances, classId) {
                    this.selectedUserName = `${lastName}, ${firstName}`;
                    this.selectedUserId = userId;
                    this.selectedClassId = classId;
                    this.selectedAttendances = attendances || [];
                    this.logModalOpen = true;
                    this.logs = [];
                    this.studentFiles = [];
                    this.modalTab = 'logs';
                    this.stopActivityRefresh();

                    await this.fetchStudentWorkspace(userId, classId, this.selectedAttendances, true);
                    this.logRefreshInterval = setInterval(() => {
                        if (this.logModalOpen && this.selectedUserId && this.selectedClassId) {
                            this.fetchStudentWorkspace(this.selectedUserId, this.selectedClassId, this.selectedAttendances);
                        }
                    }, 3000);
                },

                closeActivityModal() {
                    this.logModalOpen = false;
                    this.selectedUserId = null;
                    this.selectedClassId = null;
                    this.selectedAttendances = [];
                    this.stopActivityRefresh();
                },

                stopActivityRefresh() {
                    if (this.logRefreshInterval) {
                        clearInterval(this.logRefreshInterval);
                        this.logRefreshInterval = null;
                    }
                },

                get groupedLogs() {
                    return this.logs.reduce((groups, log) => {
                        const date = log.created_at.split(/[ T]/)[0];
                        if (!groups[date]) { groups[date] = []; }
                        groups[date].push(log);
                        return groups;
                    }, {});
                },

                formatDateHeader(dateStr) {
                    const today = new Date().toISOString().split('T')[0];
                    const yesterdayDate = new Date();
                    yesterdayDate.setDate(yesterdayDate.getDate() - 1);
                    const yesterday = yesterdayDate.toISOString().split('T')[0];
                    if (dateStr === today) return 'Today';
                    if (dateStr === yesterday) return 'Yesterday';
                    return new Date(dateStr).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                },

                formatTime(dateStr) {
                    return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                },

                getIcon(type) {
                    const icons = {
                        'attendance': 'ri-checkbox-circle-line',
                        'navigation': 'ri-global-line',
                        'submission': 'ri-file-upload-line',
                        'material': 'ri-book-open-line',
                        'quiz': 'ri-task-line'
                    };
                    return icons[type] || 'ri-cursor-line';
                },

                getIconClass(type) {
                    const classes = {
                        'attendance': 'bg-green-50 text-green-600 border border-green-200',
                        'navigation': 'bg-amber-50 text-amber-600 border border-amber-200',
                        'submission': 'bg-blue-50 text-blue-600 border border-blue-200',
                        'material': 'bg-purple-50 text-purple-600 border border-purple-200',
                        'quiz': 'bg-indigo-50 text-indigo-600 border border-indigo-200'
                    };
                    return classes[type] || 'bg-gray-100 text-gray-600 border-gray-200';
                }
            };
        }
    </script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('taskManager', (initialTasks = [], subjectId = null) => ({
                taskList: initialTasks,
                subjectId: subjectId,
                showEditorModal: false,
                showGradingModal: false,
                isEditing: false,
                editingTaskId: null,
                saving: false,

                taskForm: {
                    title: '',
                    deadline: '',
                    description: ''
                },

                criteria: [],
                gradingTask: null,
                submissions: [],
                aiGradingEnabled: true,
                searchQuery: '',
                sortBy: 'name_asc',
                pollInterval: null,

                // 1. AUTO-INITIALIZE BACKGROUND POLLING (Every 4 seconds)
                init() {
                    if (this.subjectId) {
                        // Poll every 4 seconds without touching the DOM or WebRTC stream
                        this.pollInterval = setInterval(() => {
                            this.refreshTasks();
                        }, 4000);
                    }
                },

                // 2. SILENT BACKGROUND FETCH & REACTIVE STATE MERGE
                async refreshTasks() {
                    if (!this.subjectId) return;

                    try {
                        const response = await fetch(`/professor/sessions/${this.subjectId}/tasks-json`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) return;
                        const freshTasks = await response.json();

                        // Merge updated counters and submission lists into existing state
                        freshTasks.forEach(freshTask => {
                            const existingIndex = this.taskList.findIndex(t => t.id === freshTask.id);

                            if (existingIndex !== -1) {
                                // Update counter and submission array reactively
                                this.taskList[existingIndex].submissions_count = freshTask.submissions_count;
                                this.taskList[existingIndex].submissions = freshTask.submissions;

                                // If the grading modal is currently open for this task, dynamically sync live submissions table
                                if (this.showGradingModal && this.gradingTask && this.gradingTask.id === freshTask.id) {
                                    this.submissions = freshTask.submissions || [];
                                }
                            } else {
                                // Dynamically append new task if created elsewhere
                                this.taskList.unshift(freshTask);
                            }
                        });
                    } catch (err) {
                        console.error('Silent refresh failed:', err);
                    }
                },

                get computedMaxPoints() {
                    if (this.criteria.length === 0) return 0;
                    return this.criteria.reduce((total, c) => total + this.getMaxPoints(c), 0);
                },

                getMaxPoints(criterion) {
                    if (!criterion.levels || criterion.levels.length === 0) return 0;
                    return Math.max(...criterion.levels.map(l => parseInt(l.points) || 0));
                },

                openEditor(task = null, rubric = null, criteria = []) {
                    if (task) {
                        this.isEditing = true;
                        this.editingTaskId = task.id;

                        let formattedDeadline = '';
                        if (task.deadline) {
                            const d = new Date(task.deadline);
                            if (!isNaN(d.getTime())) {
                                formattedDeadline = d.toISOString().slice(0, 16);
                            } else {
                                formattedDeadline = String(task.deadline).replace(' ', 'T').slice(0, 16);
                            }
                        }

                        this.taskForm = {
                            title: task.title || '',
                            deadline: formattedDeadline,
                            description: task.description || ''
                        };

                        if (criteria && criteria.length > 0) {
                            this.criteria = JSON.parse(JSON.stringify(criteria));
                            let uidCounter = 1000;
                            this.criteria.forEach(c => {
                                if (!c.uid) c.uid = uidCounter++;
                                if (c.levels) {
                                    c.levels.forEach(l => { if (!l.uid) l.uid = uidCounter++; });
                                }
                            });
                        } else {
                            this.resetCriteria();
                        }
                    } else {
                        this.isEditing = false;
                        this.editingTaskId = null;
                        this.taskForm = { title: '', deadline: '', description: '' };
                        this.resetCriteria();
                    }
                    this.showEditorModal = true;
                },

                resetCriteria() {
                    this.criteria = [{
                        uid: Date.now(),
                        name: 'General Criteria',
                        description: '',
                        levels: [
                            { uid: Date.now() + 1, label: 'Excellent', points: 10, description: '' },
                            { uid: Date.now() + 2, label: 'Needs Improvement', points: 5, description: '' }
                        ]
                    }];
                },

                addCriterion() {
                    this.criteria.push({
                        uid: Date.now(),
                        name: '',
                        description: '',
                        levels: [
                            { uid: Date.now() + 1, label: 'Excellent', points: 10, description: '' }
                        ]
                    });
                },

                removeCriterion(uid) {
                    if (this.criteria.length > 1) {
                        this.criteria = this.criteria.filter(c => c.uid !== uid);
                    }
                },

                addLevel(criterion) {
                    criterion.levels.push({
                        uid: Date.now(),
                        label: '',
                        points: 0,
                        description: ''
                    });
                },

                removeLevel(criterion, levelUid) {
                    if (criterion.levels.length > 1) {
                        criterion.levels = criterion.levels.filter(l => l.uid !== levelUid);
                    }
                },

                saveTask() {
                    this.saving = true;

                    const formattedCriteria = this.criteria.map(c => ({
                        name: c.name || c.criterion_name || 'Unnamed Criterion',
                        description: c.description || '',
                        levels: (c.levels || []).map(l => ({
                            label: l.label || '',
                            points: parseInt(l.points) || 0,
                            description: l.description || ''
                        }))
                    }));

                    const payload = {
                        subject_id: this.subjectId,
                        title: this.taskForm.title,
                        deadline: this.taskForm.deadline,
                        description: this.taskForm.description,
                        points: this.computedMaxPoints,
                        rubric: {
                            name: `${this.taskForm.title} Rubric`,
                            criteria_json: JSON.stringify(formattedCriteria)
                        }
                    };

                    const url = this.isEditing
                        ? `/professor/tasks/${this.editingTaskId}`
                        : '/professor/tasks';

                    fetch(url, {
                        method: this.isEditing ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    })
                        .then(async res => {
                            const data = await res.json();
                            if (!res.ok) throw data;
                            return data;
                        })
                        .then(data => {
                            this.saving = false;
                            const savedTask = data.task || data;

                            if (this.isEditing) {
                                const index = this.taskList.findIndex(t => t.id === this.editingTaskId || t.id === savedTask.id);
                                if (index !== -1) {
                                    this.taskList.splice(index, 1, savedTask);
                                }
                            } else {
                                this.taskList.unshift(savedTask);
                            }

                            this.showEditorModal = false;
                        })
                        .catch(err => {
                            this.saving = false;
                            console.error('Save error:', err);
                            if (err.errors) {
                                alert(Object.values(err.errors).flat().join('\n'));
                            } else {
                                alert(err.message || 'Failed to save task.');
                            }
                        });
                },

                async deleteTask(taskId) {
                    if (!confirm('Are you sure you want to delete this task?')) return;

                    try {
                        const response = await fetch(`/professor/tasks/${taskId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ _method: 'DELETE' })
                        });

                        if (response.ok) {
                            this.taskList = this.taskList.filter(t => t.id !== taskId);
                        } else {
                            alert('Failed to delete task.');
                        }
                    } catch (err) {
                        console.error('Delete Task Error:', err);
                    }
                },

                openGrading(task, submissions) {
                    this.gradingTask = task;
                    this.submissions = submissions || [];
                    if (task.ai_grading_enabled !== undefined) {
                        this.aiGradingEnabled = Boolean(task.ai_grading_enabled);
                    }
                    this.searchQuery = '';
                    this.showGradingModal = true;
                },

                async toggleAiGrading() {
                    // 1. Instantly flip UI state
                    this.aiGradingEnabled = !this.aiGradingEnabled;

                    if (!this.gradingTask) return;

                    // 2. Persist to DB via AJAX
                    try {
                        const res = await fetch(`/professor/tasks/${this.gradingTask.id}/toggle-ai`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ ai_grading_enabled: this.aiGradingEnabled })
                        });

                        if (!res.ok) throw new Error('Failed to save state');
                    } catch (err) {
                        console.error('Failed to update AI status in DB:', err);
                        // Rollback toggle state on error
                        this.aiGradingEnabled = !this.aiGradingEnabled;
                    }
                },

                selectCriterionLevel(sub, criterionIndex, points) {
                    // 1. Initialize temporary selection tracking on the submission object
                    if (!sub.selectedCriterionScores) {
                        sub.selectedCriterionScores = {};
                    }

                    // 2. Assign selected points for this criterion index
                    sub.selectedCriterionScores[criterionIndex] = parseInt(points) || 0;

                    // 3. Automatically update sub.grade with the total sum
                    sub.grade = Object.values(sub.selectedCriterionScores).reduce((sum, pts) => sum + pts, 0);
                },

                isCriterionLevelSelected(sub, criterionIndex, points) {
                    return sub.selectedCriterionScores && sub.selectedCriterionScores[criterionIndex] === parseInt(points);
                },

                async regradeSubmission(sub, event) {
                    const btn = event.currentTarget;
                    const originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Analyzing...';

                    try {
                        const res = await fetch(`/professor/submissions/${sub.id}/regrade`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                            }
                        });
                        const data = await res.json();

                        if (data.success) {
                            btn.innerHTML = '<i class="ri-check-line"></i> Success';
                            btn.classList.replace('text-[#383838]', 'text-green-700');
                            btn.classList.replace('bg-gray-100', 'bg-green-100');

                            sub.grade = data.total_score;
                            sub.auto_graded = true;
                            sub.submission_grade = data.submission_grade;

                            setTimeout(() => {
                                btn.innerHTML = originalHtml;
                                btn.classList.replace('text-green-700', 'text-[#383838]');
                                btn.classList.replace('bg-green-100', 'bg-gray-100');
                                btn.disabled = false;
                            }, 2000);
                        } else {
                            throw new Error(data.message);
                        }
                    } catch (err) {
                        alert('Grading failed: ' + err.message);
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                },

                async submitGrade(sub, event) {
                    const form = event.target;
                    const btn = form.querySelector('button[type="submit"]');
                    const originalHtml = btn.innerHTML;

                    btn.disabled = true;
                    btn.innerHTML = '<i class="ri-loader-4-line animate-spin text-lg"></i>';

                    try {
                        const formData = new FormData(form);
                        const res = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        if (res.ok) {
                            btn.innerHTML = '<i class="ri-check-double-line text-lg"></i>';
                            btn.classList.replace('bg-[#383838]', 'bg-green-500');

                            sub.grade = formData.get('grade');
                            sub.feedback = formData.get('feedback');
                            sub.auto_graded = false;

                            setTimeout(() => {
                                btn.innerHTML = originalHtml;
                                btn.classList.replace('bg-green-500', 'bg-[#383838]');
                                btn.disabled = false;
                            }, 2000);
                        } else {
                            throw new Error("Failed to save.");
                        }
                    } catch (err) {
                        alert('Save failed.');
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    }
                },

                get filteredSubmissions() {
                    let result = [...this.submissions];

                    if (this.searchQuery.trim() !== '') {
                        const q = this.searchQuery.toLowerCase();
                        result = result.filter(sub => {
                            const fullName = `${sub.user?.first_name || ''} ${sub.user?.last_name || ''}`.toLowerCase();
                            return fullName.includes(q);
                        });
                    }

                    return result.sort((a, b) => {
                        const nameA = `${a.user?.last_name || ''} ${a.user?.first_name || ''}`.toLowerCase();
                        const nameB = `${b.user?.last_name || ''} ${b.user?.first_name || ''}`.toLowerCase();
                        const scoreA = parseFloat(a.grade) || 0;
                        const scoreB = parseFloat(b.grade) || 0;

                        if (this.sortBy === 'name_asc') return nameA.localeCompare(nameB);
                        if (this.sortBy === 'name_desc') return nameB.localeCompare(nameA);
                        if (this.sortBy === 'score_desc') return scoreB - scoreA;
                        if (this.sortBy === 'score_asc') return scoreA - scoreB;

                        return 0;
                    });
                },

                formatDate(dateStr) {
                    if (!dateStr) return 'No Date Recorded';
                    const d = new Date(dateStr);
                    if (isNaN(d.getTime())) return dateStr;
                    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
                },

                formatDuration(val) {
                    if (val === null || val === undefined || val === '') return '--';

                    const num = parseInt(val);
                    if (isNaN(num)) return val;

                    if (num < 60) {
                        return `${num}s`;
                    }

                    const mins = Math.floor(num / 60);
                    const secs = num % 60;

                    if (mins >= 60) {
                        const hrs = Math.floor(mins / 60);
                        const remMins = mins % 60;
                        return secs > 0 ? `${hrs}h ${remMins}m ${secs}s` : `${hrs}h ${remMins}m`;
                    }

                    return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`;
                }
            }));
        });
    </script>
</x-app-layout>