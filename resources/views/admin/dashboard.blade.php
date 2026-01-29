<x-app-layout>
    <x-slot name="header">
        @if(session('success'))
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-4">
                <div class="bg-green-500 text-white p-4 rounded shadow-lg mb-6">
                    {{ session('success') }}
                </div>
            </div>
        @endif
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('LMMS-CCS Admin Command Center') }}
            </h2>

            <div class="flex space-x-2">
                <a href="{{ route('admin.students.create') }}"
                    class="bg-gray-800 text-white px-4 py-2 rounded text-sm">Add New Student</a>

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
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Day</label>
                            <select name="schedule_day"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
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
                                <input type="time" name="start_time"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" required>
                                <span class="mt-1 text-gray-500">-</span>
                                <input type="time" name="end_time"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm" required>
                            </div>
                        </div>

                        <div class="flex items-end">
                            <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition text-sm">
                                Generate Code
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if(session('class_code'))
                <div class="bg-green-100 border-t-4 border-green-500 rounded-b text-green-900 px-4 py-3 shadow-md mb-6"
                    role="alert">
                    <div class="flex">
                        <div class="py-1"><svg class="fill-current h-6 w-6 text-green-500 mr-4"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path
                                    d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z" />
                            </svg></div>
                        <div>
                            <p class="font-bold">New Session Generated!</p>
                            <p class="text-sm">Give this code to your students: <span
                                    class="text-2xl font-mono font-black tracking-widest">{{ session('class_code') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
    <div class="py-12">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-bold mb-6">Live Laboratory Monitor</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="student-grid">
                @foreach($activeStudents as $student)
                    <div class="border rounded-lg p-4 bg-gray-50 shadow-sm" id="card-{{ $student->id }}">
                        <div class="w-full aspect-video bg-black rounded mb-2 overflow-hidden">
                            <video id="video-{{ $student->id }}" autoplay playsinline
                                class="w-full h-full object-cover"></video>
                        </div>
                        <p class="font-bold text-gray-700">{{ $student->name }} (Year {{ $student->year_level }})</p>
                        <div class="flex space-x-2 mt-2">
                            <button onclick="startSpectating('{{ $student->id }}')"
                                class="flex-1 text-xs bg-green-500 text-white py-2 rounded font-semibold">Connect</button>
                            <button class="flex-1 text-xs bg-red-500 text-white py-2 rounded font-semibold">Lock PC</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    </div>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <script>
        const adminPeer = new Peer('ADMIN_{{ auth()->id() }}');

        function startSpectating(studentId) {
            console.log('Attempting to spectate Student: ' + studentId);
            const call = adminPeer.call('STUDENT_' + studentId, null);

            call.on('stream', (remoteStream) => {
                const video = document.getElementById('video-' + studentId);
                video.srcObject = remoteStream;
            });
        }
    </script>
</x-app-layout>