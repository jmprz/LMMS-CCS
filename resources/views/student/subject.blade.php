<x-app-layout>
 <div class="flex h-screen bg-gray-50" x-data="{ activeTask: null }">
    <aside class="w-64 bg-white border-r border-gray-200 p-6">
        <h2 class="font-bold text-gray-800 mb-6">My Classes</h2>
        <nav class="space-y-2">
            <a href="{{ route('student.dashboard') }}" class="block p-2 text-gray-600 hover:bg-blue-50 rounded">Home</a>
            @foreach(auth()->user()->joinedClasses as $enrolled)
                <a href="{{ route('student.subject', $enrolled->id) }}" 
                   class="block p-2 {{ $class->id == $enrolled->id ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50' }} rounded">
                    {{ $enrolled->subject_name }}
                </a>
            @endforeach
        </nav>
    </aside>

  <main class="flex-1 p-8 overflow-y-auto" x-data="{ tab: 'activities', activeTask: null }">
    <div class="max-w-5xl mx-auto space-y-6">
        
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6">
            <h1 class="text-3xl font-black text-gray-900 mb-3">
                {{ $class->subject_name }} | {{ $class->program }} - {{ $class->year_level }}{{ $class->section }}
            </h1>
            
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                    <i class="ri-calendar-line mr-2"></i> {{ $class->schedule_day }}
                </span>
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                    <i class="ri-time-line mr-2"></i> {{ $class->schedule_time }}
                </span>
            </div>
        </div>

        <div id="monitoring-area">
            </div>

        <div class="flex border-b border-gray-200">
            <button @click="tab = 'activities'" :class="tab === 'activities' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition">Activities</button>
            <button @click="tab = 'quizzes'" :class="tab === 'quizzes' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition">Quizzes</button>
            <button @click="tab = 'materials'" :class="tab === 'materials' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition">Materials</button>
            <button @click="tab = 'classmates'" :class="tab === 'classmates' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-6 py-3 border-b-2 font-bold text-sm transition">Classmates</button>
        </div>

        <div class="mt-6">
            <div x-show="tab === 'activities'" x-transition>
    <div class="grid gap-4">
        @forelse($tasks as $task)
            <button @click="activeTask = {{ json_encode($task) }}" 
                    class="group text-left p-5 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-blue-400 hover:shadow-md transition-all">
                <div class="flex justify-between items-start">
                    <div class="space-y-2">
                        <div>
                            <h4 class="font-bold text-gray-900 group-hover:text-blue-600 transition">{{ $task->title }}</h4>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $task->description }}</p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            @if($task->currentUserSubmission)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 uppercase tracking-wide">
                                    <i class="ri-checkbox-circle-fill mr-1"></i> Submitted
                                </span>
                                <span class="text-[10px] text-gray-400 italic">
                                    File: {{ Str::limit($task->currentUserSubmission->original_filename, 20) }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 uppercase tracking-wide">
                                    <i class="ri-time-line mr-1"></i> Missing / Pending
                                </span>
                            @endif
                        </div>
                    </div>
                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ $task->points }} pts</span>
                </div>
            </button>
        @empty
            <div class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                <p class="text-gray-400">No activities assigned yet.</p>
            </div>
        @endforelse
    </div>
</div>

<div 
    x-show="activeTask !== null" 
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
    x-cloak
>
    <div 
        @click.away="activeTask = null" 
        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden"
    >
        <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-start bg-gray-50/50">
            <div>
                <h3 class="text-xl font-black text-gray-900" x-text="activeTask?.title"></h3>
                <div class="flex gap-3 mt-1">
                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                        Points: <span x-text="activeTask?.points"></span>
                    </span>
                </div>
            </div>
            <button @click="activeTask = null" class="text-gray-400 hover:text-gray-600 transition">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

      <div class="px-8 py-6">
    <div class="mb-4 flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100">
        <div class="flex items-center gap-2">
            <i class="ri-calendar-todo-line text-gray-400"></i>
            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Deadline</span>
        </div>
        <span :class="activeTask.deadline && new Date() > new Date(activeTask.deadline) ? 'text-red-600' : 'text-blue-600'" 
              class="text-xs font-black"
              x-text="activeTask.deadline ? new Date(activeTask.deadline).toLocaleString() : 'No Deadline Set'">
        </span>
    </div>

    <div class="mb-6 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
        <h4 class="text-xs font-bold text-blue-800 uppercase tracking-widest mb-1">Instructions</h4>
        <p class="text-gray-700 text-sm leading-relaxed" x-text="activeTask?.description"></p>
    </div>

    <template x-if="activeTask?.current_user_submission">
        <div class="mb-6 space-y-4">
            <div class="p-4 rounded-xl border-2 border-green-100 bg-green-50/30">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-xs font-bold text-green-800 uppercase tracking-widest">Your Submission</h4>
                    <span class="text-lg font-black text-green-700" 
                          x-text="(activeTask.current_user_submission.grade || '0') + ' / ' + activeTask.points">
                    </span>
                </div>
                
                <div class="bg-white p-3 rounded-lg border border-green-100 mb-3">
                    <p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Professor's Feedback</p>
                    <p class="text-sm text-gray-700 italic" 
                       x-text="activeTask.current_user_submission.feedback || 'No feedback provided yet.'"></p>
                </div>

                <div class="flex justify-between items-center">
                    <a :href="'/submissions/' + activeTask.current_user_submission.file_path.replace('submissions/', '')" 
                       target="_blank" 
                       class="flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-800 transition">
                        <i class="ri-file-list-3-line"></i>
                        <span x-text="activeTask.current_user_submission.original_filename"></span>
                    </a>

                    <template x-if="!activeTask.deadline || new Date() < new Date(activeTask.deadline)">
                        <form :action="`/student/tasks/${activeTask.id}/delete`" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete your submission? This cannot be undone.')">
                            @csrf
                            <button type="submit" class="text-red-500 hover:text-red-700 p-1 rounded-md hover:bg-red-50 transition">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </form>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <template x-if="!activeTask.deadline || new Date() < new Date(activeTask.deadline)">
        <form 
            :action="`/student/tasks/${activeTask?.id}/submit`" 
            method="POST" 
            enctype="multipart/form-data"
            x-data="{ startTime: Date.now(), uploading: false }"
            x-init="$watch('activeTask', value => { if(value) startTime = Date.now() })"
            @submit="uploading = true"
        >
            @csrf
             <input type="hidden" name="opened_at" :value="startTime">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2" 
                           x-text="activeTask?.current_user_submission ? 'Update Submission' : 'File Submission'">
                    </label>
                    <input 
                        type="file" 
                        name="submission" 
                        required
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 border border-gray-200 rounded-xl p-2 cursor-pointer transition"
                    >
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <button type="button" @click="activeTask = null" class="text-sm font-bold text-gray-500 px-4">Cancel</button>
                    <button 
                        type="submit" 
                        class="px-8 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition flex items-center gap-2"
                        :disabled="uploading"
                    >
                        <template x-if="!uploading">
                            <span x-text="activeTask?.current_user_submission ? 'Resubmit Work' : 'Submit Work'"></span>
                        </template>
                        <template x-if="uploading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Uploading...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </form>
    </template>

    <template x-if="activeTask.deadline && new Date() > new Date(activeTask.deadline)">
        <div class="bg-red-50 border border-red-100 p-6 rounded-xl text-center">
            <i class="ri-error-warning-fill text-red-500 text-3xl mb-2 block"></i>
            <p class="text-red-700 font-bold">Submission Closed</p>
            <p class="text-red-600 text-xs mt-1">The deadline for this activity has passed. You can no longer submit or delete files.</p>
            <button @click="activeTask = null" class="mt-4 text-xs font-bold text-red-800 underline">Close Window</button>
        </div>
    </template>
</div>
    </div>
</div>

            <div x-show="tab === 'quizzes'" x-transition>
                <div class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed">
                    @foreach($quizzes as $quiz)
    <div class="p-4 border rounded-lg mb-2 flex justify-between items-center">
        <div>
            <h4 class="font-bold">{{ $quiz->title }}</h4>
            <p class="text-sm text-gray-500">Limit: {{ $quiz->time_limit }} mins</p>
        </div>
        <a href="{{ route('student.quizzes.attempt', $quiz->id) }}" class="bg-indigo-600 text-white px-4 py-2 rounded">
            Start Quiz
        </a>
    </div>
@endforeach
                </div>
            </div>

            <div x-show="tab === 'materials'" x-transition>
                <div class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed">
                    <p class="text-gray-400">No reference materials uploaded.</p>
                </div>
            </div>

            <div x-show="tab === 'classmates'" x-transition>
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 font-bold text-gray-700">Student Name</th>
                                <th class="px-6 py-3 font-bold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($class->students as $student)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $student->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="w-2 h-2 rounded-full inline-block bg-green-500 mr-2"></span> Enrolled
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
   <script>
    let heartbeatInterval;
    let studentPeer;
    let localStream;
    const classId = {{ $class->id }};
    const csrfToken = '{{ csrf_token() }}';
    const allowedDomains = @json($class->whitelisted_urls ? explode(',', $class->whitelisted_urls) : ['google.com']);

    document.addEventListener('DOMContentLoaded', () => {
        studentPeer = new Peer('STUDENT_{{ auth()->id() }}');
        
        // Start heartbeat immediately
        startHeartbeat();

        studentPeer.on('open', (id) => {
            console.log('✅ Peer ready: ' + id);
            
            // Auto-resume sharing if they were already sharing before refresh
            const wasSharing = localStorage.getItem('is_sharing');
            if (wasSharing === 'true') {
                startFullMonitoring();
            }
        });

        studentPeer.on('call', (call) => {
            if (localStream) {
                call.answer(localStream);
            } else {
                console.warn("⚠️ Admin requested feed, but local stream is not active.");
            }
        });
    });

    // 1. Join Classroom
    function joinClassroom(classId) {
        fetch(`/student/mark-present/${classId}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
        }).then(() => location.reload());
    }

    // 2. Start Screen Sharing (WebRTC)
    async function startFullMonitoring() {
        try {
            localStream = await navigator.mediaDevices.getDisplayMedia({ 
                video: { width: { max: 1280 }, frameRate: { max: 15 } } 
            });

            localStorage.setItem('is_sharing', 'true');

            localStream.getVideoTracks()[0].onended = () => {
                localStorage.setItem('is_sharing', 'false');
                fetch('{{ route("student.stop-presenting", $class->id) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                }).then(() => location.reload());
            };

            const adminPeerId = 'ADMIN_{{ $class->faculty_id }}';
            studentPeer.call(adminPeerId, localStream);
            
            document.getElementById('monitoring-area').innerHTML = 
                `<div class="text-green-600 font-bold animate-pulse">Status: ACTIVE MONITORING</div>`;
            
            startHeartbeat();
        } catch (err) {
            console.error("❌ Capture failed:", err);
            localStorage.setItem('is_sharing', 'false');
        }
    }

    // 3. Heartbeat Loop
    function startHeartbeat() {
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        heartbeatInterval = setInterval(() => {
            fetch(`/student/heartbeat/${classId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            });
        }, 30000);
    }

    // 4. Session Status Polling
    setInterval(() => {
        fetch(`/student/check-session-status/${classId}`)
            .then(res => res.json())
            .then(data => {
                if (data.is_active && document.getElementById('join-btn') === null) {
                    location.reload(); 
                }
            });
    }, 5000);

    // 5. Browser Navigation
    function navigateBrowser() {
        const input = document.getElementById('browserUrl').value;
        const targetUrl = input.includes('.') ? (input.startsWith('http') ? input : 'https://' + input) : 'https://www.google.com/search?q=' + encodeURIComponent(input) + '&igu=1';
        if (allowedDomains.some(d => targetUrl.includes(d.trim().toLowerCase()))) {
            document.getElementById('mainFrame').src = targetUrl;
        } else {
            alert("🚫 Access Denied.");
        }
    }
</script>
</x-app-layout>