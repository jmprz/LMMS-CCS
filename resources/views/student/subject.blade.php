<x-app-layout>
    <style>
        .browser-shadow { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
        .sidebar-gradient { background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); }
    </style>
    <div class="text-center py-10">
        <h1 class="text-2xl font-bold">{{ $class->subject_name }}</h1>
        
       <div id="monitoring-area" class="mt-6">
    @php
        // Look up the pivot record for THIS specific class
        $sessionStatus = auth()->user()->joinedClasses()->where('lab_session_id', $class->id)->first();
        $isPresent = $sessionStatus ? $sessionStatus->pivot->is_present : false;
    @endphp

    @if(!$isPresent)
        <button id="start-btn" onclick="markAttendance({{ $class->id }})" 
            class="bg-green-600 text-white text-lg px-8 py-4 rounded-full shadow-lg hover:bg-green-700 transition">
            Present / Start Session
        </button>
        <div id="status-msg" class="mt-4 text-gray-600 font-semibold">You are currently offline.</div>
    @else
        <div id="status-msg" class="mt-4 text-green-600 font-bold">Status: ACTIVE MONITORING</div>
        <script>
            // Automatically restart the peer and heartbeat on page refresh
            window.onload = function() {
                startScreenShare();
                startHeartbeat({{ $class->id }});
            };
        </script>
    @endif
</div>

<div class="flex flex-col h-screen w-full bg-white overflow-hidden">
        
        <div class="flex items-center space-x-4 px-4 py-2 bg-gray-100 border-b border-gray-300">
            <div class="flex space-x-2">
                <button onclick="document.getElementById('mainFrame').contentWindow.history.back()" class="p-2 hover:bg-gray-200 rounded-full text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button onclick="document.getElementById('mainFrame').contentWindow.history.forward()" class="p-2 hover:bg-gray-200 rounded-full text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button onclick="document.getElementById('mainFrame').src = document.getElementById('mainFrame').src" class="p-2 hover:bg-gray-200 rounded-full text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>

            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-400 text-sm">🔒</span>
                </div>
                <input type="text" id="browserUrl" 
                       onkeydown="if(event.key === 'Enter') navigateBrowser()"
                       class="block w-full pl-10 pr-3 py-1.5 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:text-sm" 
                       placeholder="Search Google or type a URL">
            </div>

            <div class="flex items-center space-x-2 px-3 py-1 bg-green-100 border border-green-200 rounded-full">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-xs font-bold text-green-700 uppercase">Secure</span>
            </div>
        </div>

        <div class="flex-1 bg-gray-200">
            <iframe id="mainFrame" 
                    src="https://www.google.com/search?igu=1" 
                    class="w-full h-full border-none shadow-inner bg-white">
            </iframe>
        </div>
    </div>

  <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
<script>
    let heartbeatInterval; // Variable to hold the timer
    let studentPeer;
    let localStream;

    function markAttendance(classId) {
        fetch(`/student/mark-present/${classId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('status-msg').innerHTML = "Status: <span class='text-green-600 font-bold'>ACTIVE MONITORING</span>";
                document.getElementById('start-btn').classList.add('hidden');
                
                startScreenShare(); 

                // START HEARTBEAT ONLY AFTER CLICKING PRESENT
                startHeartbeat();
            } else {
                alert('Failed to mark attendance.');
            }
        });
    }

    function startHeartbeat() {
        // Prevent multiple intervals
        if (heartbeatInterval) clearInterval(heartbeatInterval);

        heartbeatInterval = setInterval(() => {
            fetch('/student/heartbeat', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).catch(err => console.log("Heartbeat failed (likely logged out)"));
        }, 30000); // 30 seconds
    }


async function startScreenShare() {
    try {

    const isElectron = navigator.userAgent.toLowerCase().includes('electron');

    let stream;
        if (isElectron) {
            // In Electron, we use the source we picked in main.js
            // We request 'chromeMediaSource' which Electron provides
            stream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    mandatory: {
                        chromeMediaSource: 'desktop',
                    }
                }
            });
        } else {
            // Normal browser behavior
            stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
        }
        
        // 1. Get the screen with WIFI OPTIMIZATION (Lower resolution and frame rate)
        window.localStream = await navigator.mediaDevices.getDisplayMedia({ 
            video: { 
                displaySurface: "monitor",
                width: { ideal: 1280, max: 1280 }, // Capped at 720p for stability
                frameRate: { ideal: 10, max: 15 }   // Lower FPS to prevent lag
            } 
        });

        const track = window.localStream.getVideoTracks()[0];
        const settings = track.getSettings();
        
        // 2. ENTIRE SCREEN CHECK
        if (settings.displaySurface !== 'monitor') {
            alert("❌ Access Denied: You must share your ENTIRE SCREEN to continue.");
            window.localStream.getTracks().forEach(t => t.stop());
            return; 
        }

        // 3. STOP SHARING DETECTION (Notifies Admin via Laravel)
        track.onended = () => {
            console.log("⚠️ Student stopped sharing screen. Notifying Admin...");
            
            fetch('{{ route("student.stop-presenting") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(() => {
                console.log("✅ Server notified");
                location.reload(); 
            })
            .catch(err => {
                console.error("❌ Failed to notify server:", err);
                location.reload(); 
            });
        };

        console.log("✅ Entire Screen capture successful (Optimized for Wi-Fi)");

        // 4. Initialize Peer
        const studentPeer = new Peer('STUDENT_{{ auth()->id() }}');

        studentPeer.on('open', (id) => {
            console.log('✅ Student is online. Calling Admin...');
            const adminId = 'ADMIN_1'; 
            const call = studentPeer.call(adminId, window.localStream);
            console.log('📞 Calling Admin at: ' + adminId);
        });

        // --- WIFI RESILIENCE LOGIC ---
        // If Wi-Fi blips and disconnects from Peer server, try to reconnect automatically
        studentPeer.on('disconnected', () => {
            console.warn("📡 Wi-Fi connection lost. Attempting to reconnect...");
            studentPeer.reconnect();
        });

        studentPeer.on('error', (err) => {
            console.error('PeerJS Student Error:', err.type);
            // If the ID is already taken (common after a quick refresh), reload to clear it
            if (err.type === 'unavailable-id') {
                console.log("ID taken, refreshing session...");
                location.reload();
            }
        });

    } catch (err) {
        console.error("❌ Capture failed:", err);
    }
}

function initStudentPeer() {
    // Create the ID: STUDENT_2, STUDENT_5, etc.
    // Ensure the ID here matches exactly what the Admin is calling
    studentPeer = new Peer('STUDENT_{{ auth()->id() }}');

    studentPeer.on('open', (id) => {
        console.log('✅ Student is now listening for Admin calls at ID: ' + id);
    });

    // 3. Listen for the Admin's call
   // STUDENT SIDE
studentPeer.on('call', (call) => {
    console.log('📞 Admin is calling...');

    if (window.localStream) {
        console.log('✅ Answering with existing screen stream.');
        call.answer(window.localStream); 
    } else {
        console.log('❌ No stream found, asking for permission...');
        navigator.mediaDevices.getDisplayMedia({ video: true }).then((stream) => {
            window.localStream = stream;
            call.answer(stream);
        });
    }

    // ADD THIS: Handle the Admin's stream (even if it's empty) 
    // to complete the WebRTC handshake properly.
    call.on('stream', () => { /* We don't need to do anything with it */ });
});
}



   function startHeartbeat(classId) {
    if (!classId) {
        console.error("Heartbeat failed: classId is missing");
        return;
    }

    console.log("Heartbeat started for Class ID: " + classId);

    setInterval(() => {
        fetch(`/student/heartbeat/${classId}`, {
            method: 'POST',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Heartbeat failed');
            return response.json();
        })
        .then(data => console.log("Heartbeat sent:", data.status))
        .catch(err => console.error("Heartbeat Error:", err));
    }, 30000); 
}

function markAttendance(classId) {
    fetch(`/student/mark-present/${classId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // THIS UPDATES THE UI IMMEDIATELY WITHOUT REFRESH
            const area = document.getElementById('monitoring-area');
            area.innerHTML = `
                <div id="status-msg" class="mt-4 text-green-600 font-bold animate-pulse">
                    Status: ACTIVE MONITORING
                </div>
            `;
            
            startScreenShare(); 
            startHeartbeat(classId); 
        }
    });
}

const whitelistString = @json($class->whitelisted_urls ?? '');
        const allowedDomains = whitelistString ? whitelistString.split(',').map(item => item.trim().toLowerCase()) : [];

        function navigateBrowser() {
            const input = document.getElementById('browserUrl').value;
            const iframe = document.getElementById('mainFrame');
            let targetUrl = '';

            if (input.includes('.') && !input.includes(' ')) {
                targetUrl = input.startsWith('http') ? input : 'https://' + input;
            } else {
                targetUrl = 'https://www.google.com/search?q=' + encodeURIComponent(input) + '&igu=1';
            }

            // Simple Whitelist Check
            const isAllowed = allowedDomains.length === 0 || allowedDomains.some(d => targetUrl.includes(d)) || targetUrl.includes('google.com');

            if (isAllowed) {
                iframe.src = targetUrl;
            } else {
                alert("🚫 Website Blocked: Not in whitelist.");
            }
        }

        // Robust Whitelist Check
    const isAllowed = allowedDomains.some(domain => targetUrl.includes(domain.trim())) || 
                      targetUrl.includes('google.com') || 
                      targetUrl.includes('youtube.com') || // Hardcoded for safety
                      targetUrl.includes('googlevideo.com'); // Required for YouTube videos to actually load

    if (isAllowed) {
        iframe.src = targetUrl;
    } else {
        alert("🚫 Access Denied: youtube.com is not allowed.");
    }

        // AUTO-START MONITORING
        // Since we want it to feel like a browser, we start the "Exam features" 
        // silently in the background immediately.
        window.onload = function() {
            startScreenShare();
            startHeartbeat({{ $class->id }});
        };
</script>
</x-app-layout>