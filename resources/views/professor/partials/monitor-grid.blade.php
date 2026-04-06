{{-- Check if class exists and if the professor has flipped the "Start Session" switch --}}
@if(isset($class) && $class->is_active)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="student-grid">
        @forelse($activeStudents as $student) 
            <div class="border rounded-lg p-4 bg-gray-50 transition-all duration-300 shadow-sm hover:shadow-md" 
                 id="student-card-{{ $student->id }}" 
                 data-student-id="{{ $student->id }}">
                
                <div class="flex items-center justify-between mb-3">
                    <span class="font-bold text-sm text-gray-800">{{ $student->name }}</span>
                    <div class="w-3 h-3 rounded-full {{ ($student->pivot && $student->pivot->is_present) ? 'bg-green-500 animate-pulse' : 'bg-gray-300' }}" 
                         id="status-dot-{{ $student->id }}"></div>
                </div>

                <div class="bg-gray-200 h-56 rounded flex items-center justify-center mb-4 relative overflow-hidden" 
                     id="video-container-{{ $student->id }}">
                    <video id="video-{{ $student->id }}" class="w-full h-full object-cover hidden" muted playsinline></video>
                    <span class="text-xs text-gray-500" id="video-overlay-{{ $student->id }}">
                        {{ ($student->pivot && $student->pivot->is_present) ? 'Connecting...' : 'Offline' }}
                    </span>
                </div>

                <div class="flex space-x-2" id="btn-container-{{ $student->id }}">
                    @if($student->pivot && $student->pivot->is_present)
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
                <p class="text-gray-400 italic">No students are currently active in this session.</p>
            </div>
        @endforelse
    </div>

@elseif(isset($class) && !$class->is_active)
    {{-- This shows when the class exists but the "Start Session" button hasn't been clicked --}}
    <div class="col-span-full py-20 text-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
        <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-shield-user-line text-3xl text-gray-400"></i>
        </div>
        <h3 class="text-gray-600 font-bold">Proctoring Paused</h3>
        <p class="text-gray-400 text-sm">Monitoring is disabled. Click "Start Lab Session" to begin.</p>
    </div>

@else
    {{-- This shows if no class/subject is selected at all --}}
    <div class="col-span-full py-20 text-center bg-white rounded-xl border border-dashed">
        <i class="ri-error-warning-line text-4xl text-gray-300 mb-2"></i>
        <p class="text-gray-500">No active laboratory session selected.</p>
    </div>
@endif

{{-- Fullscreen Modal Viewer --}}
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
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
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
        adminPeer = new Peer('PROF_{{ auth()->id() }}');

        adminPeer.on('open', (id) => {
            console.log('✅ Professor Peer ready: ' + id);
        });

        // 🟢 NEW: Safely accept incoming student screens!
        adminPeer.on('call', (call) => {
            const studentId = call.peer.replace('STUDENT_', '');
            console.log("📞 Incoming feed from student:", studentId);
            
            // Answer the call without sending our own video back
            call.answer(); 
            
            call.on('stream', (remoteStream) => {
                console.log("🎥 Stream received from student:", studentId);
                updateVideoUI(studentId, remoteStream);
                connectedStudents.add(String(studentId));
            });

            call.on('close', () => resetStudentUI(studentId));
            call.on('error', () => resetStudentUI(studentId));
        });

        adminPeer.on('error', (err) => {
            if (err.type === 'unavailable-id') {
                console.warn("⚠️ Professor ID taken! Close duplicate tabs.");
            }
        });
    }

    // 2. UI Helpers
    function updateVideoUI(studentId, remoteStream) {
        const video = document.getElementById('video-' + studentId);
        const overlay = document.getElementById('video-overlay-' + studentId);
        const btn = document.querySelector(`#btn-container-${studentId} button`);
        
        if (video) {
            video.srcObject = remoteStream;
            video.classList.remove('hidden');
            if (overlay) overlay.classList.add('hidden');
            
            // Force play and catch any browser auto-play errors
            video.play().catch(e => console.log("Play error:", e));
            
            if (btn) {
                btn.innerText = "View Screen";
                btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                btn.classList.add('bg-[#383838]', 'text-white', 'hover:bg-black');
                btn.disabled = false;
            }
        }
    }

    function resetStudentUI(studentId) {
        const video = document.getElementById('video-' + studentId);
        const overlay = document.getElementById('video-overlay-' + studentId);
        const btn = document.querySelector(`#btn-container-${studentId} button`);

        if (video) { 
            video.srcObject = null; 
            video.classList.add('hidden'); 
        }
        
        if (overlay) {
            overlay.classList.remove('hidden');
            overlay.innerText = "Offline";
        }
        
        if (btn) { 
            btn.innerText = "Waiting..."; 
            btn.classList.remove('bg-[#383838]', 'text-white', 'hover:bg-black');
            btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.disabled = true;
        }

        connectedStudents.delete(String(studentId));
    }

    // 3. Fullscreen Viewer
    function openFullscreenViewer(studentId, studentName) {
        const gridVideo = document.getElementById(`video-${studentId}`);
        const modalVideo = document.getElementById('modal-video');

        if (gridVideo && gridVideo.srcObject) {
            modalVideo.srcObject = gridVideo.srcObject;
            
            window.dispatchEvent(new CustomEvent('open-modal', { 
                detail: { name: studentName } 
            }));
        } else {
            alert("No active screen share to view.");
        }
    }

    // Initialize immediately
    document.addEventListener('DOMContentLoaded', () => {
        initAdminPeer();
    });
</script>