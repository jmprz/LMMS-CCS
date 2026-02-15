<x-app-layout>
   <x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('LMMS-CCS Admin Command Center') }}
        </h2>
        <div class="flex space-x-2">
            <a href="{{ route('admin.students.create') }}"
                class="bg-gray-800 text-white px-4 py-2 rounded text-sm shadow hover:bg-gray-700 transition">Add New Student</a>
        </div>
    </div>
</x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6 border-l-4 border-indigo-500">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Create New Laboratory Session</h3>
            <form action="{{ route('admin.generate-code') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700">Subject Code</label>
                        <input type="text" name="subject_name" placeholder="e.g., SOFTENG1"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Day</label>
                        <select name="schedule_day" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500">
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Time Range</label>
                        <div class="flex items-center gap-2">
                            <input type="time" name="start_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500" required>
                            <span class="mt-1 text-gray-500">-</span>
                            <input type="time" name="end_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm focus:ring-indigo-500" required>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm uppercase tracking-wider">
                            Generate Code
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if(session('class_code'))
            <div class="bg-green-100 border-t-4 border-green-500 rounded-b text-green-900 px-4 py-3 shadow-md mb-6 animate-bounce" role="alert">
                <div class="flex">
                    <div class="py-1">
                        <svg class="fill-current h-6 w-6 text-green-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z" /></svg>
                    </div>
                    <div>
                        <p class="font-bold">New Session Generated!</p>
                        <p class="text-sm">Give this code to your students: <span class="text-2xl font-mono font-black tracking-widest bg-white px-2 rounded border border-green-300 ml-2">{{ session('class_code') }}</span></p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Current Active Sessions</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($activeSessions as $session)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-indigo-600">{{ $session->class_code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $session->subject_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $session->schedule_day }} ({{ $session->schedule_time }})</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form action="{{ route('admin.sessions.end', $session->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-bold">End Session</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold">Live Laboratory Monitor</h3>
                <div class="flex items-center space-x-4 text-sm">
                    <span class="flex items-center"><span class="h-3 w-3 bg-green-500 rounded-full mr-1"></span> Active</span>
                    <span class="flex items-center"><span class="h-3 w-3 bg-gray-300 rounded-full mr-1"></span> Offline</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="student-grid">
                @foreach($activeStudents as $student)
                    @php
                        $isPresent = $student->joinedClasses->where('pivot.is_present', true)->count() > 0;
                    @endphp
                    
                    <div class="border-2 rounded-lg p-4 transition-all {{ $isPresent ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-gray-50 shadow-sm' }}" id="card-{{ $student->id }}">
                        <div class="flex justify-between items-start mb-2">
                            <p class="font-bold text-gray-700">{{ $student->name }}</p>
                            <span class="h-3 w-3 rounded-full {{ $isPresent ? 'bg-green-500 animate-pulse' : 'bg-gray-300' }}"></span>
                        </div>
                        
                        <div class="w-full aspect-video bg-black rounded mb-3 overflow-hidden border border-gray-300 relative">
                          <video id="video-{{ $student->id }}" 
       autoplay 
       playsinline 
       muted 
       class="w-full h-full bg-black object-contain">
</video>
                            
                            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-60 {{ $isPresent ? 'hidden' : '' }}">
                                <span class="text-white text-xs font-mono uppercase tracking-tighter">No Feed Available</span>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mb-3">Year Level: {{ $student->year_level }}</p>

                        <div class="flex space-x-2 mt-2">
                            @if($isPresent)
                                <button onclick="startSpectating('{{ $student->id }}')"
                                    class="flex-1 text-xs bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded font-semibold transition">Connect Feed</button>
                            @else
                                <button disabled class="flex-1 text-xs bg-gray-300 text-gray-500 py-2 rounded font-semibold cursor-not-allowed">Waiting...</button>
                            @endif
                            <button onclick="sendLockCommand('{{ $student->id }}')" class="flex-1 text-xs bg-red-500 hover:bg-red-600 text-white py-2 rounded font-semibold transition">Lock PC</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
 <script>
let adminPeer;

function initAdminPeer() {
    adminPeer = new Peer('ADMIN_{{ auth()->id() }}'); 

    adminPeer.on('open', (id) => {
        console.log('✅ Admin Peer is ready. ID: ' + id);
    });

    adminPeer.on('error', (err) => {
        console.error('PeerJS Admin Error:', err.type);
    });

    adminPeer.on('call', (call) => {
        const studentId = call.peer.replace('STUDENT_', '');
        console.log('📞 Incoming stream from Student: ' + studentId);
        
        // Answer the call
        call.answer(); 

        // 1. HANDLE THE VIDEO STREAM
        call.on('stream', (remoteStream) => {
            const video = document.getElementById('video-' + studentId);
            if (video) {
                video.srcObject = remoteStream;
                video.play();
                
                // Update UI: Hide overlay and set button to "Live"
                document.querySelector(`#card-${studentId} .absolute`)?.classList.add('hidden');
                const btn = document.querySelector(`#card-${studentId} button`);
                if (btn) {
                    btn.innerText = "● LIVE";
                    btn.classList.replace('bg-indigo-600', 'bg-red-600'); // Optional: turn red for live
                }
            }
        });

        // 2. HANDLE DISCONNECTION (Combined both your 'close' blocks)
        call.on('close', () => {
            console.log(`📡 Student ${studentId} stopped their feed.`);
            
            const video = document.getElementById('video-' + studentId);
            if (video) video.srcObject = null;
            
            // Show the "Disconnected" overlay again
            const card = document.getElementById('card-' + studentId);
            if (card) {
                const overlay = card.querySelector('.absolute');
                if (overlay) {
                    overlay.classList.remove('hidden');
                    overlay.innerHTML = `<div class="bg-black/60 w-full h-full flex items-center justify-center text-white font-bold">⚠️ FEED STOPPED</div>`;
                }
                
                const btn = card.querySelector('button');
                if (btn) {
                    btn.innerText = "Waiting for Student...";
                    btn.classList.replace('bg-red-600', 'bg-gray-400');
                    btn.disabled = true;
                }
            }
        });
    });
}

initAdminPeer();

function startSpectating(studentId) {
    const targetId = 'STUDENT_' + studentId;
    console.log('📞 Attempting to call: ' + targetId);

    if (!adminPeer || !adminPeer.open) {
        console.error("Admin Peer is not connected to the signaling server.");
        initAdminPeer();
        return;
    }

    try {
        // PeerJS needs a stream object to initiate the handshake
        const dummyStream = new MediaStream();
        const call = adminPeer.call(targetId, dummyStream);

        if (!call) {
            console.error("PeerJS: Call object returned undefined.");
            alert("Connection error: The call could not be initialized.");
            return;
        }

        // Visual feedback
        const btn = document.querySelector(`#card-${studentId} button`);
        if (btn) btn.innerText = "Connecting...";

        call.on('stream', (remoteStream) => {
            console.log('🎥 Stream received! ID: ' + targetId);
            const video = document.getElementById('video-' + studentId);

            if (video) {
                video.srcObject = remoteStream;

                // Wait until the video can actually play
                video.oncanplay = () => {
                    video.play();
                    if (btn) btn.innerText = "Live";

                    // Hide "No Feed" overlay
                    const container = video.parentElement;
                    const overlay = container.querySelector('.absolute');
                    if (overlay) overlay.classList.add('hidden');
                };
            }
        });
        
        // Handle call errors (like if the student rejects or hangs up)
        call.on('error', (err) => {
            console.error("Call error:", err);
            if (btn) btn.innerText = "Connect Feed";
        });

    } catch (err) {
        console.error("Try-Catch Error:", err);
    }
} // <--- Added this bracket to close startSpectating

function checkPresence() {
    fetch('{{ route("admin.status-check") }}')
        .then(response => response.json())
        .then(data => {
            const presentIds = data.present_ids.map(id => String(id));

            document.querySelectorAll('[id^="card-"]').forEach(card => {
                const studentId = String(card.id.replace('card-', ''));
                const dot = card.querySelector('.rounded-full');
                const videoOverlay = card.querySelector('.absolute');
                const btnContainer = card.querySelector('.flex.space-x-2.mt-2');

                if (presentIds.includes(studentId)) {
                    // Update Online UI
                    dot.classList.replace('bg-gray-300', 'bg-green-500');
                    dot.classList.add('animate-pulse');
                    card.classList.replace('border-gray-200', 'border-green-500');
                    card.classList.replace('bg-gray-50', 'bg-green-50');

                    // Only hide overlay if not already live
                    const video = document.getElementById('video-' + studentId);
                    if (video && !video.srcObject && videoOverlay) {
                        videoOverlay.classList.remove('hidden');
                    }

                    // Only update innerHTML if it's currently showing "Waiting..." 
                    if (btnContainer.innerHTML.includes('Waiting...')) {
                        btnContainer.innerHTML = `
                            <button onclick="startSpectating('${studentId}')"
                                class="flex-1 text-xs bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded font-semibold transition">
                                Connect Feed
                            </button>
                            <button onclick="sendLockCommand('${studentId}')"
                                class="flex-1 text-xs bg-red-500 hover:bg-red-600 text-white py-2 rounded font-semibold transition">
                                Lock PC
                            </button>
                        `;
                    }
                } else {
                    // Update Offline UI
                    dot.classList.replace('bg-green-500', 'bg-gray-300');
                    dot.classList.remove('animate-pulse');
                    card.classList.replace('border-green-500', 'border-gray-200');
                    card.classList.replace('bg-green-50', 'bg-gray-50');

                    if (videoOverlay) videoOverlay.classList.remove('hidden');

                    btnContainer.innerHTML = `
                        <button disabled class="flex-1 text-xs bg-gray-300 text-gray-500 py-2 rounded font-semibold cursor-not-allowed">
                            Waiting...
                        </button>
                        <button class="flex-1 text-xs bg-red-500 hover:bg-red-600 text-white py-2 rounded font-semibold transition">
                            Lock PC
                        </button>
                    `;
                }
            });
        });
}

setInterval(checkPresence, 5000);
</script>
</x-app-layout>