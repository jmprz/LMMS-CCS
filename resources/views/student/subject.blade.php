<x-app-layout>
    <div class="text-center py-10">
        <h1 class="text-2xl font-bold">{{ $class->subject_name }}</h1>
        
        <div id="monitoring-area" class="mt-6">
            <button id="start-btn" onclick="markAttendance({{ $class->id }})" class="bg-green-600 text-white text-lg px-8 py-4 rounded-full shadow-lg hover:bg-green-700 transition">
                Present / Start Session
            </button>
            <div id="status-msg" class="mt-4 text-gray-600 font-semibold">You are currently offline.</div>
        </div>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <script>
    function markAttendance(classId) {
        document.getElementById('status-msg').innerHTML = "Status: <span class='text-green-600'>Monitoring Active</span>. Please stay on task.";
        document.getElementById('start-btn').classList.add('hidden');
        
        // Start PeerJS logic here
        startScreenShare(); 
    }

    function startScreenShare() {
        const peer = new Peer('STUDENT_{{ auth()->id() }}');
        peer.on('call', (call) => {
            navigator.mediaDevices.getDisplayMedia({ video: true }).then((stream) => {
                call.answer(stream);
            });
        });
    }
    </script>
</x-app-layout>