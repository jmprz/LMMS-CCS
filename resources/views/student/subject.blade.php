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

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-4xl mx-auto space-y-6">
            <div id="monitoring-area" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                </div>

            <h2 class="font-bold text-lg text-gray-800">Assigned Tasks</h2>
            <div class="grid gap-4">
                @forelse($tasks as $task)
                    <button @click="activeTask = {{ json_encode($task) }}" 
                            class="text-left p-4 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-blue-400 transition">
                        <h4 class="font-bold text-gray-800">{{ $task->title }}</h4>
                        <p class="text-sm text-gray-600">{{ $task->description }}</p>
                    </button>
                @empty
                    <p class="text-gray-400">No tasks currently assigned.</p>
                @endforelse
            </div>
        </div>

        <div x-show="activeTask !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
            <div class="bg-white p-8 rounded-xl w-full max-w-lg" @click.away="activeTask = null">
                <h3 class="font-bold text-xl mb-2" x-text="activeTask?.title"></h3>
                <p class="text-gray-600 mb-6" x-text="activeTask?.description"></p>
                
                <form action="#" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="block text-sm font-medium mb-2">Upload Submission</label>
                    <input type="file" name="submission" class="w-full p-2 border rounded-lg mb-4">
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="activeTask = null" class="px-4 py-2 bg-gray-200 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Submit Task</button>
                    </div>
                </form>
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