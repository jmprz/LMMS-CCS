<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="student-grid">
    @forelse($class->students as $student)
        <div class="border rounded-lg p-4 bg-gray-50 transition-all duration-300" id="student-card-{{ $student->id }}" data-student-id="{{ $student->id }}">
            
            <div class="flex items-center justify-between mb-3">
                <span class="font-bold text-sm text-gray-800">{{ $student->name }}</span>
                <div class="w-3 h-3 rounded-full {{ $student->pivot->is_present ? 'bg-green-500 animate-pulse' : 'bg-gray-300' }}" id="status-dot-{{ $student->id }}"></div>
            </div>

            <div class="bg-gray-200 h-56 rounded flex items-center justify-center mb-4 relative overflow-hidden" id="video-container-{{ $student->id }}">
                <video id="video-{{ $student->id }}" class="w-full h-full object-cover hidden" muted playsinline></video>
                <span class="text-xs text-gray-500" id="video-overlay-{{ $student->id }}">
                    {{ $student->pivot->is_present ? 'Connecting...' : 'Offline' }}
                </span>
            </div>

            <div class="flex space-x-2" id="btn-container-{{ $student->id }}">
                @if($student->pivot->is_present)
                    <button onclick="openFullscreenViewer('{{ $student->id }}', '{{ $student->name }}')" 
                            class="flex-1 text-xs bg-[#383838] text-white py-2 rounded font-bold hover:bg-black transition shadow-sm">
                        View Screen
                    </button>
                @else
                    <button disabled class="flex-1 text-xs bg-gray-300 text-gray-500 py-2 rounded font-semibold cursor-not-allowed">
                        Waiting...
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-full py-20 text-center">
            <p class="text-gray-400 italic">No students are currently enrolled in this subject session.</p>
        </div>
    @endforelse
</div>

<div x-data="{ open: false, studentName: '' }" 
     @open-modal.window="open = true; studentName = $event.detail.name"
     x-show="open" 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
     x-cloak>
    
    <div class="relative w-full max-w-4xl bg-white rounded-2xl overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
            <h3 class="font-bold text-gray-900 flex items-center">
                <span class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                Monitoring: <span x-text="studentName" class="ml-1"></span>
            </h3>
            <button @click="open = false; document.getElementById('modal-video').srcObject = null" 
                    class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="aspect-video bg-black flex items-center justify-center">
            <video id="modal-video" autoplay playsinline class="w-full h-full object-contain"></video>
        </div>
    </div>
</div>

<script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script>
    let adminPeer;
    const connectedStudents = new Set();

    // 1. Initialize Peer Connection
    function initAdminPeer() {
        adminPeer = new Peer('ADMIN_{{ auth()->id() }}');

        adminPeer.on('open', (id) => {
            console.log('✅ Admin Peer ready: ' + id);
            // Restore sessions from localStorage after Peer ID is ready
            const saved = JSON.parse(localStorage.getItem('watching') || '[]');
            saved.forEach(sId => startSpectating(sId));
        });

        adminPeer.on('call', (call) => {
            const studentId = call.peer.replace('STUDENT_', '');
            call.answer();
            call.on('stream', (remoteStream) => updateVideoUI(studentId, remoteStream));
            call.on('close', () => resetStudentUI(studentId));
        });
    }

    // 2. Start Spectating
    function startSpectating(studentId) {
        if (connectedStudents.has(String(studentId))) return; // Avoid duplicate calls

        const targetId = 'STUDENT_' + studentId;
        const call = adminPeer.call(targetId, new MediaStream());
        
        const btn = document.querySelector(`#btn-container-${studentId} button`);
        if (btn) btn.innerText = "Connecting...";

        call.on('stream', (remoteStream) => updateVideoUI(studentId, remoteStream));
        call.on('error', () => resetStudentUI(studentId));
        call.on('close', () => resetStudentUI(studentId));

        connectedStudents.add(String(studentId));
        saveToLocalStorage(studentId);
    }

    // 3. UI Helpers
    function updateVideoUI(studentId, remoteStream) {
        const video = document.getElementById('video-' + studentId);
        const overlay = document.getElementById('video-overlay-' + studentId);
        const btn = document.querySelector(`#btn-container-${studentId} button`);
        
        if (video) {
            video.srcObject = remoteStream;
            video.classList.remove('hidden');
            if (overlay) overlay.classList.add('hidden');
            video.play();
            if (btn) btn.innerText = "● LIVE";
        }
    }

    function resetStudentUI(studentId) {
        const video = document.getElementById('video-' + studentId);
        const overlay = document.getElementById('video-overlay-' + studentId);
        const btn = document.querySelector(`#btn-container-${studentId} button`);

        if (video) { video.srcObject = null; video.classList.add('hidden'); }
        if (overlay) overlay.classList.remove('hidden');
        if (btn) { btn.innerText = "Connect Feed"; btn.classList.replace('bg-red-600', 'bg-indigo-600'); }

        connectedStudents.delete(String(studentId));
        removeFromLocalStorage(studentId);
    }

    // 4. Persistence Management
    function saveToLocalStorage(id) {
        let list = JSON.parse(localStorage.getItem('watching') || '[]');
        if (!list.includes(String(id))) {
            list.push(String(id));
            localStorage.setItem('watching', JSON.stringify(list));
        }
    }

    function removeFromLocalStorage(id) {
        let list = JSON.parse(localStorage.getItem('watching') || '[]');
        list = list.filter(item => String(item) !== String(id));
        localStorage.setItem('watching', JSON.stringify(list));
    }

    // 5. Presence Syncing
    function checkPresence() {
        fetch('{{ route("admin.status-check") }}?session_id={{ $class->id }}')
            .then(res => res.json())
            .then(data => {
                const presentIds = data.present_ids.map(String);
                
                // Remove students who are no longer in the DB
                connectedStudents.forEach(sId => {
                    if (!presentIds.includes(sId)) resetStudentUI(sId);
                });

                // Auto-connect to new students
                presentIds.forEach(sId => {
                    if (!connectedStudents.has(sId)) startSpectating(sId);
                });
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initAdminPeer();
        setInterval(checkPresence, 5000);
    });

    function openFullscreenViewer(studentId, studentName) {
    const gridVideo = document.getElementById(`video-${studentId}`);
    const modalVideo = document.getElementById('modal-video');

    if (gridVideo && gridVideo.srcObject) {
        // Transfer the stream to the modal video
        modalVideo.srcObject = gridVideo.srcObject;
        
        // Dispatch event to Alpine.js to open the modal
        window.dispatchEvent(new CustomEvent('open-modal', { 
            detail: { name: studentName } 
        }));
    } else {
        alert("Wait for the connection to be established first.");
    }
}
</script>