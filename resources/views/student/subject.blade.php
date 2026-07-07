<x-app-layout>
    @php
        $violationStatus = $violationStatus ?? [
            'violation_count' => 0,
            'threshold' => $class->violation_warning_threshold ?? config('lmms.violation_warning_threshold', 3),
            'remaining_warnings' => $class->violation_warning_threshold ?? config('lmms.violation_warning_threshold', 3),
            'is_screen_blocked' => false,
        ];
    @endphp

    <div id="screen-block-overlay"
        class="{{ ($violationStatus['is_screen_blocked'] ?? false) ? 'flex' : 'hidden' }} fixed inset-0 z-[99999] bg-[#111827] items-center justify-center p-6">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-lg w-full p-10 text-center border border-red-100">
            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="ri-lock-2-line text-4xl text-red-600"></i>
            </div>
            <h2 class="text-2xl font-black text-gray-900 mb-3">Screen Locked</h2>
            <p class="text-sm text-gray-500 leading-relaxed mb-4">
                You reached the maximum number of policy violations for this lab session.
                Your workspace is locked until your instructor unblocks you.
            </p>
            <p class="text-[10px] font-black uppercase tracking-widest text-red-500">
                Violations: {{ $violationStatus['violation_count'] ?? 0 }} / {{ $violationStatus['threshold'] ?? 3 }}
            </p>
        </div>
    </div>

    <div id="violation-warning-modal" class="hidden fixed inset-0 z-[99998] bg-black/50 backdrop-blur-sm items-center justify-center p-6">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full p-8 text-center border border-amber-100">
            <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-5">
                <i class="ri-error-warning-line text-3xl text-amber-500"></i>
            </div>
            <h3 class="text-xl font-black text-gray-900 mb-2">Policy Violation</h3>
            <p id="violation-warning-message" class="text-sm text-gray-600 leading-relaxed mb-6"></p>
            <button type="button" onclick="hideViolationWarning()"
                class="w-full bg-[#383838] text-white py-3 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-black transition">
                I Understand
            </button>
        </div>
    </div>

    <!-- Lifted isSharing state to the root level to expose it to the FAB button -->
    <div class="flex h-screen w-full bg-gray-50 overflow-hidden" x-data="{ sidebarOpen: false, activeToolTab: 'compiler', isSharing: false }" @screen-shared.window="isSharing = true" @screen-stopped.window="isSharing = false">
        
        <div :class="sidebarOpen ? 'w-1/2' : 'w-full'" class="h-full relative transition-all duration-500 ease-in-out flex flex-col border-r border-gray-200">
            
            <div id="lockdown-ui" class="hidden w-full h-full bg-black relative flex-1 flex-col">
                <video id="professor-screen" autoplay playsinline muted class="w-full h-full object-contain"></video>
                <div class="absolute top-4 left-4 flex gap-3">
                    <span class="px-4 py-2 bg-red-600 text-white rounded-lg font-bold text-[10px] uppercase tracking-widest animate-pulse flex items-center gap-2 shadow-xl">
                        <i class="ri-broadcast-fill"></i> LIVE LECTURE
                    </span>
                </div>
            </div>

            <!-- Removed local isSharing from this scope so it inherits from the root -->
            <div id="normal-view" class="w-full h-full overflow-y-auto flex-col flex-1" x-data="{ activeTab: 'tasks' }">
                <main class="flex-1 p-8">
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2 bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6">
                                <h1 class="text-3xl font-black text-gray-900 mb-3 tracking-tight">
                                    {{ $class->subject_name }} <span class="text-gray-400 font-light mx-2">|</span>
                                    <span class="text-[#383838] uppercase">{{ $class->program }}-{{ $class->year_level }}{{ $class->section }}</span>
                                </h1>
                                <div class="flex gap-2">
                                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase border border-gray-200">
                                        <i class="ri-calendar-line mr-2"></i> {{ $class->schedule_day }}
                                    </span>
                                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase border border-gray-200">
                                        <i class="ri-time-line mr-2"></i> {{ $class->schedule_time }}
                                    </span>
                                </div>
                            </div>

                            <div class="md:col-span-1">
                                @if(!$class->is_active)
                                    <div></div>
                                @else
                                    <div x-show="isSharing" x-cloak class="bg-white border-2 border-dashed border-gray-200 rounded-2xl p-6 h-full flex flex-col justify-center items-center text-center animate-fade-in">
                                        <div class="ri-broadcast-line text-3xl text-green-500 animate-pulse mb-2"></div>
                                        <h2 class="font-black text-gray-900 tracking-tight">Monitoring Active</h2>
                                        <p class="text-[10px] text-gray-500 mt-1">The professor is viewing your screen.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(!$class->is_active)
                            <div class="bg-amber-50 border border-amber-200 rounded-3xl p-12 text-center shadow-inner">
                                <i class="ri-error-warning-line text-5xl text-amber-500 mb-4 block"></i>
                                <p class="font-black text-amber-900 text-xl">Session Offline</p>
                                <p class="text-amber-700 font-medium">The instructor has not initialized the laboratory session yet.</p>
                                 <a href="{{ route('dashboard') }}" class="inline-flex items-center text-xs font-black text-gray-400 hover:text-gray-800 uppercase tracking-widest transition duration-150 group">
                                        <i class="ri-arrow-left-line mr-2 text-sm transition-transform group-hover:-translate-x-1"></i>
                                        Back to Dashboard
                                    </a>
                            </div>
                        @else
                            <div x-show="!isSharing" class="flex flex-col items-center justify-center bg-white p-12 rounded-[40px] border border-gray-200 shadow-sm text-center">
                                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                                    <i class="ri-macbook-line text-4xl text-[#383838]"></i>
                                </div>
                                <h3 class="text-2xl font-black text-gray-900 mb-2 tracking-tight">Security Check Required</h3>
                                <p class="text-gray-500 mb-8 max-w-md">
                                    To maintain lab integrity, share your 
                                    <span class="font-bold text-gray-800 underline underline-offset-4">Entire Screen</span> 
                                    to unlock the classroom dashboard.
                                </p>
                                <div class="flex flex-col items-center space-y-4">
                                    <button onclick="enterClassroom()" class="bg-[#383838] text-white px-10 py-4 rounded-2xl shadow-xl hover:bg-[#2c2c2c] font-black transition-all hover:scale-105 duration-150">
                                        Share Screen & Enter Classroom
                                    </button>
                                    <a href="{{ route('dashboard') }}" class="inline-flex items-center text-xs font-black text-gray-400 hover:text-gray-800 uppercase tracking-widest transition duration-150 group">
                                        <i class="ri-arrow-left-line mr-2 text-sm transition-transform group-hover:-translate-x-1"></i>
                                        Back to Dashboard
                                    </a>
                                </div>
                            </div>

                            <div x-show="isSharing" x-cloak class="animate-fade-in">
                                <div class="flex border-b border-gray-200 mb-8">
                                    <template x-for="t in ['materials', 'tasks', 'quizzes',]">
                                        <button @click="activeTab = t"
                                            :class="activeTab === t ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 hover:text-gray-600 font-bold'"
                                            class="px-8 py-4 text-[10px] uppercase tracking-widest transition" x-text="t"></button>
                                    </template>
                                </div>

                                <div x-show="activeTab === 'tasks'" x-data="classroomTasks()" class="space-y-6" @task-updated.window="fetchTasks()">
                                    <div class="flex items-center gap-2 bg-gray-50/50 p-1.5 rounded-[20px] border border-gray-100 w-fit">
                                        <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'" class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all">All Tasks</button>
                                        <button @click="filter = 'submitted'" :class="filter === 'submitted' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'" class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2"><i class="ri-checkbox-circle-line"></i> Submitted</button>
                                        <button @click="filter = 'missing'" :class="filter === 'missing' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'" class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2"><i class="ri-error-warning-line"></i> Missing</button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <template x-for="task in filteredTasks" :key="task.id">
                                          <div @click="$dispatch('open-task', task)" class="bg-white p-5 rounded-[28px] border border-gray-100 flex flex-col justify-between group hover:border-[#383838] cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-gray-100/50 active:scale-[0.98] animate-fade-in">
                                                <div class="space-y-4">
                                                    <div class="flex items-start gap-3">
                                                        <div class="mt-1 w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                                                            <i class="ri-file-text-line text-sm"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                            <h4 class="font-black text-[#383838] text-base tracking-tight leading-tight group-hover:text-black transition-colors" x-text="task.title"></h4>
                                                            <div class="mt-1 flex items-center gap-1.5">
                                                                <i class="ri-calendar-todo-line text-gray-400 text-[10px]"></i>
                                                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide" x-text="formatDeadline(task.deadline)"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="py-3 border-y border-gray-50">
                                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Score:</span>
                                                        <div class="flex items-baseline gap-1">
                                                            <span class="text-2xl font-black text-[#383838]" x-text="task.current_user_submission?.grade ?? '--'"></span>
                                                            <span class="text-xs font-bold text-gray-300" x-text="'/ ' + task.points"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between pt-3">
                                                    <template x-if="task.current_user_submission"><span class="flex items-center gap-1 text-[9px] font-black text-black uppercase tracking-tighter"><i class="ri-checkbox-circle-fill text-base text-[#383838]"></i> Submitted</span></template>
                                                    <template x-if="!task.current_user_submission"><span class="flex items-center gap-1 text-[9px] font-black text-gray-300 uppercase tracking-tighter"><i class="ri-radio-button-line text-base"></i> Missing</span></template>
                                                    <i class="ri-arrow-right-line text-gray-300 group-hover:text-[#383838] group-hover:translate-x-1 transition-all"></i>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                    <div x-show="filteredTasks.length === 0" class="py-20 text-center"><i class="ri-inbox-line text-4xl text-gray-200"></i><p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-4">No tasks found in this category</p></div>
                                </div>

                                <div x-show="activeTab === 'quizzes'" x-data="classroomQuizzes()" class="space-y-6">
    <div class="flex items-center gap-2 bg-gray-50/50 p-1.5 rounded-[20px] border border-gray-100 w-fit">
        <button @click="filter = 'all'" :class="filter === 'all' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'" class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all">All Quizzes</button>
        <button @click="filter = 'completed'" :class="filter === 'completed' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'" class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all"><i class="ri-checkbox-circle-line"></i> Completed</button>
        <button @click="filter = 'pending'" :class="filter === 'pending' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'" class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all"><i class="ri-error-warning-line"></i> Pending</button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <template x-for="quiz in filteredQuizzes" :key="quiz.id">
            <div @click="$dispatch('open-quiz', { id: quiz.id })" 
     class="bg-white p-5 rounded-[28px] border border-gray-100 flex flex-col justify-between group hover:border-[#383838] cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-gray-100/50 active:scale-[0.98] animate-fade-in min-h-[220px]">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 w-8 h-8 shrink-0 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors"><i class="ri-survey-line text-sm"></i></div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-black text-[#383838] text-base tracking-tight leading-tight group-hover:text-black transition-colors truncate" x-text="quiz.title"></h4>
                            <div class="mt-1 flex items-center gap-1.5"><i class="ri-calendar-todo-line text-gray-400 text-[10px]"></i><span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide" x-text="formatDeadline(quiz.expires_at)"></span></div>
                        </div>
                    </div>
                    <div class="py-3 border-y border-gray-50">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Score:</span>
                        <div class="flex items-baseline gap-1"><span class="text-2xl font-black text-[#383838]" x-text="quiz.user_score !== undefined && quiz.user_score !== null ? quiz.user_score : '--'"></span><span class="text-xs font-bold text-gray-300" x-text="'/ ' + (quiz.total_points || quiz.questions_count)"></span></div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-3">
                    <template x-if="quiz.has_attempt">
                        <span class="flex items-center gap-1 text-[9px] font-black text-black uppercase tracking-tighter">
                            <i class="ri-checkbox-circle-fill text-base text-[#383838]"></i> Completed
                        </span>
                    </template>
                    <template x-if="!quiz.has_attempt">
                        <span class="flex items-center gap-1 text-[9px] font-black text-gray-400 uppercase tracking-tighter">
                            <i class="ri-error-warning-fill text-base text-gray-400"></i> Pending
                        </span>
                    </template>
                    <i class="ri-arrow-right-line text-gray-300 group-hover:text-[#383838] group-hover:translate-x-1 transition-all"></i>
                </div>
            </div>
        </template>
    </div>
    
    <div x-show="filteredQuizzes.length === 0" class="py-20 text-center"><i class="ri-survey-line text-4xl text-gray-200"></i><p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-4">No quizzes found</p></div>
</div>
                                
                                <div x-show="activeTab === 'materials'" x-data="classroomMaterials()" class="space-y-6 animate-fade-in">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <template x-for="material in materials" :key="material.id">
                                            <template x-if="material.type !== 'pptx'">
                                                <div @click="$dispatch('open-material', material)" class="bg-white p-5 rounded-[28px] border border-gray-100 flex flex-col justify-between group hover:border-[#383838] cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-gray-100/50 active:scale-[0.98] animate-fade-in min-h-[180px]">
                                                    <div class="space-y-4">
                                                        <div class="flex items-start gap-3">
                                                            <div class="mt-1 w-8 h-8 shrink-0 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                                                                <i :class="{ 'ri-file-pdf-line': material.type === 'pdf', 'ri-video-line': material.type === 'youtube', 'ri-file-line': material.type !== 'pdf' && material.type !== 'youtube' }" class="text-sm"></i>
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <h4 class="font-black text-[#383838] text-base tracking-tight leading-tight group-hover:text-black transition-colors truncate" :title="material.title" x-text="material.title"></h4>
                                                                <div class="mt-1 flex items-center gap-1.5"><span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide" x-text="material.type + ' Reference'"></span></div>
                                                            </div>
                                                        </div>
                                                        <div class="py-3 border-y border-gray-50">
                                                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Source Type</span>
                                                            <span class="text-xs font-black text-[#383838] uppercase" x-text="material.type === 'youtube' ? 'Video Lecture' : 'Reading Material'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center justify-between pt-3">
                                                        <span class="flex items-center gap-1 text-[9px] font-black text-gray-400 group-hover:text-black uppercase tracking-tighter transition-colors"><i class="ri-external-link-line text-base"></i> View Material</span>
                                                        <i class="ri-arrow-right-line text-gray-300 group-hover:text-[#383838] group-hover:translate-x-1 transition-all"></i>
                                                    </div>
                                                </div>
                                            </template>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </main>
            </div>
        </div> 
        
        <div x-show="sidebarOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full" 
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-300" 
             x-transition:leave-start="translate-x-0" 
             x-transition:leave-end="translate-x-full"
             class="w-1/2 h-full bg-white flex flex-col border-l border-gray-200 z-40 shrink-0 shadow-2xl">
            
            <div class="border-b border-gray-100 p-4 flex flex-col xl:flex-row justify-between items-center bg-white gap-4 shrink-0">
                <div class="flex flex-wrap items-center gap-2 bg-gray-50/50 p-1.5 rounded-[18px] border border-gray-100">
                    <!-- REMOVED: Live Tasks tab toggle button -->
                    <button @click="activeToolTab = 'compiler'"
                            :class="activeToolTab === 'compiler' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2 rounded-[12px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="ri-code-s-slash-line text-sm"></i> OneCompiler
                    </button>
                    <button @click="activeToolTab = 'browser'"
                            :class="activeToolTab === 'browser' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2 rounded-[12px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="ri-global-line text-sm"></i> Browser
                    </button>
                    <button @click="activeToolTab = 'document'"
                            :class="activeToolTab === 'document' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2 rounded-[12px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="ri-file-text-line text-sm"></i> Editor
                    </button>
                </div>
            </div>

            <div class="flex-grow w-full bg-gray-50 relative overflow-hidden flex flex-col">
                
                <!-- REMOVED: Live Tasks active content window panel container block -->

                <div x-show="activeToolTab === 'compiler'" class="w-full h-full">
                    <iframe src="https://onecompiler.com" class="w-full h-full border-none bg-white"></iframe>
                </div>

                <div x-show="activeToolTab === 'browser'" class="w-full h-full flex flex-col bg-white" x-data="browserManager()">
                    <div class="p-4 border-b border-gray-100 bg-white shrink-0">
                        <div class="flex items-center gap-3">
                            <button @click="browserBack()" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Go Back"><i class="ri-arrow-left-line text-xl"></i></button>
                            <button @click="browserForward()" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Go Forward"><i class="ri-arrow-right-line text-xl"></i></button>
                            <button @click="browserRefresh()" :class="refreshing ? 'animate-spin' : ''" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Refresh Page"><i class="ri-refresh-line text-xl"></i></button>
                            <div class="flex-1 flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2">
                                <i class="ri-global-line text-gray-400"></i>
                                <input type="text" x-model="urlInput" @keyup.enter="navigateTo()" placeholder="Search educational URL..." class="flex-1 bg-transparent border-0 focus:ring-0 text-sm p-0 focus:outline-none w-full">
                            </div>
                            <button @click="navigateTo()" :disabled="loadingUrl" :class="loadingUrl ? 'bg-gray-400' : 'bg-blue-600 hover:bg-blue-700'" class="px-6 py-2 text-white rounded-xl font-bold transition text-xs">
                                <span x-show="!loadingUrl">Go</span><span x-show="loadingUrl">...</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex-grow w-full bg-white relative">
                        <iframe id="dashboard-browser-frame" :src="browserUrl" class="w-full h-full border-none bg-white absolute inset-0"></iframe>
                    </div>
                </div>

                <div x-show="activeToolTab === 'document'" class="w-full h-full flex flex-col bg-white" 
                     x-data="{ docContent: '', get wordCount() { let text = this.docContent.trim(); return text ? text.split(/\s+/).length : 0; } }">
                    <div class="p-4 border-b border-gray-100 bg-white flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-semibold text-gray-500 flex items-center gap-1.5 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                                <i class="ri-text-spacing text-gray-400 text-sm"></i> Words: <span x-text="wordCount" class="font-bold text-gray-800">0</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="navigator.clipboard.writeText(docContent); alert('Content copied to clipboard!')" class="px-4 py-2 text-xs font-semibold bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-black rounded-xl border border-gray-100 transition flex items-center gap-1.5">
                                <i class="ri-file-copy-line"></i> Copy
                            </button>
                            <button @click="const blob = new Blob([docContent], { type: 'text/plain' }); const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = 'laboratory-notes.txt'; link.click();" class="px-4 py-2 text-xs font-bold bg-[#383838] text-white hover:bg-black rounded-xl transition flex items-center gap-1.5 shadow-sm">
                                <i class="ri-download-cloud-line"></i> Save
                            </button>
                        </div>
                    </div>
                    <div class="flex-grow w-full p-6 bg-gray-50/50">
                        <textarea x-model="docContent" placeholder="Type or paste your data text structure notes, source snippets, or answers for the assignment here..." class="w-full h-full resize-none bg-white border border-gray-200 focus:border-gray-400 focus:ring-4 focus:ring-gray-100 rounded-3xl p-6 text-sm text-gray-700 focus:outline-none transition-all shadow-sm leading-relaxed font-mono"></textarea>
                    </div>
                </div>

            </div>
        </div>

        <!-- UPDATED: Added x-show="isSharing" and x-cloak to toggle FAB visibility dynamically -->
        <button @click="sidebarOpen = !sidebarOpen" x-show="isSharing" x-cloak
            class="fixed bottom-6 right-6 z-[60000] bg-[#383838] hover:bg-black text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200 group border border-gray-700/10">
            <i :class="sidebarOpen ? 'ri-close-line' : 'ri-terminal-box-line'" class="text-2xl transition-transform group-hover:rotate-12"></i>
        </button>
    </div>

    <!-- Modals and script architectures remain below -->
   <!-- Modals and script architectures remain below -->
  <div id="task-modal" class="hidden fixed inset-0 z-[10000] bg-black/40 backdrop-blur-md flex items-center justify-center p-4" x-data="taskModal()" @open-task.window="openModal($event.detail)" @click.self="closeModal()">
    <div class="bg-white rounded-[40px] shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto animate-fade-in border border-zinc-100/80 [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-zinc-200 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-zinc-300 transition-colors" @click.stop>
        
        <div class="border-b border-zinc-100 p-8 flex justify-between items-start bg-gradient-to-b from-zinc-50/50 to-white rounded-t-[40px]">
            <div>
                <h2 class="text-3xl font-black text-zinc-900 tracking-tight leading-tight" x-text="currentTask?.title"></h2>
                <p class="text-sm text-zinc-500 mt-2 font-medium max-w-md" x-text="currentTask?.description"></p>
            </div>
            <button @click="closeModal()" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-zinc-50 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 transition-all shadow-sm border border-zinc-100">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <div class="p-8 space-y-8">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-zinc-50/60 p-5 rounded-[24px] border border-zinc-100/80">
                    <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest block mb-1">Max Weight</span>
                    <span class="text-xl font-black text-zinc-900" x-text="currentTask?.points + ' PTS'"></span>
                </div>
                <div class="bg-zinc-50/60 p-5 rounded-[24px] border border-zinc-100/80">
                    <span class="text-[10px] font-black text-zinc-400 uppercase tracking-widest block mb-1">Due Date</span>
                    <span class="text-sm font-bold text-zinc-600" x-text="formatDeadline(currentTask?.deadline)"></span>
                </div>
            </div>

            <div x-show="currentTask?.current_user_submission" class="space-y-4 animate-fade-in">
                <h3 class="font-black text-[10px] text-zinc-400 uppercase tracking-[0.2em] ml-1">Current Submission</h3>
                <div class="bg-white p-2 rounded-[32px] border border-zinc-100 shadow-sm">
                    <div class="flex items-center justify-between p-4 bg-zinc-50/50 rounded-[24px] border border-zinc-100/40">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-xl border border-zinc-100 flex items-center justify-center text-zinc-800 shadow-sm">
                                <i class="ri-file-3-line text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-bold text-zinc-900 text-sm truncate max-w-[200px]" x-text="currentTask?.current_user_submission?.original_filename"></p>
                                <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-tight">Logged: <span x-text="formatDate(currentTask?.current_user_submission?.submitted_at)"></span></p>
                            </div>
                        </div>
                        <a :href="'/' + currentTask?.current_user_submission?.file_path" target="_blank" class="w-10 h-10 flex items-center justify-center bg-white border border-zinc-100 rounded-xl text-zinc-400 hover:text-zinc-900 hover:border-zinc-300 shadow-sm transition-all">
                            <i class="ri-download-2-line"></i>
                        </a>
                    </div>

                    <div x-show="currentTask?.current_user_submission?.grade !== null" class="m-2 space-y-6">
                        
                        <div class="bg-gradient-to-br from-zinc-900 to-zinc-800 text-white p-6 rounded-[24px] shadow-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-widest opacity-60 block mb-1">Resulting Grade</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-4xl font-black text-white" x-text="detailedFeedback ? Number(detailedFeedback.total_score).toFixed(1) : currentTask?.current_user_submission?.grade"></span>
                                        <span class="text-xl opacity-50" x-text="'/ ' + (detailedFeedback ? detailedFeedback.max_score : currentTask?.points)"></span>
                                    </div>
                                </div>
                                
                                <template x-if="detailedFeedback && detailedFeedback.auto_graded">
                                    <div class="bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10 flex items-center gap-1.5">
                                        <i class="ri-robot-line text-sm text-blue-300"></i>
                                        <span class="text-[9px] font-black uppercase tracking-wider text-blue-50">AI Verified</span>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4 overflow-hidden h-2 flex rounded-full bg-white/10" x-show="detailedFeedback">
                                <div :style="`width: ${(detailedFeedback?.total_score / Math.max(1, detailedFeedback?.max_score)) * 100}%`"
                                     :class="detailedFeedback?.total_score >= (detailedFeedback?.max_score * 0.7) ? 'bg-emerald-400 shadow-emerald-500/20' : 'bg-amber-400 shadow-amber-500/20'"
                                     class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500 rounded-full">
                                </div>
                            </div>
                        </div>

                        <div x-show="currentTask?.current_user_submission?.feedback" class="pt-5 border-t border-zinc-100">
                            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-400 mb-2 ml-1">Remarks</p>
                            <div class="bg-zinc-50 border border-zinc-100 rounded-2xl p-4">
                                <p class="text-sm font-medium leading-relaxed text-zinc-700 whitespace-pre-line" x-text="currentTask?.current_user_submission?.feedback"></p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-black text-[10px] text-zinc-400 uppercase tracking-[0.2em] ml-1">📋 Evaluation Breakdown</h4>
                            
                            <div x-show="loadingFeedback" class="py-8 text-center bg-zinc-50 rounded-2xl border border-dashed border-zinc-200">
                                <div class="w-6 h-6 border-2 border-zinc-900 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                                <p class="text-[9px] font-black text-zinc-400 uppercase tracking-widest">Parsing Rubric Scoring Model Matrix...</p>
                            </div>

                            <div x-show="!loadingFeedback && detailedFeedback" class="space-y-4 max-h-[38vh] overflow-y-auto pr-2 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-zinc-200 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-zinc-300 transition-colors">
                                <template x-for="(score, index) in (detailedFeedback?.criterion_scores || detailedFeedback?.criterionScores || [])" :key="score.id">
                                    <div :class="getBorderClass(score)" class="border-l-4 bg-zinc-50/50 rounded-r-2xl p-4 border border-zinc-100 flex flex-col gap-2.5 transition hover:bg-zinc-50">
                                        
                                        <div class="flex justify-between items-start gap-4">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-0.5">
                                                    <span class="bg-zinc-200 text-zinc-700 font-black text-[10px] rounded-full w-5 h-5 flex items-center justify-center shrink-0" x-text="index + 1"></span>
                                                    <h5 class="font-black text-zinc-900 text-sm tracking-tight truncate" x-text="score.criterion?.criterion_name"></h5>
                                                </div>
                                                <p x-show="score.criterion?.description" class="text-[11px] text-zinc-500 ml-7 leading-tight" x-text="score.criterion?.description"></p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span :class="getTextColorClass(score)" class="text-lg font-black" x-text="Number(score.points_earned).toFixed(1)"></span>
                                                <span class="text-xs font-bold text-zinc-400" x-text="'/ ' + score.max_points"></span>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-1.5 ml-7">
                                            <span class="text-[9px] bg-zinc-200/80 text-zinc-700 px-2.5 py-0.5 rounded-md font-bold uppercase tracking-wide border border-zinc-200/20">
                                                <span x-text="typeIcons[score.criterion?.checking_type] || '📊'"></span>
                                                <span x-text="typeLabels[score.criterion?.checking_type] || 'Evaluation'"></span>
                                            </span>
                                            <span :class="score.auto_checked ? 'bg-blue-50/80 text-blue-700 border-blue-100' : 'bg-purple-50/80 text-purple-700 border-purple-100'" class="text-[9px] px-2.5 py-0.5 rounded-md font-bold uppercase tracking-wide border" x-text="score.auto_checked ? '🤖 Auto' : '👨‍🏫 Manual'"></span>
                                        </div>

                                        <div class="ml-7 overflow-hidden h-1 flex rounded-full bg-zinc-200">
                                            <div :style="`width: ${getPercentage(score)}%`" :class="getBgProgressClass(score)" class="shadow-none flex flex-col justify-center text-center rounded-full"></div>
                                        </div>

                                        <template x-if="score.feedback">
                                            <div class="ml-7 bg-white border border-zinc-100 rounded-xl p-3 shadow-sm">
                                                <p class="text-[9px] font-black text-zinc-400 uppercase tracking-widest mb-1">Feedback Remarks</p>
                                                <div class="text-xs text-zinc-700 whitespace-pre-line leading-relaxed font-medium" x-text="score.feedback"></div>
                                            </div>
                                        </template>

                                        <div class="flex items-center gap-1 mt-1 ml-7 text-[10px] font-black uppercase tracking-tight">
                                            <template x-if="score.points_earned >= score.max_points">
                                                <span class="text-emerald-600 flex items-center gap-1"><i class="ri-checkbox-circle-fill text-sm"></i> Perfect Score! 🎉</span>
                                            </template>
                                            <template x-if="score.points_earned < score.max_points && getPercentage(score) >= 70">
                                                <span class="text-blue-600 flex items-center gap-1"><i class="ri-check-line text-sm"></i> Good Achievement</span>
                                            </template>
                                            <template x-if="getPercentage(score) < 70 && getPercentage(score) >= 50">
                                                <span class="text-amber-600 flex items-center gap-1"><i class="ri-information-line text-sm"></i> Acceptable</span>
                                            </template>
                                            <template x-if="getPercentage(score) < 50">
                                                <span class="text-rose-600 flex items-center gap-1"><i class="ri-close-circle-line text-sm"></i> Needs Re-evaluation</span>
                                            </template>
                                        </div>

                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div x-show="currentTask?.current_user_submission?.grade === null" class="p-2">
                        <button @click="showResubmitForm = !showResubmitForm" class="w-full py-4 bg-zinc-100 text-zinc-800 border border-zinc-200/50 font-black rounded-[20px] hover:bg-zinc-900 hover:text-white hover:border-zinc-900 transition-all text-xs uppercase tracking-widest shadow-sm">
                            <i class="ri-edit-line mr-2"></i> Edit Submission
                        </button>
                        
                        <form x-show="showResubmitForm" @submit.prevent="resubmitTask($event)" class="mt-4 p-6 bg-zinc-50 rounded-[24px] border border-zinc-200/80 animate-fade-in" enctype="multipart/form-data">
                            <div class="flex items-center gap-2 text-[10px] text-zinc-500 font-bold mb-4 uppercase tracking-wider">
                                <i class="ri-information-line text-sm text-zinc-400"></i> Note: Existing file will be overwritten
                            </div>
                            <input type="file" name="submission" required class="block w-full text-xs text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:bg-zinc-900 file:text-white mb-4 cursor-pointer">
                            <button type="submit" :disabled="resubmitting" class="w-full py-3 bg-zinc-900 text-white font-black rounded-xl hover:bg-zinc-800 transition-all text-[10px] uppercase tracking-widest shadow-md">
                                <span x-show="!resubmitting">Update Work</span>
                                <span x-show="resubmitting" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-3 w-3 text-white" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg> Processing...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div x-show="!currentTask?.current_user_submission" class="space-y-4 animate-fade-in">
                <h3 class="font-black text-[10px] text-zinc-400 uppercase tracking-[0.2em] ml-1">Upload Work</h3>
                <form @submit.prevent="resubmitTask($event)" enctype="multipart/form-data" x-data="{ fileName: '' }" class="bg-zinc-50/50 border-2 border-dashed border-zinc-200 rounded-[32px] p-10 text-center transition-all hover:border-zinc-900 hover:bg-zinc-50 group relative">
                    <label class="block cursor-pointer">
                        <div class="space-y-4">
                            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-zinc-100 flex items-center justify-center mx-auto text-zinc-300 group-hover:text-zinc-900 group-hover:scale-105 transition-all">
                                <i class="ri-upload-cloud-2-line text-3xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-zinc-800" x-text="fileName ? fileName : 'Drag & drop or browse'"></p>
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-widest mt-1">
                                    <span x-show="!fileName">Select file for evaluation</span>
                                    <span x-show="fileName" class="text-emerald-600">File attached and ready</span>
                                </p>
                            </div>
                        </div>
                        <input type="file" name="submission" required class="hidden" @change="fileName = $event.target.files[0].name">
                    </label>
                    
                    <button type="submit" :disabled="resubmitting || !fileName" :class="fileName ? 'bg-zinc-900 hover:bg-zinc-800 text-white shadow-xl shadow-zinc-200/50' : 'bg-zinc-200 text-zinc-400 cursor-not-allowed'" class="mt-8 w-full py-4 font-black rounded-2xl transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-3">
                        <span x-show="!resubmitting">Upload Work</span>
                        <template x-if="resubmitting">
                            <div class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg> Sending...
                            </div>
                        </template>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
    <div x-data="{ showQuizModal: false, quizUrl: '', isLocked: false, open(id) { this.quizUrl = `/student/quizzes/${id}/attempt`; this.showQuizModal = true; this.isLocked = false; } }"
         x-show="showQuizModal" x-cloak @open-quiz.window="open($event.detail.id)"
         @message.window="if ($event.data === 'lock-modal') isLocked = true; if ($event.data === 'unlock-modal') isLocked = false; if ($event.data === 'close-modal') { showQuizModal = false; isLocked = false; }"
         class="fixed inset-0 z-[9999] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-6xl h-[92vh] rounded-[2rem] overflow-hidden shadow-2xl flex flex-col relative">
            <div class="flex justify-between items-center p-6 border-b bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse" x-show="isLocked"></div>
                    <h3 class="font-black text-gray-900 tracking-tight">QUIZ WORKSPACE</h3>
                </div>
                <button x-show="!isLocked" @click="showQuizModal = false" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all uppercase tracking-widest">Close</button>
                <span x-show="isLocked" class="text-[10px] font-black text-red-500 uppercase tracking-widest"><i class="ri-lock-fill mr-1"></i> Quiz in Progress (Locked)</span>
            </div>
            <div class="flex-grow bg-gray-50"><iframe :src="quizUrl" class="w-full h-full border-none shadow-inner"></iframe></div>
        </div>
    </div>

    <div x-data="materialViewer()" @open-material.window="openMaterial($event.detail)" x-show="showViewer" x-cloak @click.self="closeMaterial()" class="fixed inset-0 z-[20000] bg-black/40 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] shadow-2xl max-w-5xl w-full h-[85vh] flex flex-col overflow-hidden animate-fade-in border border-gray-100" @click.stop>
            <div class="border-b border-gray-50 p-6 flex justify-between items-center bg-white shrink-0">
                <div>
                    <h2 class="text-xl font-black text-[#383838] tracking-tight leading-tight truncate max-w-[300px] md:max-w-xl" x-text="currentMaterial.title"></h2>
                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest block mt-0.5" x-text="currentMaterial.type + ' Reference Material'"></span>
                </div>
                <div class="flex items-center gap-3"><button @click="closeMaterial()" class="px-6 py-2.5 bg-[#383838] text-white font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-[#2c2c2c] transition shadow-md">Finish Reading</button></div>
            </div>
            <div class="flex-grow w-full bg-gray-50 relative overflow-hidden">
                <div class="absolute inset-0 flex items-center justify-center -z-10 bg-gray-100"><div class="flex flex-col items-center gap-4"><div class="w-8 h-8 border-2 border-[#383838] border-t-transparent rounded-full animate-spin"></div></div></div>
                <template x-if="currentMaterial.type === 'pdf'"><iframe :src="currentMaterial.url" class="w-full h-full border-none block"></iframe></template>
                <template x-if="currentMaterial.type === 'youtube'"><div class="w-full h-full flex items-center justify-center bg-black"><iframe :src="currentMaterial.url" class="w-full h-full border-none" allow="autoplay; encrypted-media" allowfullscreen></iframe></div></template>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script>
    const classId = '{{ $class->id }}';
    const profPeerId = 'PROF_{{ $class->faculty_id }}';
    const csrfToken = '{{ csrf_token() }}';
    let studentPeer = null;
    let localScreenStream = null;
    let isCheckingStatus = false;
    let violationState = @json($violationStatus);

    function showScreenBlockOverlay() {
        const overlay = document.getElementById('screen-block-overlay');
        if (overlay) {
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        }
    }

    function hideScreenBlockOverlay() {
        const overlay = document.getElementById('screen-block-overlay');
        if (overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }
    }

    function showViolationWarning(message) {
        const modal = document.getElementById('violation-warning-modal');
        const messageEl = document.getElementById('violation-warning-message');
        if (!modal || !messageEl) return;
        messageEl.textContent = message;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideViolationWarning() {
        const modal = document.getElementById('violation-warning-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function applyEnforcementResult(result) {
        if (!result) return;

        violationState = {
            violation_count: result.violation_count ?? violationState.violation_count,
            threshold: result.threshold ?? violationState.threshold,
            remaining_warnings: result.remaining_warnings ?? violationState.remaining_warnings,
            is_screen_blocked: !!result.is_screen_blocked,
        };

        if (result.is_screen_blocked) {
            showScreenBlockOverlay();
            return;
        }

        if (result.action === 'warning' && result.message) {
            showViolationWarning(result.message);
        }
    }

    function handleHeartbeatEnforcement(data) {
        if (!data) return;

        violationState = {
            violation_count: data.violation_count ?? violationState.violation_count,
            threshold: data.threshold ?? violationState.threshold,
            remaining_warnings: data.remaining_warnings ?? violationState.remaining_warnings,
            is_screen_blocked: !!data.is_screen_blocked,
        };

        if (data.is_screen_blocked) {
            showScreenBlockOverlay();
        } else {
            hideScreenBlockOverlay();
        }
    }

    if (violationState.is_screen_blocked) {
        document.addEventListener('DOMContentLoaded', showScreenBlockOverlay);
    }

    
    const localPeerOptions = {
        host: '127.0.0.1',
        port: 9000,
        path: '/myapp',
        secure: false,
        config: {
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        },
        pingInterval: 5000, // Sends a ping every 5 seconds to prevent Cloudflare from dropping it
        debug: 1            // 1 = Errors only, 2 = Warnings, 3 = Everything
    };


    function verifySessionStatus() {
        if (isCheckingStatus) return;
        isCheckingStatus = true;

        fetch(`/student/heartbeat/${classId}`, { 
            method: 'POST', 
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' } 
        })
        .then(res => res.json())
        .then(data => {
            isCheckingStatus = false;
            handleHeartbeatEnforcement(data);
            if (data && (data.is_active === false || data.status === 'inactive' || data.session_active === false)) {
                console.log("⚠️ Backend confirmed session is offline. Reloading workspace view...");
                cleanupHardwareAndReload();
            }
        })
        .catch(err => {
            isCheckingStatus = false;
            console.error("Status synchronization verification check failed:", err);
        });
    }

    function cleanupHardwareAndReload() {
        if (localScreenStream) {
            localScreenStream.getTracks().forEach(track => track.stop());
        }
        if (studentPeer) {
            studentPeer.destroy();
        }
        window.location.reload();
    }
    

    // ⚡ UNIFIED ENTRYPOINT: Triggered only when the student explicitly clicks the interface button.
    async function enterClassroom() {
        try {
            console.log("📹 Accessing student display media for monitoring...");
            // Request the feed using high-performance constraints suited for production
            localScreenStream = await navigator.mediaDevices.getDisplayMedia({
                video: { width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 15 } },
                audio: false
            });

            // Unlock and swap out the onboarding placeholder card for the operational workspace layout grid
            window.dispatchEvent(new CustomEvent('screen-shared'));

            console.log("📡 Dialing professor monitoring node:", profPeerId);
            const call = studentPeer.call(profPeerId, localScreenStream, {
                metadata: { 
                    studentId: {{ auth()->id() }}, 
                    studentName: '{{ auth()->user()->name ?? "Student" }}' 
                }
            });

            // Log present attendance row metrics to server storage
            fetch("{{ route('student.mark-present', $class->id) }}", { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' } 
            });

            if (call) {
                call.on('error', err => {
                    console.error("Student monitor call link connection error:", err);
                    verifySessionStatus();
                });
                call.on('close', () => {
                    console.log("🔴 Student monitor link terminated by target server.");
                    verifySessionStatus();
                });
            }

            // Track if the student manually closes screen capture using browser navigation overlays
            localScreenStream.getVideoTracks()[0].onended = function () {
                console.warn("⚠️ Screen capture tracking disconnected by user.");
                window.dispatchEvent(new CustomEvent('screen-stopped'));
                cleanupHardwareAndReload();
            };

        } catch (err) {
            console.error("Screen capture engagement process cancelled or failed:", err);
            alert("Screen sharing authentication is mandatory to gain entrance inside this laboratory room workspace environment.");
        }
    }


    document.addEventListener('DOMContentLoaded', () => {
        studentPeer = new Peer('STUDENT_{{ auth()->id() }}', localPeerOptions);

        studentPeer.on('open', (id) => {
            console.log("✅ Student connected to local PeerJS server with ID:", id);
            // 🛑 REMOVED: startScreenShareToProfessor() call. No more automatic prompts on load!
        });

        // Listen for the incoming broadcast lecture channel feed from the instructor panel context 
        studentPeer.on('call', (call) => {
            console.log("📞 Incoming lecture stream call from professor...");
            call.answer();

            call.on('stream', (stream) => {
                console.log("🟢 Lecture stream frame buffers received!");
                const videoElement = document.getElementById('professor-screen');
                
                if (videoElement) {
                    videoElement.srcObject = stream;
                    videoElement.onloadedmetadata = () => {
                        videoElement.play()
                            .then(() => {
                                document.getElementById('lockdown-ui').classList.remove('hidden');
                                document.getElementById('normal-view').classList.add('hidden');
                            })
                            .catch(err => console.error("Playback engine engagement failed:", err));
                    };
                }
            });

            const clearBroadcastView = () => {
                console.log("🔴 Screen share session terminated.");
                const videoElement = document.getElementById('professor-screen');
                if (videoElement) videoElement.srcObject = null;
                document.getElementById('lockdown-ui').classList.add('hidden');
                document.getElementById('normal-view').classList.remove('hidden');
                verifySessionStatus();
            };

            call.on('close', clearBroadcastView);
            call.on('error', clearBroadcastView);
        });

        studentPeer.on('error', (err) => {
            console.error("Student Peer Connection Error:", err);
            verifySessionStatus();
        });

        // High-frequency active heartbeat pooling task tracker to watch for structural session state kills
        setInterval(() => {
            fetch(`/student/heartbeat/${classId}`, { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' } 
            })
            .then(res => res.json())
            .then(data => {
                handleHeartbeatEnforcement(data);
                if (data && data.is_active === false) {
                    console.log("⚡ Session has been terminated by the instructor. Refreshing UI...");
                    cleanupHardwareAndReload();
                }
            })
            .catch(err => console.error("Heartbeat communication sync error:", err));
        }, 4000);
    });
</script>
<script>

        async function enterClassroom() {
            try {
                const stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
                window.dispatchEvent(new CustomEvent('screen-shared'));
                const call = studentPeer.call(profPeerId, stream, { metadata: { studentId: {{ auth()->id() }}, studentName: '{{ auth()->user()->name }}' } });
                fetch("{{ route('student.mark-present', $class->id) }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' } });
                stream.getVideoTracks()[0].onended = () => { window.dispatchEvent(new CustomEvent('screen-stopped')); location.reload(); };
            } catch (err) { console.error("Capture Failed", err); }
        }

        function classroomTasks() {
            return {
                tasks: [], filter: 'all',
                init() { this.fetchTasks(); setInterval(() => this.fetchTasks(), 5000); },
                fetchTasks() { fetch(`/student/classroom/${classId}/live-tasks`).then(res => res.json()).then(data => this.tasks = data); },
                get filteredTasks() {
                    if (this.filter === 'submitted') return this.tasks.filter(t => t.current_user_submission !== null);
                    if (this.filter === 'missing') return this.tasks.filter(t => t.current_user_submission === null);
                    return this.tasks;
                },
                formatDeadline(deadline) { 
                    if (!deadline) return 'No deadline';
                    const date = new Date(deadline);
                    return isNaN(date.getTime()) ? deadline : date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                }
            }
        }

        @php
            $initialQuizzes = $class->quizzes()->where('published_at', '<=', now())->get()->map(function($quiz) {
                $attempt = $quiz->attempts()->where('user_id', auth()->id())->first();
                return ['id' => $quiz->id, 'title' => $quiz->title, 'expires_at' => $quiz->expires_at, 'questions_count' => $quiz->questions_count, 'total_points' => $quiz->total_points ?? $quiz->questions_count, 'has_attempt' => (bool)$attempt, 'user_score' => $attempt ? $attempt->score : null];
            });
        @endphp

        function classroomQuizzes() {
            return {
                quizzes: @json($initialQuizzes), filter: 'all',
                init() { setInterval(() => this.fetchQuizzes(), 5000); },
                fetchQuizzes() { fetch(`/student/classroom/${classId}/live-quizzes`).then(res => res.json()).then(data => { this.quizzes = data; }).catch(err => console.error(err)); },
                get filteredQuizzes() {
                    if (this.filter === 'completed') return this.quizzes.filter(q => q.has_attempt);
                    if (this.filter === 'pending') return this.quizzes.filter(q => !q.has_attempt);
                    return this.quizzes;
                },
                formatDeadline(dateString) {
                    if (!dateString) return 'No Deadline';
                    const date = new Date(dateString);
                    return isNaN(date.getTime()) ? 'No Deadline' : date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                },
                handleQuizClick(quiz) {
                    // If the student already completed it, stop them right here!
                    if (quiz.has_attempt) {
                        alert("You have already completed this quiz! You cannot retake it.");
                        return;
                    }

                    // Otherwise, open the modal normally
                    this.selectedQuiz = quiz; 
                    this.quizModalOpen = true;
                }
            }
        }

        @php
            $initialMaterials = $class->materials->map(function($m) {
                $url = $m->content;
                if ($m->type === 'youtube') $url = \Illuminate\Support\Str::contains($url, 'embed') ? $url : \Illuminate\Support\Str::replace('watch?v=', 'embed/', $url);
                else $url = url('/' . $url);
                return ['id' => $m->id, 'title' => $m->title, 'type' => $m->type, 'url' => $url];
            });
        @endphp

       function classroomMaterials() {
    return {
        materials: @json($initialMaterials),
        init() { setInterval(() => this.fetchMaterials(), 5000); },
        fetchMaterials() { 
            fetch(`/student/classroom/${classId}/live-materials`)
                .then(res => res.json())
                .then(data => { this.materials = data; })
                .catch(err => console.error(err)); 
        }
    };
}

function materialViewer() {
    return {
        showViewer: false, 
        currentMaterial: { title: '', type: '', url: '', id: null }, 
        startTime: null,
        openMaterial(material) {
            this.currentMaterial = material; 
            this.showViewer = true; 
            this.startTime = new Date();
            fetch(`/student/materials/${material.id}/log-start`, { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } 
            });
        },
        closeMaterial() {
            let endTime = new Date(); 
            let duration = Math.round((endTime - this.startTime) / 1000);
            fetch(`/student/materials/${this.currentMaterial.id}/log-end`, { 
                method: 'POST', 
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken, 
                    'Accept': 'application/json' 
                }, 
                body: JSON.stringify({ duration: duration }) 
            }).then(() => {
                this.showViewer = false; 
                this.currentMaterial = { title: '', type: '', url: '', id: null };
            });
        }
    };
}

       function taskModal() {
            return {
                currentTask: null, 
                showResubmitForm: false, 
                resubmitting: false,
                loadingFeedback: false, 
                detailedFeedback: null,
                typeIcons: { code: '💻', keyword: '🔍', text: '📝', file: '📁', ai: '🤖', manual: '✋' },
                typeLabels: { code: 'Code Execution', keyword: 'Keyword Detection', text: 'Text Analysis', file: 'File Validation', ai: 'AI Evaluation', manual: 'Manual Grading' },
                
                formatDate(dateString) {
                    if (!dateString) return 'N/A'; const date = new Date(dateString);
                    return isNaN(date.getTime()) ? dateString : date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
                },
                formatDeadline(deadline) {
                    if (!deadline) return 'No deadline'; const date = new Date(deadline);
                    return isNaN(date.getTime()) ? deadline : date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
                },
                openModal(task) { 
                    this.currentTask = task; 
                    this.showResubmitForm = false; 
                    this.detailedFeedback = null;
                    document.getElementById('task-modal').classList.remove('hidden'); 
                    
                    // Fire asynchronous request to grab criteria breakdowns if the task has already been graded
                    if (task.current_user_submission && task.current_user_submission.grade !== null) {
                        this.fetchDetailedFeedback(task.id);
                    }
                },
                closeModal() { 
                    document.getElementById('task-modal').classList.add('hidden'); 
                    this.currentTask = null; 
                    this.detailedFeedback = null; 
                },
                async fetchDetailedFeedback(taskId) {
                    this.loadingFeedback = true;
                    try {
                        const res = await fetch(`/student/tasks/${taskId}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            this.detailedFeedback = data.submissionGrade;
                        }
                    } catch (err) {
                        console.error("Failed to load task criteria feedback logs:", err);
                    } finally {
                        this.loadingFeedback = false;
                    }
                },
                getPercentage(score) {
                    return score.max_points > 0 ? (score.points_earned / score.max_points) * 100 : 0;
                },
                getBorderClass(score) {
                    let pct = this.getPercentage(score);
                    if (Number(score.points_earned) >= Number(score.max_points)) return 'border-green-500';
                    if (pct >= 70) return 'border-blue-500';
                    if (pct >= 50) return 'border-yellow-500';
                    return 'border-red-500';
                },
                getTextColorClass(score) {
                    let pct = this.getPercentage(score);
                    if (Number(score.points_earned) >= Number(score.max_points)) return 'text-green-600';
                    if (pct >= 70) return 'text-blue-600';
                    if (pct >= 50) return 'text-yellow-600';
                    return 'text-red-600';
                },
                getBgProgressClass(score) {
                    let pct = this.getPercentage(score);
                    if (Number(score.points_earned) >= Number(score.max_points)) return 'bg-green-500';
                    if (pct >= 70) return 'bg-blue-500';
                    if (pct >= 50) return 'bg-yellow-500';
                    return 'bg-red-500';
                },
                async resubmitTask(event) {
                    if (event) event.preventDefault(); if (!confirm('Upload Work?')) return;
                    this.resubmitting = true; const formData = new FormData(event.target);
                    try {
                        const res = await fetch(`/student/tasks/${this.currentTask.id}/submit`, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } });
                        if (res.ok) { alert('✅ Upload Successful!'); this.closeModal(); window.dispatchEvent(new CustomEvent('task-updated')); }
                        else { const err = await res.json(); alert('❌ Error: ' + (err.message || 'Upload failed')); }
                    } catch (error) { alert('❌ Network Error'); } finally { this.resubmitting = false; }
                }
            }
        }

        function browserManager() { 
            return {
                classId: classId, csrfToken: csrfToken, browserUrl: classId ? `/student/classroom/${classId}/browser-home` : 'about:blank', 
                urlInput: '', loadingUrl: false, refreshing: false, historyStack: classId ? [`/student/classroom/${classId}/browser-home`] : ['about:blank'],
                historyIndex: 0, blockedSites: [], 
                init() {
                    if (!this.classId || this.classId === 'undefined') return;
                    this.loadBlockedRules();
                    window.addEventListener('message', (event) => { if (event.data && event.data.type === 'iframe-navigate') { this.urlInput = event.data.url; this.navigateTo(); } });
                    if (window.ipcRenderer) { window.ipcRenderer.on('site-blocked-by-electron', (event, url) => { this.showBlockedPage(`"${this.cleanDomain(url)}" is restricted by the Instructor.`); this.logViolationAttempt(url); }); }
                },
                async loadBlockedRules() {
                    try {
                        const res = await fetch(`/student/classroom/${this.classId}/allowed-sites`);
                        if (res.ok) {
                            const data = await res.json(); this.blockedSites = [...(data.pre_approved || []), ...(data.session_sites || []), ...(data.task_sites || [])];
                            if (window.ipcRenderer) window.ipcRenderer.send('update-blocklist', this.blockedSites.map(site => site.domain.replace(/^www\./, '').toLowerCase().trim()));
                        }
                    } catch (err) { console.error("Failed to load blocklist", err); }
                },
                cleanDomain(url) { try { let domain = url.includes('://') ? url.split('://')[1] : url; return domain.split('/')[0].split('?')[0].replace(/^www\./, '').toLowerCase().trim(); } catch (e) { return url; } },
                isSiteBlocked(url) { const currentDomain = this.cleanDomain(url); return this.blockedSites.some(site => { const blockedDomain = site.domain.replace(/^www\./, '').toLowerCase().trim(); return currentDomain === blockedDomain || currentDomain.endsWith('.' + blockedDomain); }); },
                navigateTo(preserveHistory = true) {
                    let input = this.urlInput.trim(); if (!input) return;
                    const isUrl = (input.includes('.') && !input.includes(' ')) || input.startsWith('http');
                    if (!isUrl) {
                        this.loadingUrl = true; const lowerInput = input.toLowerCase();
                        if (this.blockedSites.some(site => { const keyword = site.domain.replace(/^www\./, '').toLowerCase().trim().split('.')[0]; return keyword.length > 2 && lowerInput.includes(keyword); })) {
                            this.loadingUrl = false; this.showBlockedPage(`Search query contains restricted keyword terms.`); this.logViolationAttempt(null, 'Attempted restricted search: ' + input); this.urlInput = ''; return;
                        }
                        this.browserUrl = `/student/classroom/${this.classId}/search?q=${encodeURIComponent(input)}`;
                        if (preserveHistory) { this.historyStack = this.historyStack.slice(0, this.historyIndex + 1); this.historyStack.push(this.browserUrl); this.historyIndex = this.historyStack.length - 1; }
                        const frame = document.getElementById('dashboard-browser-frame'); if (frame) frame.src = this.browserUrl;
                        this.urlInput = ''; this.loadingUrl = false; return;
                    }
                    let url = input.toLowerCase(); if (!url.startsWith('http')) url = 'https://' + url;
                    this.loadingUrl = true;
                    if (this.isSiteBlocked(url)) { this.loadingUrl = false; this.showBlockedPage(`"${this.cleanDomain(url)}" is blocked.`); this.logViolationAttempt(url); this.urlInput = ''; return; }
                    if (preserveHistory) { this.historyStack = this.historyStack.slice(0, this.historyIndex + 1); this.historyStack.push(url); this.historyIndex = this.historyStack.length - 1; }
                    this.browserUrl = url; const frame = document.getElementById('dashboard-browser-frame'); if (frame) frame.src = url;
                    this.logSiteVisit(url); this.urlInput = ''; this.loadingUrl = false;
                },
                browserBack() { if (this.historyIndex > 0) { this.historyIndex--; this.loadUrlFromHistory(this.historyStack[this.historyIndex]); } },
                browserForward() { if (this.historyIndex < this.historyStack.length - 1) { this.historyIndex++; this.loadUrlFromHistory(this.historyStack[this.historyIndex]); } },
                loadUrlFromHistory(url) {
                    this.loadingUrl = true;
                    if (url.includes('/browser-home') || url.includes('/search?q=')) { this.browserUrl = url; const frame = document.getElementById('dashboard-browser-frame'); if (frame) frame.src = url; this.loadingUrl = false; return; }
                    if (this.isSiteBlocked(url)) { this.loadingUrl = false; this.showBlockedPage(`"${this.cleanDomain(url)}" is restricted.`); this.logViolationAttempt(url); return; }
                    this.browserUrl = url; const frame = document.getElementById('dashboard-browser-frame'); if (frame) frame.src = url; this.loadingUrl = false;
                },
                browserRefresh() {
                    this.refreshing = true; const frame = document.getElementById('dashboard-browser-frame');
                    if (frame) { const currentUrl = frame.src; frame.src = 'about:blank'; setTimeout(() => { frame.src = currentUrl; this.refreshing = false; }, 100); }
                },
                showBlockedPage(reason) {
                    const blockedHtml = `<!DOCTYPE html><html><body style="font-family:sans-serif; background:#f8f9fa; display:flex; align-items:center; justify-content:center; height:100vh; margin:0;"><div style="background:white; padding:40px; border-radius:12px; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.1); max-width:400px; border:1px solid #fee2e2;"><div style="font-size:50px; margin-bottom:10px;">🚫</div><div style="color:#991b1b; background:#fef2f2; padding:15px; border-radius:8px; font-weight:bold;">${reason}</div><p style="color:#6b7280; font-size:12px; margin-top:15px;">This website or search keyword is restricted during this laboratory session.</p></div></body></html>`;
                    this.browserUrl = URL.createObjectURL(new Blob([blockedHtml], { type: 'text/html' })); const frame = document.getElementById('dashboard-browser-frame'); if (frame) frame.src = this.browserUrl;
                },
                logViolationAttempt(targetUrl, detail = null) {
                    const payload = {
                        type: 'violation',
                        detail: detail || `Attempted to access blocked site: ${targetUrl ? this.cleanDomain(targetUrl) : 'restricted content'}`,
                        lab_session_id: this.classId,
                    };
                    if (targetUrl) payload.url = targetUrl;

                    fetch('/student/log-behavior', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => applyEnforcementResult(data))
                    .catch(err => console.error(err));
                },
                logSiteVisit(targetUrl) { if (targetUrl.startsWith('blob:') || targetUrl.includes('/search?q=')) return; fetch('/student/log-behavior', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }, body: JSON.stringify({ type: 'navigation', detail: targetUrl, lab_session_id: this.classId }) }).catch(err => console.error(err)); }
            };
        }
    </script>

    <style>
        @keyframes fade-in { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
        .animate-bounce-short { animation: bounce 1s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>