<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="student-grid">
    @foreach($activeStudents as $student)
        <div class="border rounded-lg p-4 bg-gray-50 transition-all duration-300" 
             id="student-card-{{ $student->id }}" 
             data-student-id="{{ $student->id }}">
            
            <div class="flex items-center justify-between mb-3">
                <span class="font-bold text-sm">{{ $student->name }}</span>
                <div class="w-3 h-3 rounded-full bg-gray-300" id="status-dot-{{ $student->id }}"></div>
            </div>

           <div class="bg-gray-200 h-56 rounded flex items-center justify-center mb-4 relative overflow-hidden" 
     id="video-container-{{ $student->id }}">
    
    <video id="video-{{ $student->id }}" class="w-full h-full object-cover hidden" muted playsinline></video>
    
    <span class="text-xs text-gray-500" id="video-overlay-{{ $student->id }}">Offline</span>
</div>

            <div class="flex space-x-2" id="btn-container-{{ $student->id }}">
                <button disabled class="flex-1 text-xs bg-gray-300 text-gray-500 py-2 rounded font-semibold cursor-not-allowed">
                    Waiting...
                </button>
            </div>
        </div>
    @endforeach
</div>

<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script>
    let adminPeer;

    // 1. Initialize Peer Connection
    function initAdminPeer() {
        adminPeer = new Peer('ADMIN_{{ auth()->id() }}');

        adminPeer.on('open', (id) => console.log('✅ Admin Peer ready: ' + id));

        adminPeer.on('call', (call) => {
            const studentId = call.peer.replace('STUDENT_', '');
            call.answer(); // Automatically answer student calls

            call.on('stream', (remoteStream) => {
                const video = document.getElementById('video-' + studentId);
                const overlay = document.getElementById('video-overlay-' + studentId);
                
                if (video && overlay) {
                    video.srcObject = remoteStream;
                    video.classList.remove('hidden'); // Show video element
                    overlay.classList.add('hidden');  // Hide "Offline" text
                    video.play();
                }
            });

            call.on('close', () => resetStudentUI(studentId));
        });
    }

    // 2. Start Spectating Function
    function startSpectating(studentId) {
        const targetId = 'STUDENT_' + studentId;
        const dummyStream = new MediaStream(); // Required for WebRTC initiation
        const call = adminPeer.call(targetId, dummyStream);

        const btn = document.querySelector(`#btn-container-${studentId} button`);
        if (btn) btn.innerText = "Connecting...";

        call.on('stream', (remoteStream) => {
            const video = document.getElementById('video-' + studentId);
            const overlay = document.getElementById('video-overlay-' + studentId);
            
            if (video) {
                video.srcObject = remoteStream;
                video.classList.remove('hidden');
                overlay.classList.add('hidden');
                video.play();
                if (btn) btn.innerText = "● LIVE";
            }
        });
    }

    // 3. UI Reset Logic
    function resetStudentUI(studentId) {
        const video = document.getElementById('video-' + studentId);
        const overlay = document.getElementById('video-overlay-' + studentId);
        const btn = document.querySelector(`#btn-container-${studentId} button`);

        if (video) {
            video.srcObject = null;
            video.classList.add('hidden');
        }
        if (overlay) overlay.classList.remove('hidden');
        if (btn) {
            btn.innerText = "Connect Feed";
            btn.classList.replace('bg-red-600', 'bg-indigo-600');
        }
    }

    // 4. Presence Syncing (Updates UI based on DB)
    function checkPresence() {
        fetch('{{ route("admin.status-check") }}')
            .then(res => res.json())
            .then(data => {
                const presentIds = data.present_ids.map(String);
                
                document.querySelectorAll('[id^="student-card-"]').forEach(card => {
                    const sId = card.getAttribute('data-student-id');
                    const dot = document.getElementById('status-dot-' + sId);
                    const btnContainer = document.getElementById('btn-container-' + sId);

                    if (presentIds.includes(sId)) {
                        dot.classList.replace('bg-gray-300', 'bg-green-500');
                        btnContainer.innerHTML = `<button onclick="startSpectating('${sId}')" class="flex-1 text-xs bg-indigo-600 text-white py-2 rounded">Connect Feed</button>`;
                    } else {
                        dot.classList.replace('bg-green-500', 'bg-gray-300');
                        btnContainer.innerHTML = `<button disabled class="flex-1 text-xs bg-gray-300 text-gray-500 py-2 rounded cursor-not-allowed">Waiting...</button>`;
                    }
                });
            });
    }

    // Initialize
    initAdminPeer();
    setInterval(checkPresence, 5000);
</script>