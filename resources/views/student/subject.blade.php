<x-app-layout>
    <style>
        .browser-shadow { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
    </style>

  <div id="monitoring-area" class="mt-6 text-center">
    @if(!$class->is_active)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 text-yellow-700">
            <p class="font-bold">⚠️ Class Not Started</p>
            <p>The instructor has not started the session yet. Please wait.</p>
        </div>
    @elseif(!$isPresent)
        <button id="join-btn" onclick="joinClassroom({{ $class->id }})" 
            class="bg-blue-600 text-white px-8 py-4 rounded-full shadow-lg hover:bg-blue-700">
            Join Classroom
        </button>
    @else
        <button id="start-btn" onclick="startFullMonitoring()" 
            class="bg-green-600 text-white px-8 py-4 rounded-full shadow-lg hover:bg-green-700">
            Start Screen Sharing
        </button>
    @endif
</div>
    </div>

    <div class="flex flex-col h-screen w-full bg-white overflow-hidden">
        <div class="flex items-center space-x-4 px-4 py-2 bg-gray-100 border-b border-gray-300">
            <input type="text" id="browserUrl" 
                   onkeydown="if(event.key === 'Enter') navigateBrowser()"
                   class="block w-full px-3 py-1.5 border rounded-md" placeholder="Search or type URL">
        </div>
        <div class="flex-1 bg-gray-200">
            <iframe id="mainFrame" src="https://www.google.com/search?igu=1" class="w-full h-full border-none"></iframe>
        </div>
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
            studentPeer.on('open', (id) => console.log('✅ Peer ready: ' + id));
           studentPeer.on('call', (call) => {
    if (localStream) {
        call.answer(localStream);
    } else {
        console.warn("Admin called, but no local stream is active.");
    }
});
        });

        // 1. Join Classroom (Database Only)
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

                localStream.getVideoTracks()[0].onended = () => {
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
            }
        }

        function startHeartbeat() {
            if (heartbeatInterval) clearInterval(heartbeatInterval);
            heartbeatInterval = setInterval(() => {
                fetch(`/student/heartbeat/${classId}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
                });
            }, 30000);
        }

        setInterval(() => {
    fetch(`/student/check-session-status/${classId}`)
        .then(res => res.json())
        .then(data => {
            // If the admin started the session, reload the page 
            // so the "Warning" banner disappears and the "Join" button appears.
            if (data.is_active && document.getElementById('join-btn') === null) {
                location.reload(); 
            }
        });
}, 5000);

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