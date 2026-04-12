<x-app-layout>
    <div id="lockdown-ui" 
         class="hidden fixed inset-0 z-[9999] bg-white w-full h-screen"
         x-data="{ workspaceOpen: true }"> <div class="flex w-full h-full">
            <div :class="workspaceOpen ? 'w-1/2' : 'w-full'" class="h-full bg-black relative transition-all duration-500 ease-in-out">
                <video id="professor-screen" autoplay playsinline muted class="w-full h-full object-contain"></video>
                
                <div class="absolute top-4 left-4 flex gap-3">
                    <button 
                        x-show="workspaceOpen"
                        class="px-6 py-2 bg-gray-800 text-white rounded-lg font-bold hover:bg-red-600 transition flex items-center gap-2 shadow-2xl"
                        @click="workspaceOpen = false">
                        <i class="ri-layout-right-line"></i> CLOSE WORKSPACE
                    </button>

                    <button 
                        x-show="!workspaceOpen"
                        style="display: none;"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2 shadow-2xl animate-bounce-short"
                        @click="workspaceOpen = true">
                        <i class="ri-layout-right-fill"></i> OPEN WORKSPACE
                    </button>
                </div>
            </div>

            <div x-show="workspaceOpen" 
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-500"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-1/2 h-full bg-gray-50 flex flex-col border-l border-gray-200" 
                 x-data="lockdownWorkspace()">
                 
                <div class="flex border-b border-gray-300 bg-white">
                    <button @click="tab = 'activities'" 
                            :class="tab === 'activities' ? 'border-b-2 border-black font-black text-black' : 'text-gray-400 font-bold'" 
                            class="px-8 py-4 text-[10px] uppercase tracking-widest transition-all">
                        Activities
                    </button>
                    <button @click="tab = 'browser'" 
                            :class="tab === 'browser' ? 'border-b-2 border-black font-black text-black' : 'text-gray-400 font-bold'" 
                            class="px-8 py-4 text-[10px] uppercase tracking-widest transition-all">
                        Research Browser
                    </button>
                </div>

                <div x-show="tab === 'activities'" class="flex-1 overflow-y-auto p-8">
                    <h2 class="text-2xl font-black text-gray-800 mb-2 tracking-tight">Workspace</h2>
                    <p class="text-sm text-gray-500 uppercase font-bold tracking-widest mb-6 text-[10px]">Active Tasks</p>
                    
                    <div class="space-y-4">
                        <template x-for="task in tasks" :key="task.id">
                            <div class="p-6 bg-white rounded-2xl border border-gray-200 shadow-sm animate-fade-in">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-lg text-gray-900" x-text="task.title"></h3>
                                        <p class="text-gray-600 mt-2 text-sm" x-text="task.description"></p>
                                    </div>
                                    <span class="text-xs font-black text-purple-600 bg-purple-50 px-3 py-1 rounded-full ml-4" 
                                          x-text="task.points + ' PTS'"></span>
                                </div>
                                
                                <form @submit.prevent="submitTask(task.id, $event)" enctype="multipart/form-data">
                                    <div class="mb-4" x-data="{ fileName: '' }">
                                        <label class="cursor-pointer group block">
                                            <div class="flex items-center justify-center w-full px-4 py-4 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl group-hover:bg-gray-100 group-hover:border-green-500 transition-all">
                                                <div class="flex items-center gap-3 w-full">
                                                    <div class="p-2 bg-white rounded-lg shadow-sm border border-gray-200">
                                                        <i class="ri-folder-upload-line text-lg"></i>
                                                    </div>
                                                    <div class="flex-1 overflow-hidden">
                                                        <span class="text-sm font-bold text-gray-700 block truncate" 
                                                              x-text="fileName || 'Click to browse files'"></span>
                                                        <span class="text-[10px] text-gray-400 uppercase font-black" 
                                                              x-text="fileName ? 'Ready to submit' : 'Select your work'"></span>
                                                    </div>
                                                </div>
                                                <input type="file" name="submission" required class="hidden" 
                                                       @change="fileName = $event.target.files[0]?.name || ''">
                                            </div>
                                        </label>
                                    </div>
                                    <button type="submit" 
                                            :disabled="uploadingTaskId === task.id"
                                            :class="uploadingTaskId === task.id ? 'bg-gray-400' : 'bg-green-600 hover:bg-green-700'"
                                            class="w-full py-3 text-white font-black rounded-xl transition uppercase text-[10px] tracking-widest shadow-lg shadow-green-100">
                                        <span x-show="uploadingTaskId === task.id">Uploading...</span>
                                        <span x-show="uploadingTaskId !== task.id">Submit Task</span>
                                    </button>
                                </form>
                            </div>
                        </template>
                        
                        <div x-show="tasks.length === 0" class="text-center py-20 border-2 border-dashed border-gray-200 rounded-3xl bg-white/50">
                            <i class="ri-checkbox-circle-line text-green-400 text-6xl"></i>
                            <p class="text-gray-700 font-black mt-4 text-xl tracking-tight">All tasks submitted!</p>
                            <p class="text-gray-400 text-xs mt-1 uppercase font-bold tracking-widest">Great job!</p>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'browser'" class="flex-1 p-8 flex flex-col" x-data="browserManager()">
                    <div class="flex gap-2 mb-4 bg-white p-2 rounded-2xl border border-gray-200 shadow-sm">
                        <input type="text" x-model="urlInput" @keyup.enter="navigateTo()" 
                               placeholder="Search Google or enter educational URL..." 
                               class="flex-1 border-none bg-gray-50 rounded-xl text-xs px-4 py-2 focus:ring-0">
                        <button @click="refresh()" class="p-2 text-gray-400 hover:text-black transition">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                    <iframe id="lockdown-frame" :src="browserUrl" class="w-full flex-1 rounded-2xl border border-gray-200 bg-white shadow-inner"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div id="normal-view" class="flex h-screen bg-gray-50" x-data="{ isSharing: false, activeTab: 'activities' }">
        <main class="flex-1 p-8 overflow-y-auto" @screen-shared.window="isSharing = true" @screen-stopped.window="isSharing = false">
            <div class="max-w-5xl mx-auto space-y-6">
                <div class="bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6">
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

                @if(!$class->is_active)
                    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-12 text-center shadow-inner">
                        <i class="ri-error-warning-line text-5xl text-amber-500 mb-4 block"></i>
                        <p class="font-black text-amber-900 text-xl">Session Offline</p>
                        <p class="text-amber-700 font-medium">The instructor has not initialized the laboratory session yet.</p>
                    </div>
                @else
                    <div x-show="!isSharing" class="flex flex-col items-center justify-center bg-white p-12 rounded-[40px] border border-gray-200 shadow-sm text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                            <i class="ri-macbook-line text-4xl text-[#383838]"></i>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 mb-2 tracking-tight">Security Check Required</h3>
                        <p class="text-gray-500 mb-8 max-w-md">To maintain lab integrity, share your <span class="font-bold text-gray-800 underline underline-offset-4">Entire Screen</span> to unlock the classroom dashboard.</p>
                        <button onclick="enterClassroom()" class="bg-black text-white px-10 py-4 rounded-2xl shadow-xl hover:bg-gray-800 font-black transition-all hover:scale-105">
                            Share Screen & Enter Classroom
                        </button>
                    </div>

                    <div x-show="isSharing" x-cloak class="animate-fade-in">
                        <div class="flex border-b border-gray-200 mb-8">
                            <template x-for="t in ['activities', 'quizzes', 'materials', 'classmates']">
                                <button @click="activeTab = t" 
                                        :class="activeTab === t ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 hover:text-gray-600 font-bold'" 
                                        class="px-8 py-4 text-[10px] uppercase tracking-widest transition" 
                                        x-text="t"></button>
                            </template>
                        </div>

                        <div class="bg-white border-2 border-dashed border-gray-200 rounded-[30px] p-10 text-center mb-10 shadow-sm">
                             <div class="ri-broadcast-line text-5xl text-green-500 animate-pulse mb-4"></div>
                             <h2 class="text-xl font-black text-gray-900 tracking-tight">Monitoring Active</h2>
                             <p class="text-xs text-gray-500 max-w-md mx-auto">Your screen is being broadcast to the instructor. Lockdown workspace will trigger automatically during demonstrations.</p>
                        </div>

                        <div x-show="activeTab === 'activities'" x-data="classroomTasks()" class="space-y-6">
                            <template x-for="task in tasks" :key="task.id">
                                <div class="bg-white p-6 rounded-2xl border border-gray-200 flex justify-between items-center group hover:border-black transition-all hover:shadow-lg shadow-gray-100">
                                    <div>
                                        <h4 class="font-bold text-gray-900" x-text="task.title"></h4>
                                        <p class="text-xs text-gray-400 font-medium" x-text="task.description"></p>
                                        <span x-show="task.current_user_submission" class="text-[9px] font-black bg-green-100 text-green-700 px-2 py-0.5 rounded-full mt-2 inline-block uppercase">
                                            ✓ Submitted
                                        </span>
                                    </div>
                                    <button @click="openTaskModal(task)" 
                                            class="bg-gray-100 text-[#383838] px-6 py-2 rounded-xl text-[10px] font-black uppercase group-hover:bg-black group-hover:text-white transition tracking-widest">
                                        View Task
                                    </button>
                                </div>
                            </template>

                            <div class="bg-white border border-gray-200 rounded-[30px] overflow-hidden shadow-2xl mt-12" x-data="browserManager()">
                                <div class="bg-white p-4 border-b border-gray-100 flex items-center gap-3">
                                    <span class="bg-gray-100 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-tighter shadow-sm">
                                        <i class="ri-google-fill mr-1 text-blue-500"></i> Google Search
                                    </span>
                                    <div class="flex-1 bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 text-[10px] text-gray-400 italic font-medium">Secure Research Mode: Access restricted to whitelisted domains.</div>
                                </div>
                                <iframe :src="browserUrl" class="w-full h-[500px] border-none"></iframe>
                            </div>
                        </div>

                        <div x-show="activeTab === 'materials'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($class->materials as $material)
                                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex items-center justify-between hover:shadow-md transition-all group">
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 bg-gray-50 rounded-2xl group-hover:bg-[#383838] transition-colors">
                                            @if($material->type == 'pdf') <i class="ri-file-pdf-fill text-red-500 group-hover:text-white text-xl"></i>
                                            @elseif($material->type == 'youtube') <i class="ri-youtube-fill text-red-600 group-hover:text-white text-xl"></i>
                                            @else <i class="ri-file-ppt-2-fill text-orange-500 group-hover:text-white text-xl"></i> @endif
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-sm text-gray-900 truncate max-w-[150px]">{{ $material->title }}</h4>
                                            <p class="text-[9px] uppercase font-black text-gray-400 tracking-tighter">{{ $material->type }} Document</p>
                                        </div>
                                    </div>
                                    <a href="{{ $material->type === 'youtube' ? $material->content : url($material->content) }}" target="_blank" class="p-2 hover:bg-gray-100 rounded-full transition-colors"><i class="ri-external-link-line text-gray-400"></i></a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>

    <div id="task-modal" 
         class="hidden fixed inset-0 z-[10000] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
         x-data="taskModal()"
         @click.self="closeModal()">
        
        <div class="bg-white rounded-[40px] shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-fade-in" @click.stop>
            <div class="border-b border-gray-100 p-8 flex justify-between items-start">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight" x-text="currentTask?.title"></h2>
                    <p class="text-sm text-gray-500 mt-2 font-medium" x-text="currentTask?.description"></p>
                </div>
                <button @click="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-50 text-gray-400 hover:text-black transition">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <div class="p-8 space-y-8">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Max Weight</span>
                        <span class="text-lg font-black text-purple-600" x-text="currentTask?.points + ' PTS'"></span>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Due Date</span>
                        <span class="text-sm font-bold text-gray-800" x-text="formatDeadline(currentTask?.deadline)"></span>
                    </div>
                </div>

                <div x-show="currentTask?.current_user_submission" class="space-y-4">
                    <h3 class="font-black text-xs text-gray-400 uppercase tracking-widest ml-1">Your Submission</h3>
                    
                    <div class="bg-white p-6 rounded-[30px] border-2 border-gray-100 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-400"><i class="ri-file-3-line text-2xl"></i></div>
                                <div>
                                    <p class="font-bold text-gray-900 text-sm" x-text="currentTask?.current_user_submission?.original_filename"></p>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">Received: <span x-text="formatDate(currentTask?.current_user_submission?.submitted_at)"></span></p>
                                </div>
                            </div>
                            <a :href="'/' + currentTask?.current_user_submission?.file_path" target="_blank" class="p-3 bg-gray-50 rounded-xl text-gray-400 hover:text-black transition-colors"><i class="ri-download-2-line"></i></a>
                        </div>

                        <div x-show="currentTask?.current_user_submission?.grade !== null" class="p-6 bg-gradient-to-br from-blue-600 to-[#383838] rounded-3xl text-white shadow-xl shadow-blue-100 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-black uppercase tracking-widest opacity-80">Official Grade</span>
                                <span class="text-3xl font-black" x-text="currentTask?.current_user_submission?.grade + ' / ' + currentTask?.points"></span>
                            </div>
                            <div x-show="currentTask?.current_user_submission?.feedback" class="pt-4 border-t border-white/20">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-80 mb-2">Professor Feedback</p>
                                <p class="text-sm italic font-medium leading-relaxed" x-text="currentTask?.current_user_submission?.feedback"></p>
                            </div>
                        </div>

                        <div x-show="currentTask?.current_user_submission?.grade === null">
                            <button @click="showResubmitForm = !showResubmitForm" class="w-full py-4 bg-[#383838] text-white font-black rounded-2xl hover:bg-black transition text-xs uppercase tracking-widest shadow-lg shadow-gray-200">
                                <i class="ri-edit-2-line mr-2"></i> Update Submission
                            </button>

                            <form x-show="showResubmitForm" @submit.prevent="resubmitTask()" class="mt-6 p-6 bg-amber-50 rounded-[30px] border-2 border-dashed border-amber-200 animate-fade-in" enctype="multipart/form-data">
                                <p class="text-xs text-amber-800 font-bold mb-4 flex items-center gap-2"><i class="ri-error-warning-fill"></i> Previous work will be overwritten.</p>
                                <input type="file" name="submission" required class="block w-full text-xs file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-amber-600 file:text-white mb-4">
                                <button type="submit" :disabled="resubmitting" class="w-full py-3 bg-green-600 text-white font-black rounded-xl hover:bg-green-700 transition text-[10px] uppercase tracking-widest">
                                    <span x-show="!resubmitting">Confirm Update</span>
                                    <span x-show="resubmitting">Syncing Server...</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div x-show="!currentTask?.current_user_submission" class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-[30px] p-12 text-center">
                    <i class="ri-folder-info-line text-5xl text-gray-300 mb-4 block"></i>
                    <p class="font-black text-gray-400 uppercase tracking-widest text-[10px]">No Submission Detected</p>
                    <p class="text-xs text-gray-400 mt-2 font-medium">Please enter the classroom to complete this laboratory task.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <script>
        const classId = {{ $class->id }};
        const profPeerId = 'PROF_{{ $class->faculty_id }}';
        const csrfToken = '{{ csrf_token() }}';
        let studentPeer = null;

        document.addEventListener('DOMContentLoaded', () => {
            studentPeer = new Peer('STUDENT_{{ auth()->id() }}');
            
            studentPeer.on('call', (call) => {
                call.answer();
                call.on('stream', (stream) => {
                    document.getElementById('lockdown-ui').classList.remove('hidden');
                    document.getElementById('normal-view').classList.add('hidden');
                    document.getElementById('professor-screen').srcObject = stream;
                    document.getElementById('professor-screen').play();
                });
                call.on('close', () => {
                    document.getElementById('lockdown-ui').classList.add('hidden');
                    document.getElementById('normal-view').classList.remove('hidden');
                });
            });

            setInterval(() => {
                fetch(`/student/heartbeat/${classId}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }});
            }, 30000);
        });

        async function enterClassroom() {
            try {
                const stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
                window.dispatchEvent(new CustomEvent('screen-shared'));
                studentPeer.call(profPeerId, stream);

                fetch("{{ route('student.mark-present', $class->id) }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }});
                stream.getVideoTracks()[0].onended = () => { window.dispatchEvent(new CustomEvent('screen-stopped')); location.reload(); };
            } catch (err) { console.error("Capture Failed", err); }
        }

        // Lockdown Workspace logic
        function lockdownWorkspace() {
            return {
                tab: 'activities',
                tasks: [],
                uploadingTaskId: null,
                init() { this.fetchTasks(); setInterval(() => this.fetchTasks(), 3000); },
                fetchTasks() {
                    fetch(`/student/classroom/${classId}/live-tasks`)
                        .then(res => res.json())
                        .then(data => this.tasks = data.filter(t => !t.current_user_submission));
                },
                async submitTask(taskId, event) {
                    this.uploadingTaskId = taskId;
                    const formData = new FormData(event.target);
                    try {
                        const res = await fetch(`/student/tasks/${taskId}/submit`, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken }});
                        if (res.ok) { alert('✅ Task submitted!'); this.fetchTasks(); }
                        else { alert('❌ Submission failed.'); }
                    } catch (error) { console.error(error); }
                    finally { this.uploadingTaskId = null; }
                }
            }
        }

        // Normal View Activity Log logic
        function classroomTasks() {
            return {
                tasks: [],
                init() { this.fetchTasks(); setInterval(() => this.fetchTasks(), 5000); },
                fetchTasks() { fetch(`/student/classroom/${classId}/live-tasks`).then(res => res.json()).then(data => this.tasks = data); },
                openTaskModal(task) { Alpine.$data(document.querySelector('[x-data*="taskModal"]')).openModal(task); }
            }
        }

        // Modal Logic
        function taskModal() {
            return {
                currentTask: null,
                showResubmitForm: false,
                resubmitting: false,
                openModal(task) { this.currentTask = task; this.showResubmitForm = false; document.getElementById('task-modal').classList.remove('hidden'); },
                closeModal() { document.getElementById('task-modal').classList.add('hidden'); this.currentTask = null; },
                formatDate(dateString) { return dateString ? new Date(dateString).toLocaleString() : 'N/A'; },
                formatDeadline(deadline) { return deadline ? new Date(deadline).toLocaleDateString() : 'No deadline'; },
                async resubmitTask() {
                    if (!confirm('Replace previous work?')) return;
                    this.resubmitting = true;
                    try {
                        const res = await fetch(`/student/tasks/${this.currentTask.id}/submit`, { method: 'POST', body: new FormData(event.target), headers: { 'X-CSRF-TOKEN': csrfToken }});
                        if (res.ok) { alert('✅ Resubmitted!'); location.reload(); }
                    } catch (error) { alert('❌ Error'); }
                    finally { this.resubmitting = false; }
                }
            }
        }

        // Browser functionality
        function browserManager() {
            return {
                browserUrl: 'https://www.google.com/search?igu=1',
                urlInput: '',
                navigateTo() {
                    let url = this.urlInput.trim();
                    this.browserUrl = url.startsWith('http') ? url : 'https://www.google.com/search?q=' + encodeURIComponent(url) + '&igu=1';
                },
                refresh() { 
                    const frame = document.getElementById('lockdown-frame');
                    if(frame) frame.src += '';
                }
            }
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