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

                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 mt-4">Platform
                        Support</div>

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

                                    <button type="button" title="Assign Live Task"
                                        @click.prevent="$dispatch('open-task-modal')"
                                        class="w-12 h-12 rounded-full flex items-center justify-center text-gray-600 bg-gray-50 hover:bg-purple-50 border border-gray-200 hover:border-purple-300 hover:text-purple-600 transition-all duration-200 transform hover:scale-105 text-xl">
                                        <i class="ri-add-line"></i>
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
                        <div x-show="activeTab === 'monitoring'">
                            <div x-data="{ 
                                     showTaskModal: false,
                                     taskTitle: '',
                                     taskDesc: '',
                                     taskDeadline: '',
                                     taskPoints: 100,
                                     submitTask() {
                                         fetch('/professor/classroom/{{ $class->id }}/live-tasks', {
                                             method: 'POST',
                                             headers: {
                                                 'Content-Type': 'application/json',
                                                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                             },
                                             body: JSON.stringify({ 
                                                 title: this.taskTitle, 
                                                 description: this.taskDesc,
                                                 deadline: this.taskDeadline,
                                                 points: this.taskPoints
                                             })
                                         })
                                         .then(res => {
                                             if(res.ok) {
                                                 this.showTaskModal = false; 
                                                 this.taskTitle = ''; 
                                                 this.taskDesc = '';
                                                 this.taskDeadline = '';
                                                 this.taskPoints = 100;
                                                 alert('Task pushed to all students instantly!');
                                                 silentRefresh();
                                             }
                                         })
                                         .catch(err => console.error('Failed to assign task:', err));
                                     }
                                 }" @open-task-modal.window="showTaskModal = true">

                                <div x-show="showTaskModal" style="display: none;"
                                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
                                    <div class="bg-white w-1/3 p-6 rounded-2xl shadow-2xl"
                                        @click.away="showTaskModal = false">
                                        <h2 class="text-2xl font-black text-gray-800 mb-4">Assign Live Task</h2>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 mb-1">Task
                                                    Title</label>
                                                <input type="text" x-model="taskTitle"
                                                    placeholder="e.g. Create a Laravel Route"
                                                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-purple-500 focus:border-purple-500">
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-sm font-bold text-gray-700 mb-1">Deadline</label>
                                                    <input type="datetime-local" x-model="taskDeadline"
                                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-purple-500 focus:border-purple-500"
                                                        required>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-bold text-gray-700 mb-1">Max
                                                        Points</label>
                                                    <input type="number" x-model="taskPoints"
                                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-purple-500 focus:border-purple-500"
                                                        required>
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-bold text-gray-700 mb-1">Instructions</label>
                                                <textarea x-model="taskDesc"
                                                    placeholder="Describe what the students need to do..."
                                                    class="w-full border border-gray-300 rounded-lg p-3 h-28 focus:ring-purple-500 focus:border-purple-500"></textarea>
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-3 mt-6">
                                            <button @click.prevent="showTaskModal = false" type="button"
                                                class="px-5 py-2 font-bold text-gray-500 hover:text-gray-800">Cancel</button>
                                            <button @click.prevent="submitTask()" type="button"
                                                class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition-all">Push
                                                to Students</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($session->is_active)
                                <div class="flex justify-between items-center mb-8 ms-4 me-4">
                                    <div>
                                        <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">LIVE
                                            MONITORING</h2>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Manage
                                            student's screen</p>
                                    </div>
                                    <button id="refresh-monitor-btn" class="p-2 hover:bg-gray-100 rounded-lg transition"
                                        title="Refresh student list">
                                        <i class="ri-refresh-line text-xl"></i>
                                    </button>
                                </div>
                                @include('professor.partials.monitor-grid', ['activeStudents' => $activeStudents])
                            @else
                                <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed">
                                    <p class="text-gray-500 font-bold">Session is currently offline. Click "Start Session"
                                        to allow students to join.</p>
                                </div>
                            @endif
                        </div>

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
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Manage
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
                                        <p class="text-gray-400 font-bold text-sm">No learning materials have been posted
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
                                                        <p class="text-sm font-bold text-gray-800 mb-4">Direct inline
                                                            web rendering is unavailable for direct PowerPoint formats.
                                                        </p>
                                                        <a :href="previewUrl" download
                                                            class="inline-block px-6 py-3 bg-[#383838] text-white font-black silverware uppercase text-[10px] tracking-widest rounded-xl hover:bg-black transition-all">
                                                            <i class="ri-download-cloud-line mr-1 text-sm"></i> Download
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
                                                                            x-text="log.student_name"></div>
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

               <div x-show="activeTab === 'tasks'" class="space-y-6" x-cloak x-data="taskManager()">

    <div>
        <div class="flex justify-between items-center mb-8 ms-4 me-4">
            <div>
                <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Laboratory Tasks</h2>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Manage activities, rubrics, and grades</p>
            </div>
            <button @click="openEditor()"
                class="bg-[#383838] text-white px-6 py-3 rounded-xl font-black uppercase text-[10px] hover:bg-black transition-all shadow-md active:scale-95 tracking-widest flex items-center gap-2">
                <i class="ri-add-line text-sm"></i> Create New Task
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($tasks as $task)
                <div class="bg-white p-6 rounded-3xl border border-gray-100 flex flex-col justify-between shadow-sm hover:shadow-md hover:border-[#383838] transition-all group">
                    <div>
                        <div class="flex justify-between items-start mb-5">
                            <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-[#383838] group-hover:text-white transition-colors duration-300">
                                <i class="ri-flask-line text-xl"></i>
                            </div>
                            <span class="bg-gray-100 text-[#383838] px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border border-gray-200">
                                {{ $task->points ?? 0 }} PTS
                            </span>
                        </div>
                        <h4 class="font-black text-gray-900 text-lg mb-2 leading-tight">{{ $task->title }}</h4>
                        <div class="space-y-2 mt-3 mb-6">
                            <div class="flex items-center text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                <i class="ri-time-line mr-2 text-gray-300"></i>
                                {{ \Carbon\Carbon::parse($task->deadline)->format('M d, Y h:i A') }}
                            </div>
                            <div class="flex items-center text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                <i class="ri-group-line mr-2 text-gray-300"></i>
                                {{ $task->submissions->count() }} Submissions
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto grid grid-cols-2 gap-3">
                       @php
                            // 🟢 Reconstruct relational DB rules into Alpine JSON map
                            $alpineCriteria = [];
                            if ($task->rubric && $task->rubric->criteria) {
                                $uid = 1; $levelUid = 1000;
                                foreach ($task->rubric->criteria as $c) {
                                    $levelsWithUids = [];
                                    foreach ($c->checking_rules['levels'] ?? [] as $lvl) {
                                        $levelsWithUids[] = [
                                            'uid' => $levelUid++,
                                            'label' => $lvl['label'] ?? 'Level',
                                            'points' => (int)($lvl['points'] ?? 0),
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
                        @endphp
                        
                        <button @click="openEditor({{ json_encode($task) }}, {{ json_encode($task->rubric) }}, {{ json_encode($alpineCriteria) }})" 
                                class="w-full bg-white border-2 border-gray-100 text-gray-600 py-3 rounded-xl text-[10px] font-black uppercase hover:border-[#383838] hover:text-[#383838] transition-all tracking-widest flex items-center justify-center gap-1.5">
                            <i class="ri-pencil-line text-sm"></i> Edit Setup
                        </button>

                        <button @click="openGrading({{ json_encode($task) }}, {{ json_encode($task->submissions()->with(['user', 'submissionGrade.criterionScores.criterion'])->get()) }})" 
                                class="w-full bg-[#383838] text-white py-3 rounded-xl text-[10px] font-black uppercase hover:bg-black transition-all tracking-widest shadow-sm flex items-center justify-center gap-1.5"> 
                            <i class="ri-check-double-line text-sm"></i> Grade 
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-24 border-2 border-dashed border-gray-200 rounded-[2rem] text-center bg-gray-50/50">
                    <i class="ri-inbox-2-line text-5xl text-gray-300 mb-4 block"></i>
                    <p class="text-gray-500 font-black uppercase tracking-widest text-xs">No laboratory tasks created yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="showEditorModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4" x-cloak x-transition.opacity>
            <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col overflow-hidden transform transition-all" @click.away="showEditorModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-4">
                        <div>
                            <h3 class="font-black text-xl text-gray-900 uppercase tracking-tight" x-text="isEditing ? 'Edit Laboratory Task' : 'Create New Task'"></h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Configure details & scoring rubric</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="text-right hidden sm:block border-r border-gray-200 pr-6">
                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Auto-Calculated Max Points</p>
                            <p class="text-2xl font-black text-[#383838]" x-text="computedMaxPoints"></p>
                        </div>
                        <button @click="saveTask()" class="bg-[#383838] hover:bg-black text-white px-8 py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-md transition flex items-center gap-2">
                            <i class="ri-save-line text-sm"></i> <span x-text="isEditing ? 'Update Task' : 'Save & Publish'"></span>
                        </button>
                        <button @click="showEditorModal = false" class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-100 transition text-gray-600">
                            <i class="ri-close-line text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-white">
                    <form id="unified-task-form" class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-4 space-y-6">
                            <div class="bg-gray-50 rounded-3xl p-6 border border-gray-100">
                                <h4 class="font-black text-[#383838] uppercase tracking-widest text-xs mb-5 flex items-center gap-2">
                                    <i class="ri-information-line"></i> Task Details
                                </h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 px-1">Activity Title *</label>
                                        <input type="text" x-model="taskForm.title" placeholder="e.g., Python Basics Lab" class="w-full border-gray-200 bg-white rounded-xl p-3.5 text-sm font-bold focus:ring-2 focus:ring-[#383838] focus:border-[#383838] outline-none transition-all shadow-sm">
                                    </div>
                                    
                                    <div>
                                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 px-1">Deadline *</label>
                                        <input type="datetime-local" x-model="taskForm.deadline" class="w-full border-gray-200 bg-white rounded-xl p-3.5 text-sm font-bold focus:ring-2 focus:ring-[#383838] outline-none transition-all shadow-sm text-gray-600">
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 px-1">Instructions</label>
                                        <textarea x-model="taskForm.description" rows="5" placeholder="Provide clear instructions for the students..." class="w-full border-gray-200 bg-white rounded-xl p-3.5 text-sm focus:ring-2 focus:ring-[#383838] outline-none transition-all shadow-sm resize-none"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-8 space-y-4">
                            <h4 class="font-black text-[#383838] uppercase tracking-widest text-xs mb-2 flex items-center gap-2 px-1">
                                <i class="ri-list-check-2"></i> Grading Criteria (Rubric)
                            </h4>
                            
                            <template x-for="(criterion, cIdx) in criteria" :key="criterion.uid">
                                <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-sm transition-all focus-within:border-[#383838]">
                                    <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 bg-gray-50">
                                        <div class="w-7 h-7 rounded-full bg-[#383838] text-white flex items-center justify-center text-[10px] font-black shrink-0" x-text="cIdx + 1"></div>
                                        <input x-model="criterion.name" type="text" placeholder="Criterion Name (e.g., Code Logic)" class="flex-1 bg-transparent border-b-2 border-dashed border-gray-300 focus:border-[#383838] outline-none text-sm font-black text-gray-900 py-1 transition">
                                        <div class="flex items-center gap-4 shrink-0">
                                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Max: <span class="text-[#383838] text-sm" x-text="getMaxPoints(criterion)"></span></span>
                                            <button @click="removeCriterion(criterion.uid)" x-show="criteria.length > 1" class="text-gray-400 hover:text-red-500 transition p-1.5 rounded-lg hover:bg-red-50">
                                                <i class="ri-delete-bin-line text-lg"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="p-5 overflow-x-auto">
                                        <div class="flex gap-4 min-w-max pb-2">
                                            <template x-for="(level, lIdx) in criterion.levels" :key="level.uid">
                                                <div class="w-64 border border-gray-200 rounded-2xl overflow-hidden bg-white hover:border-gray-400 transition group flex flex-col shadow-sm">
                                                    <div class="border-b border-gray-100 p-3 bg-gray-50">
                                                        <input x-model="level.label" type="text" placeholder="Level label" class="w-full text-xs font-black text-gray-800 bg-transparent border-none outline-none mb-2 p-0 focus:ring-0">
                                                        <div class="flex items-center gap-2">
                                                            <input x-model.number="level.points" type="number" min="0" class="w-16 text-sm font-black text-[#383838] bg-white border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-1 focus:ring-[#383838] outline-none text-center">
                                                            <span class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Points</span>
                                                        </div>
                                                    </div>
                                                    <div class="p-3 flex-grow bg-white">
                                                        <textarea x-model="level.description" placeholder="Requirements for this level..." rows="3" class="w-full text-xs text-gray-600 bg-transparent border-none outline-none resize-none p-0 focus:ring-0"></textarea>
                                                    </div>
                                                    <div class="px-3 pb-3 text-right bg-white">
                                                        <button @click="removeLevel(criterion, level.uid)" x-show="criterion.levels.length > 1" class="text-[9px] text-gray-400 hover:text-red-500 font-black uppercase tracking-widest">Remove</button>
                                                    </div>
                                                </div>
                                            </template>
                                            <div class="w-40 flex-shrink-0">
                                                <button @click="addLevel(criterion)" class="w-full h-full min-h-[160px] border-2 border-dashed border-gray-200 rounded-2xl text-gray-400 hover:border-[#383838] hover:text-[#383838] transition flex flex-col items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest bg-gray-50/50 hover:bg-gray-50">
                                                    <i class="ri-add-circle-line text-2xl"></i> Add Level
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-5 pb-5 border-t border-gray-100 pt-4 bg-white">
                                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                                            <i class="ri-robot-line text-sm text-[#383838]"></i> AI Context Instructions (Optional)
                                        </label>
                                        <textarea x-model="criterion.description" placeholder="Tell the AI exactly what to check for regarding this criterion..." rows="2" class="w-full text-xs border-gray-200 bg-gray-50 rounded-xl p-3 resize-none focus:ring-2 focus:ring-[#383838] outline-none transition"></textarea>
                                    </div>
                                </div>
                            </template>

                            <button @click="addCriterion()" class="w-full py-5 border-2 border-dashed border-gray-200 rounded-3xl text-gray-500 hover:border-[#383838] hover:text-[#383838] font-black text-[10px] uppercase tracking-widest transition flex items-center justify-center gap-2 bg-gray-50 hover:bg-gray-100">
                                <i class="ri-add-circle-fill text-xl"></i> Add New Criterion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

   <template x-teleport="body">
    <div x-show="showGradingModal" class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4" x-cloak x-transition.opacity>
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-7xl h-[90vh] flex flex-col overflow-hidden transform transition-all" @click.away="showGradingModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
            
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-4">
                    <div>
                        <h3 class="font-black text-xl text-gray-900 uppercase tracking-tight" x-text="gradingTask?.title"></h3>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Reviewing Submissions</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3 bg-white border border-gray-200 px-4 py-2.5 rounded-2xl shadow-sm">
                        <p class="text-xs font-black text-[#383838] uppercase tracking-widest">Gemini Auto-Grader</p>
                        <button @click="aiGradingEnabled = !aiGradingEnabled" :class="aiGradingEnabled ? 'bg-[#383838]' : 'bg-gray-200'" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                            <span :class="aiGradingEnabled ? 'translate-x-5' : 'translate-x-0'" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                    
                    <button @click="showGradingModal = false" class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-100 transition text-gray-600">
                        <i class="ri-close-line text-lg"></i>
                    </button>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                <div class="relative w-full sm:w-96">
                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search by student name..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sort By:</span>
                    <select x-model="sortBy" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-[#383838] outline-none cursor-pointer">
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
                        <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <th class="px-6 pb-2">Student</th>
                            <th class="px-6 pb-2">Submitted</th>
                            <th class="px-6 pb-2">Duration</th>
                            <th class="px-6 pb-2">File</th>
                            <th class="px-6 pb-2 text-right">Grading & Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="sub in filteredSubmissions" :key="sub.id">
                            <tr class="bg-gray-50/50 hover:bg-gray-50 transition-all rounded-3xl group border border-gray-100">
                                <td class="px-6 py-6 font-bold text-gray-900 rounded-l-3xl border-y border-l border-gray-100 align-top">
                                    <span class="block text-sm text-[#383838]" x-text="sub.user ? `${sub.user.last_name}, ${sub.user.first_name}` : 'N/A'"></span>
                                </td>
                                <td class="px-6 py-6 text-xs font-bold text-gray-600 border-y border-gray-100 align-top" x-text="formatDate(sub.created_at)"></td>
                                <td class="px-6 py-6 text-xs font-bold text-gray-600 border-y border-gray-100 align-top" x-text="formatDuration(sub.duration || sub.duration_seconds || sub.time_taken) || '--'"></td>
                                <td class="px-6 py-6 border-y border-gray-100 align-top">
                                    <a :href="'{{ url('/') }}/' + sub.file_path" target="_blank" class="inline-flex items-center text-[10px] font-black text-gray-700 bg-white px-4 py-2.5 rounded-xl hover:bg-[#383838] hover:text-white transition-all uppercase tracking-widest border border-gray-200 shadow-sm">
                                        <i class="ri-download-2-line mr-2 text-sm"></i> Download
                                    </a>
                                </td>
                                <td class="px-6 py-6 rounded-r-3xl border-y border-r border-gray-100 align-top">
                                    <div class="mb-5 flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                                        <div class="flex items-center gap-4">
                                            <div>
                                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Grading Status</span>
                                                <p class="text-xs font-black mt-1 text-[#383838]" x-text="sub.auto_graded ? '🤖 AI Evaluated' : (sub.grade !== null ? '✍️ Manual Entry' : '⏳ Pending')"></p>
                                            </div>
                                            <button x-show="aiGradingEnabled" @click="regradeSubmission(sub, $event)" class="ml-2 bg-gray-100 hover:bg-gray-200 text-[#383838] text-[9px] font-black px-3 py-2 rounded-xl transition flex items-center gap-1.5 uppercase tracking-widest border border-gray-200">
                                                <i class="ri-magic-line text-sm"></i> AI Grade
                                            </button>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Achieved Score</span>
                                            <p class="text-lg font-black text-[#383838]"><span x-text="sub.grade ?? '0'"></span> <span class="text-xs text-gray-400 font-bold tracking-widest" x-text="'/ ' + gradingTask.points"></span></p>
                                        </div>
                                    </div>

                                    <template x-if="sub.submission_grade && sub.submission_grade.criterion_scores">
                                        <div class="mb-5 space-y-3">
                                            <h4 class="text-[9px] font-black uppercase tracking-widest text-gray-400">Rubric Breakdown</h4>
                                            <template x-for="score in sub.submission_grade.criterion_scores" :key="score.id">
                                                <div class="p-4 border border-gray-200 bg-white rounded-2xl shadow-sm">
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span class="text-xs font-black text-gray-900" x-text="score.criterion?.criterion_name || 'Criterion'"></span>
                                                        <span class="text-[10px] font-black px-2.5 py-1 bg-gray-100 text-[#383838] rounded-lg border border-gray-200" x-text="score.points_earned + ' / ' + score.max_points"></span>
                                                    </div>
                                                    <p class="text-[11px] text-gray-500 leading-relaxed font-medium" x-text="score.feedback"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <form :action="'/professor/grade/' + sub.id" method="POST" class="flex flex-col gap-3 mt-2 border-t border-gray-200 pt-5" @submit.prevent="submitGrade(sub, $event)">
                                        @csrf
                                        <div class="flex items-center justify-between">
                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block">Final Score Override</span>
                                            <div class="flex items-center bg-white border border-gray-200 rounded-xl px-3 py-1 shadow-sm focus-within:ring-2 focus-within:ring-[#383838] transition">
                                                <input type="number" name="grade" :value="sub.grade" class="w-14 bg-transparent border-none p-0 text-sm font-black text-center focus:ring-0 text-[#383838]" placeholder="0">
                                                <span class="text-[10px] font-black text-gray-400 ml-1" x-text="'/ ' + gradingTask.points"></span>
                                            </div>
                                        </div>
                                        <div class="flex items-stretch gap-2 h-12">
                                            <input type="text" name="feedback" :value="sub.feedback" class="w-full border-gray-200 rounded-xl text-xs px-4 focus:ring-2 focus:ring-[#383838] transition-all bg-white" placeholder="Enter manual comments...">
                                            <button type="submit" class="bg-[#383838] text-white px-5 rounded-xl hover:bg-black transition shadow-sm h-full flex items-center justify-center w-16 shrink-0">
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
                    <div class="text-center py-24 bg-gray-50/50 rounded-[2rem] border border-gray-100 shadow-inner mt-4">
                        <i class="ri-search-eye-line text-6xl text-gray-200 mb-4 block"></i>
                        <p class="text-gray-400 font-black text-xs uppercase tracking-widest">No submissions match your search.</p>
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
            <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Quiz Management</h2>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Control laboratory quiz & results</p>
        </div>
        <a href="{{ route('professor.quizzes.create', ['session_id' => $session->id]) }}" target="_blank"
            class="bg-[#383838] text-white px-6 py-2.5 rounded-xl font-bold uppercase text-xs hover:bg-black transition-all shadow-sm active:scale-95 inline-block">
            + Create Quiz
        </a>
    </div>

    <div id="quizzes-list-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($session->quizzes ?? [] as $quiz)
            <div class="bg-white p-5 rounded-2xl border border-gray-100 flex flex-col justify-between group hover:border-[#383838] transition-all shadow-sm">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center border group-hover:bg-black group-hover:text-white transition">
                            <i class="ri-timer-line text-lg"></i>
                        </div>
                        <span class="bg-gray-100 text-[#383838] px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                            {{ $quiz->questions->count() }} PTS
                        </span>
                    </div>

                    <h4 class="font-bold text-gray-900 mb-1 group-hover:text-black transition">{{ $quiz->title }}</h4>

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

                <div class="mt-6">
                    <button @click="selectedQuiz = {{ json_encode($quiz) }}; scores = {{ json_encode($quiz->attempts()->with('user')->get()) }}"
                        class="w-full bg-gray-50 text-[#383838] border border-gray-200 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest">
                        View Results
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center">
                <i class="ri-timer-flash-line text-4xl text-gray-200 mb-3 block"></i>
                <p class="text-gray-400 italic text-sm">No quizzes available for this session.</p>
            </div>
        @endforelse
    </div>

    <template x-teleport="body">
        <div x-show="selectedQuiz" class="fixed inset-0 z-[99999] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4 sm:p-6" x-cloak x-transition.opacity>
            
            <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-4xl h-[85vh] flex flex-col overflow-hidden transform transition-all" @click.away="closeResults()" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                    <div>
                        <h3 class="font-black text-2xl text-gray-900 uppercase tracking-tight" x-text="selectedQuiz?.title"></h3>
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mt-1">Quiz Performance & Score Overview</p>
                    </div>
                    <button @click="closeResults()" class="w-10 h-10 flex items-center justify-center bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-900 rounded-xl transition-all">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                <div class="px-8 py-4 border-b border-gray-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 shrink-0">
                    <div class="relative w-full sm:w-80">
                        <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Search student name..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Sort By:</span>
                        <select x-model="sortBy" class="bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-[#383838] outline-none cursor-pointer">
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="score_desc">Highest Score</option>
                            <option value="score_asc">Lowest Score</option>
                        </select>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-gray-50/50">
                    
                    <template x-if="scores.length > 0">
                        <div class="hidden sm:flex items-center justify-between px-6 mb-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                            <div class="w-1/2">Student Name</div>
                            <div class="w-1/4 text-center">Time Taken</div>
                            <div class="w-1/4 text-right">Final Score</div>
                        </div>
                    </template>

                    <div class="space-y-3">
                        <template x-for="attempt in filteredScores" :key="attempt.id">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-white p-4 sm:px-6 rounded-2xl border border-gray-100 shadow-sm hover:border-[#383838] transition-all group gap-4">
                                
                                <div class="w-full sm:w-1/2 flex items-center gap-4">
                                    <span class="font-bold text-gray-900 text-sm group-hover:text-black transition-colors" 
                                          x-text="attempt.user ? `${attempt.user.last_name}, ${attempt.user.first_name}` : 'N/A'"></span>
                                </div>

                                <div class="w-full sm:w-1/4 flex sm:justify-center">
                                    <div class="inline-flex items-center text-[10px] font-black text-gray-500 bg-gray-50 px-3 py-2 rounded-xl border border-gray-100 uppercase tracking-widest">
                                        <i class="ri-timer-line mr-2"></i>
                                        <span x-text="Math.floor(attempt.time_spent / 60) + 'm ' + (attempt.time_spent % 60) + 's'"></span>
                                    </div>
                                </div>

                                <div class="w-full sm:w-1/4 flex items-center sm:justify-end gap-2">
                                    <div class="px-3 py-2 border-2 border-gray-50 rounded-xl">
                                        <span class="text-xs font-black text-gray-400 group-hover:text-[#383838] transition-colors" 
                                              x-text="Math.round((attempt.score / attempt.total_questions) * 100) + '%'"></span>
                                    </div>
                                    <div class="px-4 py-2 bg-[#383838] text-white rounded-xl shadow-sm">
                                        <span class="text-xs font-black tracking-wide" 
                                              x-text="attempt.score + ' / ' + attempt.total_questions"></span>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                    <template x-if="scores.length === 0">
                        <div class="text-center py-20 bg-white border-2 border-dashed border-gray-100 rounded-[2rem] flex flex-col items-center justify-center mt-2">
                            <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                                <i class="ri-inbox-line text-2xl text-gray-300"></i>
                            </div>
                            <h4 class="text-gray-900 font-bold mb-1">No Submissions Yet</h4>
                            <p class="text-gray-400 text-xs font-bold">Students haven't completed this quiz.</p>
                        </div>
                    </template>

                    <template x-if="scores.length > 0 && filteredScores.length === 0">
                        <div class="text-center py-20 flex flex-col items-center justify-center mt-2">
                            <i class="ri-search-eye-line text-6xl text-gray-200 mb-4 block"></i>
                            <p class="text-gray-400 font-black text-xs uppercase tracking-widest">No submissions match your search.</p>
                        </div>
                    </template>
                    
                </div>
            </div>
        </div>
    </template>
</div>

                        <div x-show="activeTab === 'students'" x-cloak class="space-y-6 animate-fade-in">

                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 ms-4 me-4 gap-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Enrolled
                                        Students</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Monitor presence and connection status
                                    </p>
                                </div>
                                <div
                                    class="bg-gray-100 text-[#383838] px-5 py-2.5 rounded-xl flex items-center gap-3 border border-gray-200 shadow-sm">
                                    <i class="ri-team-line text-lg"></i>
                                    <span class="text-[10px] font-black uppercase tracking-widest">
                                        Total Enrolled: {{ $class->students->count() ?? 0 }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                @forelse($class->students as $student)
                                    <div
                                        class="bg-white p-5 rounded-3xl border border-gray-100 flex items-center justify-between group hover:border-[#383838] hover:shadow-lg transition-all duration-300">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-[#383838] font-black text-sm border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                                                {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                                            </div>

                                            <div class="flex flex-col min-w-0">
                                                <h4
                                                    class="font-black text-gray-900 text-sm truncate leading-tight group-hover:text-black transition">
                                                    {{ strtoupper($student->last_name) }}, {{ $student->first_name }}
                                                    @if($student->middle_name)
                                                    {{ strtoupper(substr($student->middle_name, 0, 1)) }}. @endif
                                                </h4>
                                                <p class="text-[10px] text-gray-400 font-bold tracking-widest mt-1">
                                                    {{ $student->school_id }}
                                                </p>
                                            </div>
                                        </div>

                                        <div>
                                            @if($student->pivot->is_present)
                                                <div class="w-3 h-3 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)] animate-pulse"
                                                    title="Active"></div>
                                            @else
                                                <div class="w-3 h-3 rounded-full bg-gray-200" title="Offline"></div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center bg-white">
                                        <i class="ri-user-unfollow-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 font-bold text-sm">No students enrolled in this session.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>



                        <div x-show="activeTab === 'browser-security'" x-data="browserSecurityManager()" x-cloak
                            class="space-y-6 animate-fade-in">

                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 ms-4 me-4 gap-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Browser
                                        Security</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Control and monitor website access
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                                <div class="xl:col-span-1 space-y-6">

                                    <div class="bg-white rounded-[2rem] border border-gray-100 p-8 shadow-sm">
                                        <h3
                                            class="text-xs font-black text-[#383838] uppercase tracking-widest mb-6 flex items-center">
                                            <i class="ri-global-line mr-2 text-lg"></i> Add Allowed Website
                                        </h3>

                                        <form @submit.prevent="addSite()" class="space-y-5">
                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Domain
                                                    *</label>
                                                <input type="text" x-model="newSite.domain" placeholder="youtube.com"
                                                    required
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none transition-all">
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold mt-2 px-1 uppercase tracking-widest">
                                                    Exclude http:// or www.</p>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Display
                                                    Name *</label>
                                                <input type="text" x-model="newSite.name" placeholder="YouTube" required
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none transition-all">
                                            </div>

                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Access
                                                    Scope</label>
                                                <select x-model="newSite.scope"
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none cursor-pointer transition-all">
                                                    <option value="global">Global (All tasks)</option>
                                                    <option value="task">Specific Task Only</option>
                                                </select>
                                            </div>

                                            <div x-show="newSite.scope === 'task'" x-collapse>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Target
                                                    Task</label>
                                                <select x-model="newSite.task_id"
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none cursor-pointer transition-all">
                                                    <option value="">Select a task...</option>
                                                    <template x-for="task in tasks" :key="task.id">
                                                        <option :value="task.id" x-text="task.title"></option>
                                                    </template>
                                                </select>
                                            </div>

                                            <button type="submit" :disabled="adding"
                                                class="w-full py-4 bg-[#383838] text-white rounded-2xl font-black uppercase text-[10px] hover:bg-black transition-all shadow-lg tracking-widest mt-2">
                                                <span x-show="!adding">+ Whitelist Domain</span>
                                                <span x-show="adding"><i class="ri-loader-4-line animate-spin"></i>
                                                    Processing</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="xl:col-span-2 space-y-6">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="bg-gray-50 rounded-[2rem] border border-gray-100 p-8">
                                            <h3
                                                class="text-xs font-black text-gray-500 uppercase tracking-widest mb-6 flex items-center">
                                                <i class="ri-shield-check-line mr-2 text-lg"></i> System Approved
                                            </h3>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <template x-for="site in preApprovedSites" :key="site.id">
                                                    <div
                                                        class="bg-white border border-gray-100 rounded-2xl p-4 flex flex-col justify-center">
                                                        <span class="font-black text-[#383838] text-sm"
                                                            x-text="site.name"></span>
                                                        <span
                                                            class="text-[10px] text-gray-400 font-bold tracking-widest mt-1"
                                                            x-text="site.domain"></span>
                                                    </div>
                                                </template>
                                            </div>
                                            <div x-show="preApprovedSites.length === 0"
                                                class="text-center py-4 text-gray-400 text-[10px] uppercase font-bold tracking-widest">
                                                Loading defaults...
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-[2rem] border border-gray-100 p-8 shadow-sm">
                                            <h3
                                                class="text-xs font-black text-[#383838] uppercase tracking-widest mb-6 flex items-center">
                                                <i class="ri-list-check-2 mr-2 text-lg"></i> Custom Allowed
                                            </h3>

                                            <div class="space-y-3 max-h-[250px] overflow-y-auto pr-2">
                                                <template x-for="site in sessionSites" :key="site.id">
                                                    <div
                                                        class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl group transition-colors hover:bg-white hover:border hover:border-gray-200">
                                                        <div>
                                                            <h4 class="font-black text-sm text-[#383838]"
                                                                x-text="site.name"></h4>
                                                            <p class="text-[10px] text-gray-400 font-bold tracking-widest mt-0.5"
                                                                x-text="site.domain"></p>
                                                        </div>
                                                        <button @click="deleteSite(site.id)"
                                                            class="text-gray-300 hover:text-red-500 transition p-2">
                                                            <i class="ri-delete-bin-line text-lg"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                                <div x-show="sessionSites.length === 0"
                                                    class="text-center py-10 text-gray-400">
                                                    <i class="ri-node-tree text-3xl mb-2 block text-gray-200"></i>
                                                    <p class="text-[10px] uppercase font-bold tracking-widest">No custom
                                                        rules</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm">
                                        <div class="flex justify-between items-center mb-8">
                                            <h3
                                                class="text-xs font-black text-red-500 uppercase tracking-widest flex items-center">
                                                <i class="ri-shield-cross-line mr-2 text-lg"></i> Blocked Attempts Log
                                            </h3>
                                            <button @click="refreshBlockedAttempts()"
                                                class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#383838] transition">
                                                <i class="ri-refresh-line mr-1"></i> Refresh
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-3 gap-4 mb-8">
                                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center">
                                                <p class="text-3xl font-black text-[#383838]"
                                                    x-text="blockedStats.total || 0"></p>
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-2">
                                                    Total Blocked</p>
                                            </div>
                                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center">
                                                <p class="text-3xl font-black text-[#383838]"
                                                    x-text="blockedStats.by_domain?.length || 0"></p>
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-2">
                                                    Unique Sites</p>
                                            </div>
                                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center">
                                                <p class="text-3xl font-black text-[#383838]"
                                                    x-text="blockedStats.by_student?.length || 0"></p>
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-2">
                                                    Students Affected</p>
                                            </div>
                                        </div>

                                        <div class="overflow-x-auto bg-gray-50 rounded-2xl border border-gray-100 p-4">
                                            <table class="w-full text-left">
                                                <thead>
                                                    <tr
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        <th class="px-5 pb-3 border-b border-gray-200">Student</th>
                                                        <th class="px-5 pb-3 border-b border-gray-200">Target URL</th>
                                                        <th class="px-5 pb-3 border-b border-gray-200 text-right">
                                                            Timestamp</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="attempt in blockedAttempts.slice(0, 10)"
                                                        :key="attempt.id">
                                                        <tr class="hover:bg-white transition-colors">
                                                            <td class="px-5 py-4 text-sm font-bold text-[#383838]"
                                                                x-text="attempt.user?.name"></td>
                                                            <td class="px-5 py-4">
                                                                <span class="text-xs font-black text-red-500"
                                                                    x-text="attempt.blocked_domain"></span>
                                                                <span
                                                                    class="block text-[10px] text-gray-400 font-bold mt-1 truncate max-w-[200px]"
                                                                    x-text="attempt.blocked_url"></span>
                                                            </td>
                                                            <td class="px-5 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest"
                                                                x-text="formatTime(attempt.attempted_at)"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>

                                            <div x-show="blockedAttempts.length === 0"
                                                class="text-center py-12 text-gray-400">
                                                <i class="ri-shield-check-line text-4xl mb-3 block text-gray-200"></i>
                                                <p class="text-[10px] font-black uppercase tracking-widest">Zero blocked
                                                    attempts</p>
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
        let receiverPeer = null;   // Phone 1: For receiving student screens
        let broadcastPeer = null;  // Phone 2: For sending professor screen
        let profLocalStream = null;
        let activeBroadcastCalls = [];
        const enrolledStudentIds = @json($class->students->pluck('id'));

        window.addEventListener('beforeunload', () => {
            if (receiverPeer) { receiverPeer.disconnect(); receiverPeer.destroy(); }
            if (broadcastPeer) { broadcastPeer.disconnect(); broadcastPeer.destroy(); }
        });

        // 🟢 PHONE 1: The Receiver (Listens for students on PROF_ID)
        function initReceiverPeer() {
            if (receiverPeer) { receiverPeer.destroy(); receiverPeer = null; }

            const profId = 'PROF_{{ auth()->user()->id }}';
            receiverPeer = new Peer(profId);

            receiverPeer.on('open', (id) => console.log("✅ Receiver Peer Ready:", id));

            receiverPeer.on('call', (call) => {
                const studentId = call.peer.replace('STUDENT_', '');
                console.log("📞 Incoming student monitor from:", studentId);
                call.answer();
                call.on('stream', (remoteStream) => {
                    console.log("🟢 Stream received for student", studentId);

                    const studentName = call.metadata?.studentName || 'Student ' + studentId;

                    // Function to attach stream
                    const attachStream = () => {
                        if (window.liveMonitorInstance) {
                            window.liveMonitorInstance.addStream(studentId, studentName, call, remoteStream);
                        } else {
                            console.warn('liveMonitorInstance not ready, retrying...');
                            setTimeout(attachStream, 200);
                        }
                    };
                    attachStream();
                });
                call.on('close', () => {
                    console.log("🔴 Stream closed for student", studentId);
                    if (window.liveMonitorInstance) {
                        window.liveMonitorInstance.removeStream(studentId);
                    }
                    // Clean up video element
                    const video = document.getElementById('video-' + studentId);
                    if (video) {
                        video.srcObject = null;
                    }
                });
            });

            receiverPeer.on('error', (err) => {
                if (err.type === 'unavailable-id') {
                    console.warn("⚠️ Receiver ID ghosted by public server. Existing incoming screens will still work!");
                }
            });
        }

        // 🟢 PHONE 2: The Broadcaster (Uses a random ID so it NEVER crashes!)
        function initBroadcastPeer() {
            if (broadcastPeer) { broadcastPeer.destroy(); broadcastPeer = null; }

            // Passing empty parameters generates a guaranteed unique random ID
            broadcastPeer = new Peer();

            broadcastPeer.on('open', (id) => console.log("✅ Broadcast Peer Ready (Random ID):", id));

            broadcastPeer.on('disconnected', () => {
                if (broadcastPeer && !broadcastPeer.destroyed) broadcastPeer.reconnect();
            });

            broadcastPeer.on('error', (err) => console.error("Broadcast Peer Error:", err));
        }

        function resetStudentUI(studentId) {
            const video = document.getElementById('video-' + studentId);
            const overlay = document.getElementById('video-overlay-' + studentId);
            const btn = document.querySelector(`#btn-container-${studentId} button`);

            if (video) { video.srcObject = null; video.classList.add('hidden'); }
            if (overlay) overlay.classList.remove('hidden');
            if (btn) {
                btn.innerText = "Waiting...";
                btn.classList.remove('bg-[#383838]', 'text-white', 'hover:bg-black');
                btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                btn.disabled = true;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            initReceiverPeer();
            initBroadcastPeer();
        });

        // 🟢 BROADCASTING NOW USES PHONE 2
        window.toggleBroadcast = async function () {
            const wrapper = document.getElementById('broadcast-wrapper');
            if (!wrapper) return;
            const alpine = Alpine.$data(wrapper);

            if (!alpine.isBroadcasting) {

                // Ensure the Broadcaster is alive
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
                        console.log("📡 Broadcasting to:", targetPeerId);

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

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('taskManager', () => ({
        showEditorModal: false,
        showGradingModal: false,
        isEditing: false,
        taskForm: { id: null, title: '', description: '', deadline: '' },
        criteria: [],
        uidCounter: 100,
        
        // Grading State
        gradingTask: null,
        submissions: [],
        aiGradingEnabled: true, 
        
        // 🟢 NEW: Search & Sort State
        searchQuery: '',
        sortBy: 'name_asc', // name_asc, name_desc, score_desc, score_asc

        get computedMaxPoints() {
            if (this.criteria.length === 0) return 0;
            return this.criteria.reduce((total, criterion) => total + this.getMaxPoints(criterion), 0);
        },

        getMaxPoints(criterion) {
            if (!criterion.levels || criterion.levels.length === 0) return 0;
            return criterion.levels.reduce((max, l) => Math.max(max, parseInt(l.points) || 0), 0);
        },

        // 🟢 NEW: Safely format Laravel database timestamps into readable strings
        formatDate(dateStr) {
            if (!dateStr) return 'No Date Recorded';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        },

        // 🟢 NEW: Format duration integers (minutes/seconds) into a readable "X hrs Y mins" string
        formatDuration(val) {
            if (val === null || val === undefined) return '';
            
            const num = parseInt(val);
            if (isNaN(num)) return val; // Returns original text if it's already a string like "20 mins"
            
            // Assuming your database stores duration in MINUTES. 
            // (If it stores seconds, change this math to: num / 60)
            if (num >= 60) {
                const hrs = Math.floor(num / 60);
                const mins = num % 60;
                return `${hrs}h ${mins}m`;
            }
            return `${num} mins`;
        },

        // 🟢 NEW: Reactive Getter for Filtering and Sorting Submissions
        get filteredSubmissions() {
            let result = this.submissions;

            // 1. Filter by Search Query
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(sub => {
                    const fullName = `${sub.user?.first_name || ''} ${sub.user?.last_name || ''}`.toLowerCase();
                    return fullName.includes(q);
                });
            }

            // 2. Sort the Results
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

        openEditor(existingTask = null, existingRubric = null, existingCriteria = null) {
            if (existingTask) {
                this.isEditing = true;
                this.taskForm = {
                    id: existingTask.id,
                    title: existingTask.title,
                    description: existingTask.description || '',
                    deadline: existingTask.deadline ? new Date(existingTask.deadline).toISOString().slice(0,16) : ''
                };
                
                if (existingCriteria && existingCriteria.length > 0) {
                    this.criteria = JSON.parse(JSON.stringify(existingCriteria)); 
                    this.criteria.forEach(c => {
                        c.uid = this.uidCounter++;
                        c.levels.forEach(l => l.uid = this.uidCounter++);
                    });
                } else {
                    this.criteria = [];
                    this.addCriterion();
                }
            } else {
                this.isEditing = false;
                this.taskForm = { id: null, title: '', description: '', deadline: '' };
                this.criteria = [];
                this.addCriterion();
            }
            
            this.showEditorModal = true;
        },

        addCriterion() {
            this.criteria.push({
                uid: this.uidCounter++,
                name: '',
                description: '',
                levels: [
                    { uid: this.uidCounter++, label: 'Excellent', points: 5, description: '' },
                    { uid: this.uidCounter++, label: 'Average', points: 3, description: '' },
                    { uid: this.uidCounter++, label: 'Poor', points: 1, description: '' }
                ]
            });
        },
        removeCriterion(uid) { this.criteria = this.criteria.filter(c => c.uid !== uid); },
        addLevel(criterion) { criterion.levels.push({ uid: this.uidCounter++, label: 'New', points: 0, description: '' }); },
        removeLevel(criterion, uid) { criterion.levels = criterion.levels.filter(l => l.uid !== uid); },

        async saveTask() {
            if (!this.taskForm.title || !this.taskForm.deadline) {
                alert('Title and Deadline are required.');
                return;
            }

            const payload = {
                subject_id: {{ $session->id }}, 
                title: this.taskForm.title,
                description: this.taskForm.description,
                deadline: this.taskForm.deadline,
                points: this.computedMaxPoints,
                rubric: {
                    name: this.taskForm.title + ' Scoring Config',
                    criteria_json: JSON.stringify(this.criteria)
                }
            };

            const endpoint = this.isEditing ? `/professor/tasks/${this.taskForm.id}` : `/professor/tasks`;
            const method = this.isEditing ? 'PUT' : 'POST';

            try {
                const res = await fetch(endpoint, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    window.location.reload(); 
                } else {
                    const data = await res.json();
                    alert(data.message || 'Error saving task.');
                }
            } catch (err) { console.error(err); }
        },

        openGrading(task, submissionsData) {
            this.gradingTask = task;
            this.submissions = submissionsData || [];
            this.aiGradingEnabled = true;
            this.searchQuery = ''; // Reset search when opening
            this.showGradingModal = true;
        },

        // 🟢 FIX: Re-grade without page reload (Mutates the 'sub' object directly)
       async regradeSubmission(sub, event) {
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Analyzing...';

            try {
                const res = await fetch(`/professor/submissions/${sub.id}/regrade`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                
                if (data.success) {
                    btn.innerHTML = '<i class="ri-check-line"></i> Success';
                    btn.classList.replace('text-[#383838]', 'text-green-700');
                    btn.classList.replace('bg-gray-100', 'bg-green-100');
                    
                    // 🟢 FIX: Instant UI Update (Now includes the full feedback text!)
                    sub.grade = data.total_score;
                    sub.auto_graded = true;
                    sub.submission_grade = data.submission_grade; // <--- ADD THIS LINE

                    // Revert button styling after 2 seconds
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

        // 🟢 FIX: Submit manual grade without page reload
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (res.ok) {
                    btn.innerHTML = '<i class="ri-check-double-line text-lg"></i>';
                    btn.classList.replace('bg-[#383838]', 'bg-green-500');
                    
                    // Instant UI Update
                    sub.grade = formData.get('grade');
                    sub.feedback = formData.get('feedback');
                    sub.auto_graded = false; // Override flag to show Manual Entry

                    // Revert button styling after 2 seconds
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
        }
    }));
});
</script>

</x-app-layout>