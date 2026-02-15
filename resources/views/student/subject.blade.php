<x-app-layout>
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


// STUDENT SIDE
async function startScreenShare() {
    try {
        // 1. Get the screen
        window.localStream = await navigator.mediaDevices.getDisplayMedia({ 
            video: { displaySurface: "monitor" } 
        });

        const track = window.localStream.getVideoTracks()[0];
        const settings = track.getSettings();
        
        // 2. ENTIRE SCREEN CHECK (Only once)
        if (settings.displaySurface !== 'monitor') {
            alert("❌ Access Denied: You must share your ENTIRE SCREEN to continue.");
            window.localStream.getTracks().forEach(t => t.stop());
            return; 
        }

        // 3. STOP SHARING DETECTION (Only once, includes Fetch)
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

        console.log("✅ Entire Screen capture successful");

        // 4. Initialize Peer
        const studentPeer = new Peer('STUDENT_{{ auth()->id() }}');

        studentPeer.on('open', (id) => {
            console.log('✅ Student is online. Calling Admin...');
            const adminId = 'ADMIN_1'; 
            const call = studentPeer.call(adminId, window.localStream);
            console.log('📞 Calling Admin at: ' + adminId);
        });

        studentPeer.on('error', (err) => {
            console.error('PeerJS Student Error:', err);
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
</script>
</x-app-layout>