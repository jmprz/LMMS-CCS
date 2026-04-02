<x-app-layout>
 <div class="flex h-screen bg-gray-50" x-data="{ activeTask: null }">
    <aside class="w-64 bg-white border-r border-gray-200 flex-shrink-0 flex flex-col">
    <div class="px-6 pt-8 pb-4">
        <h2 class="font-black text-[#383838] uppercase tracking-widest text-[10px] opacity-50">
            Main Navigation
        </h2>
    </div>

    <nav class="px-4 space-y-1 flex-1">
        <a href="{{ route('student.dashboard') }}" 
           class="flex items-center py-3 px-4 rounded-xl transition duration-200 {{ request()->routeIs('student.dashboard') ? 'bg-[#383838] text-white font-bold shadow-lg shadow-gray-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
            <i class="ri-home-4-line mr-3 text-lg"></i>
            <span class="text-sm">Home</span>
        </a>

        <div class="my-6 px-2">
            <hr class="border-gray-100">
        </div>

        <h2 class="px-2 font-black text-[#383838] uppercase tracking-widest text-[10px] opacity-50 mb-4">
            My Enrolled Classes
        </h2>

        <div class="space-y-1 overflow-y-auto max-h-[calc(100vh-300px)] custom-scrollbar">
            @foreach(auth()->user()->joinedClasses as $enrolled)
                <a href="{{ route('student.subject', $enrolled->id) }}" 
                   class="flex items-center py-3 px-4 rounded-xl transition duration-200 {{ $class->id == $enrolled->id ? 'bg-[#383838] text-white font-bold shadow-md' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    
                    <i class="{{ $class->id == $enrolled->id ? 'ri-book-open-fill' : 'ri-book-read-line' }} mr-3 text-lg"></i>
                    
                    <div class="flex flex-col truncate">
                        <span class="text-sm truncate leading-tight">{{ $enrolled->subject_name }}</span>
                        <span class="text-[10px] {{ $class->id == $enrolled->id ? 'text-gray-300' : 'text-gray-400' }} font-medium">
                           {{ $enrolled->program }} - {{ $enrolled->year_level }}{{ $enrolled->section }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </nav>

    <div class="p-4 border-t border-gray-100">
        <a href="#" class="flex items-center py-3 px-4 rounded-xl text-red-500 hover:bg-red-50 transition duration-200 text-sm font-bold">
            <i class="ri-logout-box-r-line mr-3 text-lg"></i>
            Logout
        </a>
    </div>
</aside>

  <main class="flex-1 p-8 overflow-y-auto" x-data="{ tab: 'activities', activeTask: null }">
   <div class="max-w-5xl mx-auto space-y-6">
            
            <div class="bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6">
                <h1 class="text-3xl font-black text-gray-900 mb-3 tracking-tight">
                    {{ $class->subject_name }} <span class="text-gray-400 font-light mx-2">|</span> <span class="text-[#383838]">{{ $class->program }}-{{ $class->year_level }}{{ $class->section }}</span>
                </h1>
                
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                        <i class="ri-calendar-line mr-2"></i> {{ $class->schedule_day }}
                    </span>
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                        <i class="ri-time-line mr-2"></i> {{ $class->schedule_time }}
                    </span>
                </div>
            </div>

    <div id="monitoring-area" class="mt-6" x-data="{ sessionActive: {{ $class->is_active ? 'true' : 'false' }} }">
                <template x-if="!sessionActive">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 flex items-center gap-4">
                        <div class="bg-amber-100 p-3 rounded-full text-amber-600 animate-pulse">
                            <i class="ri-error-warning-line text-2xl"></i>
                        </div>
                        <div>
                            <p class="font-bold text-amber-900">Class Not Started</p>
                            <p class="text-sm text-amber-700">The instructor has not started the session. Please wait for the signal.</p>
                        </div>
                    </div>
                </template>

   <template x-if="sessionActive">
                    <div class="flex justify-center bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">
                        @if(!$isPresent)
                            <button id="join-btn" onclick="joinClassroom({{ $class->id }})" 
                                class="bg-[#383838] text-white px-10 py-4 rounded-xl shadow-lg hover:bg-black transition-all font-bold flex items-center gap-3">
                                <i class="ri-door-open-line"></i> Join Classroom
                            </button>
                        @else
                            <button id="start-btn" onclick="startFullMonitoring()" 
                                class="bg-[#383838] text-white px-10 py-4 rounded-xl shadow-lg hover:bg-black transition-all font-bold flex items-center gap-3 animate-bounce-short">
                                <i class="ri-screenshot-2-line"></i> Start Screen Sharing
                            </button>
                        @endif
                    </div>
                </template>
            </div>

        <div class="flex border-b border-gray-200">
                <template x-for="t in ['activities', 'quizzes', 'materials', 'classmates']">
                    <button @click="tab = t" 
                        :class="tab === t ? 'border-[#383838] text-[#383838]' : 'border-transparent text-gray-400 hover:text-gray-600'" 
                        class="px-6 py-4 border-b-2 font-bold text-xs uppercase tracking-widest transition" 
                        x-text="t">
                    </button>
                </template>
            </div>
       <div class="mt-6">
                <div x-show="tab === 'activities'" x-transition>
                    <div class="grid gap-4">
                        @forelse($tasks as $task)
                            <button @click="activeTask = {{ json_encode($task) }}" 
                                    class="group text-left p-6 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-[#383838] hover:ring-1 hover:ring-[#383838] transition-all">
                                <div class="flex justify-between items-start">
                                    <div class="space-y-3">
                                        <h4 class="font-bold text-gray-900 text-lg group-hover:text-[#383838] transition">{{ $task->title }}</h4>
                                        <div class="flex items-center gap-3">
                                            @if($task->currentUserSubmission)
                                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-bold bg-green-50 text-green-700 uppercase border border-green-100">
                                                    SUBMITTED
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 uppercase border border-amber-100">
                                                    PENDING
                                                </span>
                                            @endif
                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">
                                                ID: #TASK-{{ $task->id }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-black text-[#383838] bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                                        {{ $task->points }} PTS
                                    </span>
                                </div>
                            </button>
                        @empty
                            @endforelse
                    </div>
                </div>
               <div x-show="tab === 'quizzes'" x-transition class="space-y-4">
    @forelse($quizzes as $quiz)
        @php
            // Logic to check for attempts and deadlines
            $attempt = $quiz->attempts()->where('user_id', auth()->id())->first();
            $isPastDeadline = $quiz->deadline && now()->gt($quiz->deadline);
            $hasAttempted = $attempt !== null;
        @endphp

        <div class="group bg-white border border-gray-200 rounded-2xl p-5 hover:border-[#383838] transition-all shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <div class="flex items-start gap-4">
                    <div class="bg-gray-100 p-3 rounded-xl text-[#383838] group-hover:bg-[#383838] group-hover:text-white transition-colors">
                        <i class="ri-timer-flash-line text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-lg group-hover:text-[#383838] transition">
                            {{ $quiz->title }}
                        </h4>
                        <div class="flex flex-wrap items-center gap-3 mt-1">
                            <span class="flex items-center text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                <i class="ri-time-line mr-1 text-xs"></i> {{ $quiz->time_limit }} Mins
                            </span>
                            @if($quiz->deadline)
                                <span class="text-gray-300">•</span>
                                <span class="flex items-center text-[10px] font-bold {{ $isPastDeadline ? 'text-red-500' : 'text-gray-500' }} uppercase tracking-widest">
                                    <i class="ri-calendar-event-line mr-1 text-xs"></i> 
                                    Due: {{ \Carbon\Carbon::parse($quiz->deadline)->format('M d, h:i A') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    @if($hasAttempted)
                        <div class="text-right px-4">
                            <span class="block text-[10px] font-black text-[#383838] uppercase tracking-tighter opacity-60">Result</span>
                            <span class="text-xl font-black text-green-600">{{ $attempt->score }} / {{ $quiz->total_points }}</span>
                        </div>
                        <button disabled class="bg-gray-100 text-gray-400 px-6 py-2.5 rounded-xl text-xs font-bold cursor-not-allowed border border-gray-200">
                            Completed
                        </button>
                    @elseif($isPastDeadline)
                        <div class="text-right px-4">
                            <span class="block text-[10px] font-black text-red-600 uppercase tracking-tighter">Closed</span>
                            <span class="text-xs font-bold text-gray-400">Past Deadline</span>
                        </div>
                        <button disabled class="bg-gray-100 text-gray-400 px-6 py-2.5 rounded-xl text-xs font-bold cursor-not-allowed">
                            Unavailable
                        </button>
                    @else
                        <a href="{{ route('student.quizzes.attempt', $quiz->id) }}" 
                           class="bg-[#383838] text-white px-8 py-3 rounded-xl text-xs font-bold hover:bg-black transition-all shadow-lg shadow-gray-200 flex items-center gap-2">
                            Take Quiz <i class="ri-edit-box-line"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-16 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
            <i class="ri-survey-line text-gray-300 text-4xl mb-3 block"></i>
            <p class="text-gray-500 font-medium">No quizzes available for this subject.</p>
        </div>
    @endforelse
</div>

<div x-show="tab === 'materials'" x-transition>
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-8">
    @foreach($class->materials as $material) 
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-gray-100 rounded-xl">
                @if($material->type == 'pdf') 
                    <i class="ri-file-pdf-fill text-red-500 text-xl"></i>
                @elseif($material->type == 'youtube') 
                    <i class="ri-youtube-fill text-red-600 text-xl"></i>
                @else 
                    <i class="ri-file-ppt-2-fill text-orange-500 text-xl"></i>
                @endif
            </div>
            <div>
                <h4 class="font-bold text-gray-900">{{ $material->title }}</h4>
                <p class="text-xs text-gray-400 uppercase font-semibold">{{ $material->type }}</p>
            </div>
        </div>
        
       @php
    // If it's a file, we use the path stored in DB. If YouTube, use the URL.
    $url = ($material->type === 'youtube') 
            ? $material->content 
            : url($material->content); // This points to your public folder
@endphp

<a href="{{ $url }}" 
   target="_blank" 
   class="inline-flex items-center text-[10px] font-black text-[#383838] bg-gray-100 px-3 py-2 rounded-lg hover:bg-black hover:text-white transition-all uppercase tracking-widest">
    <i class="fas fa-eye me-2"></i> 
    {{ $material->type === 'youtube' ? 'Watch Video' : 'View File' }}
</a>
    </div>
    @endforeach
   </div>
</div>

<div x-show="tab === 'classmates'" x-transition>
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 font-black text-[#383838] uppercase tracking-widest text-[10px]">Student Name</th>
                    <th class="px-6 py-4 font-black text-[#383838] uppercase tracking-widest text-[10px]">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($class->students as $student)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 font-medium text-gray-700 flex items-center gap-3">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-[10px] font-bold text-gray-500">
                            {{ strtoupper(substr($student->name, 0, 2)) }}
                        </div>
                        {{ $student->name }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                            <span class="w-1 h-1 rounded-full bg-green-500 mr-1.5"></span> ENROLLED
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
            </div>
        </div>
        <div 
    x-show="activeTask !== null" 
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-cloak
>
    <div 
        @click.away="activeTask = null" 
        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-200"
    >
        <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-start bg-gray-50/50">
            <div>
                <h3 class="text-xl font-black text-gray-900 leading-tight" x-text="activeTask?.title"></h3>
                <div class="flex gap-3 mt-2">
                    <span class="text-[10px] font-bold text-[#383838] bg-gray-200 px-2 py-0.5 rounded uppercase tracking-wider">
                        Points: <span x-text="activeTask?.points"></span>
                    </span>
                </div>
            </div>
            <button @click="activeTask = null" class="text-gray-400 hover:text-[#383838] transition">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <div class="px-8 py-6 space-y-6">
            <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl border border-gray-100">
                <div class="flex items-center gap-2">
                    <i class="ri-calendar-todo-line text-gray-400"></i>
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Deadline</span>
                </div>
                <span :class="activeTask?.deadline && new Date() > new Date(activeTask.deadline) ? 'text-red-600' : 'text-[#383838]'" 
                      class="text-xs font-black"
                      x-text="activeTask?.deadline ? new Date(activeTask.deadline).toLocaleString() : 'No Deadline Set'">
                </span>
            </div>

            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Instructions</h4>
                <p class="text-gray-700 text-sm leading-relaxed" x-text="activeTask?.description"></p>
            </div>

            <template x-if="activeTask?.current_user_submission">
                <div class="p-4 rounded-xl border-2 border-gray-100 bg-gray-50/30">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Your Submission</h4>
                        <span class="text-lg font-black text-[#383838]" 
                              x-text="(activeTask.current_user_submission.grade || '0') + ' / ' + activeTask.points">
                        </span>
                    </div>
                    
                    <div class="bg-white p-3 rounded-lg border border-gray-100 mb-4 shadow-sm">
                        <p class="text-[9px] uppercase font-bold text-gray-400 mb-1">Feedback</p>
                        <p class="text-sm text-gray-600 italic" 
                           x-text="activeTask.current_user_submission.feedback || 'No feedback yet.'"></p>
                    </div>

                    <div class="flex justify-between items-center">
                        <a :href="'/submissions/' + activeTask.current_user_submission.file_path.replace('submissions/', '')" 
                           target="_blank" 
                           class="flex items-center gap-2 text-sm font-bold text-[#383838] hover:underline transition">
                            <i class="ri-file-list-3-line"></i>
                            <span x-text="activeTask.current_user_submission.original_filename" class="truncate max-w-[150px]"></span>
                        </a>

                        <template x-if="activeTask && (!activeTask.deadline || new Date() < new Date(activeTask.deadline))">
                            <form :action="`/student/tasks/${activeTask.id}/delete`" method="POST" 
                                  onsubmit="return confirm('Delete this submission?')">
                                @csrf
                                <button type="submit" class="text-red-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="activeTask && (!activeTask.deadline || new Date() < new Date(activeTask.deadline))">
                <form 
                    :action="`/student/tasks/${activeTask?.id}/submit`" 
                    method="POST" 
                    enctype="multipart/form-data"
                    x-data="{ uploading: false }"
                    @submit="uploading = true"
                >
                    @csrf
                    <div class="space-y-4">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            Upload Work
                        </label>
                        <input 
                            type="file" 
                            name="submission" 
                            required
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#383838] file:text-white hover:file:bg-black border border-gray-200 rounded-xl p-1.5 cursor-pointer transition"
                        >

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button 
                                type="submit" 
                                class="w-full py-3 bg-[#383838] text-white text-sm font-bold rounded-xl hover:bg-black shadow-lg shadow-gray-200 transition flex items-center justify-center gap-2"
                                :disabled="uploading"
                            >
                                <span x-show="!uploading" x-text="activeTask?.current_user_submission ? 'Update Submission' : 'Submit Now'"></span>
                                <span x-show="uploading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Uploading...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
<div x-data="browserTabs()" class="bg-gray-100 rounded-3xl overflow-hidden border border-gray-200 shadow-xl">
    <div class="bg-white p-3 flex items-center gap-2 border-b border-gray-200">
        <template x-for="(tab, index) in tabs" :key="index">
            <div @click="activeTab = index" 
                 :class="activeTab === index ? 'bg-gray-100 border-gray-300' : 'bg-white border-transparent'"
                 class="px-4 py-2 rounded-xl border text-sm font-bold flex items-center gap-3 cursor-pointer transition-all">
                <i class="ri-global-line"></i>
                <span x-text="tab.title" class="truncate max-w-[100px]"></span>
                <i @click.stop="removeTab(index)" class="ri-close-line hover:text-red-500" x-show="tabs.length > 1"></i>
            </div>
        </template>
        <button @click="addTab()" class="p-2 hover:bg-gray-100 rounded-lg text-blue-600">
            <i class="ri-add-circle-fill text-xl"></i>
        </button>
    </div>

    <div class="bg-white px-4 py-2 flex items-center gap-4 border-b border-gray-200">
        <div class="flex gap-2 text-gray-600">
            <button @click="refresh()" class="p-2 hover:bg-gray-100 rounded-lg">
                <i class="ri-refresh-line"></i>
            </button>
        </div>
        
        <div class="flex-1 bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 text-xs text-gray-400 flex items-center gap-2 italic">
            <i class="ri-lock-2-line"></i>
            <span>Secure Research Mode: Use the Google search bar below to find resources. Direct URL entry is disabled.</span>
        </div>
        
        <div class="text-[10px] font-mono text-gray-300">
            SESSION: {{ $class->id }}
        </div>
    </div>

    <div class="relative h-[700px] bg-white">
        <template x-for="(tab, index) in tabs" :key="index">
            <iframe :src="tab.url" 
                    x-show="activeTab === index"
                    :id="'browser-frame-' + index"
                    class="absolute inset-0 w-full h-full border-none"></iframe>
        </template>
    </div>
</div>
    </main>
</div>
<style>
    .animate-bounce-short { animation: bounce 1s infinite; }
    @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    
    /* Primary Color for File Inputs */
    input[type="file"]::file-selector-button {
        background-color: #383838 !important;
        border-radius: 8px !important;
    }
    input[type="file"]::file-selector-button:hover {
        background-color: #000 !important;
    }
</style>
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
    const wasSharing = localStorage.getItem('is_sharing');
    
    // Most browsers require a USER CLICK to start screen capture.
    // Auto-resume often fails unless the user interacts with the page first.
    if (wasSharing === 'true') {
        console.log("Session was active. Please click 'Start Screen Sharing' to resume.");
    }
});

       studentPeer.on('call', (call) => {
    if (localStream && localStream.active) {
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
        let streamConstraints;

        // Check if we are running inside Electron
        if (window.electronAPI) {
            const sourceId = await window.electronAPI.getScreenId();
            streamConstraints = {
                audio: false,
                video: {
                    mandatory: {
                        chromeMediaSource: 'desktop',
                        chromeMediaSourceId: sourceId,
                        minWidth: 1280,
                        maxWidth: 1280,
                        minHeight: 720,
                        maxHeight: 720
                    }
                }
            };
            // Use getUserMedia for Electron desktop capture
            localStream = await navigator.mediaDevices.getUserMedia(streamConstraints);
        } else {
            // Fallback for standard browser
            localStream = await navigator.mediaDevices.getDisplayMedia({ 
                video: { displaySurface: "monitor", width: { max: 1280 } },
                audio: false
            });
        }

        const videoTrack = localStream.getVideoTracks()[0];
        
        // VALIDATION (Only needed for web, Electron forces it via sourceId)
        if (!window.electronAPI) {
            const settings = videoTrack.getSettings();
            if (settings.displaySurface && settings.displaySurface !== 'monitor') {
                videoTrack.stop();
                alert("❌ You must select 'ENTIRE SCREEN'.");
                return;
            }
        }

        localStorage.setItem('is_sharing', 'true');

        // Handle Disconnect
        videoTrack.onended = () => {
            localStorage.setItem('is_sharing', 'false');
            fetch('{{ route("student.stop-presenting", $class->id) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            }).then(() => location.reload());
        };

        // 3. Connect to Professor (Fixed ID)
        const adminPeerId = 'PROF_{{ $class->faculty_id }}';
        console.log("📞 Calling Professor:", adminPeerId);
        studentPeer.call(adminPeerId, localStream);
        
        // 4. UI Updates
        const statusDiv = document.getElementById('connection-status');
        if (statusDiv) statusDiv.innerHTML = `<div class="text-green-600 font-black animate-pulse">● STATUS: ACTIVE</div>`;
        
        const area = document.getElementById('monitoring-area');
        if (area && window.Alpine) {
            Alpine.$data(area).isSharing = true; 
        }

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
    fetch("{{ route('student.check-session-status', $class->id) }}")
        .then(res => res.ok ? res.json() : null)
        .then(data => {
            if (!data) return;

            const area = document.getElementById('monitoring-area');
            if (area) {
                // Accessing data for Alpine v3
                const alpineData = Alpine.$data(area);
                
                // Only trigger if the status actually changed
                if (alpineData.sessionActive !== data.is_active) {
                    alpineData.sessionActive = data.is_active;
                    
                    // Give Alpine a split second to render the buttons 
                    // then check if we should auto-resume sharing
                    setTimeout(() => {
                        const wasSharing = localStorage.getItem('is_sharing');
                        if (data.is_active && wasSharing === 'true') {
                            startFullMonitoring();
                        }
                    }, 500);
                }
            }
        })
        .catch(err => console.log("Polling..."));
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

<script>
// 1. Immediate sync to Electron Main Process
if (window.electronAPI) {
    window.electronAPI.setCurrentSession("{{ $class->id }}");
}

function browserTabs() {
    return {
        activeTab: 0,
        tabs: [
            { title: 'Google Search', url: 'https://www.google.com/search?igu=1' }
        ],
        
        init() {
            // Log that the research environment is active
            this.logActivity('environment_start', 'Browser UI initialized in locked mode');
            
            // Watch for iframe changes (though Electron catches most of this now)
            setInterval(() => {
                const frame = document.getElementById('browser-frame-' + this.activeTab);
                if (frame && frame.contentWindow) {
                    try {
                        const currentUrl = frame.contentWindow.location.href;
                        if (currentUrl !== this.tabs[this.activeTab].url && currentUrl !== 'about:blank') {
                            this.tabs[this.activeTab].url = currentUrl;
                        }
                    } catch (e) { /* CORS expected */ }
                }
            }, 2000);

            window.addEventListener('beforeunload', () => {
        if (window.electronAPI) {
            // Tell Electron to trigger one last log calculation
            window.electronAPI.triggerFinalLog("{{ $class->id }}");
        }
    });
        },

        addTab() {
            this.tabs.push({ 
                title: 'New Tab', 
                url: 'https://www.google.com/search?igu=1' 
            });
            this.activeTab = this.tabs.length - 1;
            this.logActivity('new_tab', 'Opened new research tab');
        },

        removeTab(index) {
            this.tabs.splice(index, 1);
            if (this.activeTab >= this.tabs.length) this.activeTab = this.tabs.length - 1;
        },

        refresh() {
            const frame = document.getElementById('browser-frame-' + this.activeTab);
            if (frame) {
                frame.src = frame.src;
                this.logActivity('refresh', 'Manual refresh triggered');
            }
        },

        logActivity(type, detail) {
            fetch('http://127.0.0.1:8000/student/log-behavior', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                },
                body: JSON.stringify({ 
                    type: type, 
                    detail: detail,
                    lab_session_id: "{{ $class->id }}" 
                })
            }).catch(err => console.error('Log Error:', err));
        }
    }
}
</script>
</x-app-layout>