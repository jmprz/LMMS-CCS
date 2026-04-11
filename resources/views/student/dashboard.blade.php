<x-app-layout>

    <div id="lockdown-ui" class="hidden fixed inset-0 z-[9999] bg-white w-full h-screen">
        <div class="flex w-full h-full">
            <div class="w-1/2 h-full bg-black relative">
                <video id="professor-screen" autoplay playsinline class="absolute inset-0 w-full h-full object-contain"></video>
            </div>
            
            <div class="w-1/2 h-full p-8 overflow-y-auto bg-gray-50 border-l border-gray-200">
                <h2 class="text-2xl font-black text-gray-800 mb-4">Your Workspace</h2>
                <p class="text-gray-500">The professor has locked your screen. Please complete the tasks shown on the left.</p>
            </div>
        </div>
    </div>

    <div id="dashboard-root" 
         class="p-4 md:p-8 max-w-7xl mx-auto" 
         x-data="{ 
            classes: @js($joinedClasses->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->subject_name,
                'code' => $item->class_code,
                'instructor' => $item->faculty->name,
                'day' => $item->schedule_day,
                'time' => $item->schedule_time,
                'attendance' => $item->total_attended_days ?? 0,
                'isOpen' => (bool)$item->is_active,
                'route' => route('student.subject', $item->id)
            ])),
            // Filters classes that are currently LIVE
            get activeClasses() {
                return this.classes.filter(c => c.isOpen);
            },
            // Filters classes that are currently CLOSED
            get offlineClasses() {
                return this.classes.filter(c => !c.isOpen);
            }
         }">

        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-gray-900">My Dashboard</h1>
                <p class="text-gray-500 font-bold mt-1">
                    You have <span class="text-green-600" x-text="activeClasses.length"></span> active lab sessions right now.
                </p>
            </div>
        </div>

        <div x-show="activeClasses.length > 0" x-transition:enter="transition ease-out duration-300" class="mb-12">
            <div class="flex items-center gap-2 mb-6">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <h2 class="text-xs font-black text-green-700 uppercase tracking-widest">Active Lab Sessions</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="cls in activeClasses" :key="cls.id">
                    <div class="bg-white p-6 rounded-3xl border-2 border-green-500 shadow-xl shadow-green-100/50 flex flex-col justify-between transform hover:scale-[1.02] transition duration-300">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-black text-gray-900 leading-tight" x-text="cls.name"></h3>
                                <span class="text-[10px] font-black bg-green-100 text-green-700 px-3 py-1 rounded-full">LIVE</span>
                            </div>
                            <div class="space-y-1 mb-6">
                                <p class="text-xs font-bold text-gray-500" x-text="'Prof. ' + cls.instructor"></p>
                                <p class="text-[10px] font-black text-gray-400 uppercase" x-text="cls.attendance + ' Sessions Recorded'"></p>
                            </div>
                        </div>
                        <a :href="cls.route" class="block text-center w-full bg-black text-white font-bold py-4 rounded-2xl hover:bg-green-600 transition shadow-lg">
                            Enter Classroom
                        </a>
                    </div>
                </template>
            </div>
        </div>

        @if($pendingTasks->count() > 0)
            <div class="mb-12">
                <div class="flex items-center gap-2 mb-6">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-pulse absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                    <h2 class="text-xs font-black text-amber-700 uppercase tracking-widest">Pending Review</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($pendingTasks as $task)
                        <div class="bg-white p-6 rounded-3xl border-2 border-amber-500 shadow-xl shadow-amber-100/50 flex flex-col justify-between opacity-80">
                            <div>
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="text-xl font-black text-gray-900 leading-tight">{{ $task->title }}</h3>
                                    <span class="text-[10px] font-black bg-amber-100 text-amber-700 px-3 py-1 rounded-full">SUBMITTED</span>
                                </div>
                                <p class="text-xs font-bold text-gray-500 mb-4">Instructor is currently reviewing your work.</p>
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-gray-500">Prof. {{ $task->labSession->faculty->name }}</p>
                                    <p class="text-[10px] font-black text-gray-400 uppercase">{{ $task->labSession->subject_name }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(isset($pendingTasks) && $pendingTasks->count() > 0)
        <div class="mb-12">
            <div class="flex items-center gap-2 mb-6">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-pulse absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                </span>
                <h2 class="text-xs font-black text-amber-700 uppercase tracking-widest">Pending Review</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($pendingTasks as $task)
                    <div class="bg-white p-6 rounded-3xl border-2 border-amber-500 shadow-xl shadow-amber-100/50 flex flex-col justify-between opacity-90 transition-all hover:scale-[1.01]">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-black text-gray-900 leading-tight">{{ $task->title }}</h3>
                                <span class="text-[10px] font-black bg-amber-100 text-amber-700 px-3 py-1 rounded-full uppercase">Submitted</span>
                            </div>
                            <p class="text-xs font-bold text-gray-400 mb-4 italic">The instructor is currently reviewing your file.</p>
                            <div class="space-y-1">
                                <p class="text-xs font-bold text-gray-500">Prof. {{ $task->labSession->faculty->name ?? 'Unknown' }}</p>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">{{ $task->labSession->subject_name }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
        
        <!-- Graded Tasks Section -->
<div x-data="{ 
    gradedTasks: [],
    
    init() {
        this.fetchGradedTasks();
        setInterval(() => this.fetchGradedTasks(), 5000);
    },
    
    fetchGradedTasks() {
        fetch('{{ route('student.graded-tasks') }}')
            .then(res => res.json())
            .then(data => {
                this.gradedTasks = data;
            });
    }
}" class="mb-12">
    
    <div x-show="gradedTasks.length > 0" x-transition class="mb-12">
        <div class="flex items-center gap-2 mb-6">
            <span class="flex h-3 w-3 relative">
                <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
            </span>
            <h2 class="text-xs font-black text-blue-700 uppercase tracking-widest">Recently Graded</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="task in gradedTasks" :key="task.id">
                <div class="bg-white p-6 rounded-3xl border-2 border-blue-500 shadow-xl shadow-blue-100/50 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-black text-gray-900 leading-tight" x-text="task.title"></h3>
                            <span class="text-[10px] font-black bg-blue-100 text-blue-700 px-3 py-1 rounded-full">GRADED</span>
                        </div>
                        
                        <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-bold text-gray-500">Your Score</span>
                                <span class="text-2xl font-black" 
                                      :class="(task.submission.grade / task.points) >= 0.7 ? 'text-green-600' : 'text-amber-600'"
                                      x-text="task.submission.grade + '/' + task.points"></span>
                            </div>
                            
                            <div x-show="task.submission.feedback" class="mt-3 pt-3 border-t border-gray-200">
                                <p class="text-xs font-bold text-gray-400 uppercase mb-1">Feedback</p>
                                <p class="text-sm text-gray-700 italic" x-text="task.submission.feedback"></p>
                            </div>
                        </div>
                        
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-gray-500" x-text="'Prof. ' + task.lab_session.faculty.name"></p>
                            <p class="text-[10px] font-black text-gray-400 uppercase" 
                               x-text="'Graded: ' + new Date(task.submission.updated_at).toLocaleDateString()"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

        <div>
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Class Schedule</h2>
            <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-400">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black uppercase">Subject</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase">Schedule</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="cls in offlineClasses" :key="cls.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-800" x-text="cls.name"></span>
                                        <span class="text-[10px] font-black text-gray-400" x-text="cls.code"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-gray-600" x-text="cls.day + ' | ' + cls.time"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-[10px] font-black text-gray-300 bg-gray-100 px-3 py-1 rounded-full">CLOSED</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // Use a 5-second interval for better performance, checks for live sessions automatically
        setInterval(() => {
            fetch("{{ route('student.refresh-class-statuses') }}")
                .then(res => res.json())
                .then(data => {
                    const alpine = Alpine.$data(document.getElementById('dashboard-root'));
                    alpine.classes.forEach(cls => {
                        if (data[cls.id] !== undefined) {
                            cls.isOpen = data[cls.id];
                        }
                    });
                });
        }, 5000);
    </script>

        <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>

    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            const studentId = '{{ auth()->user()->id ?? "default-id" }}'; 
            const peer = new Peer('student-' + studentId); 

            peer.on('call', (call) => {
                call.answer(); 
                
                call.on('stream', (professorStream) => {
                    const videoElement = document.getElementById('professor-screen');
                    videoElement.srcObject = professorStream;
                    videoElement.play();
                    
                    if (window.electronAPI) {
                        window.electronAPI.lockScreen();
                    }
                    
                    const ui = document.getElementById('lockdown-ui');
                    ui.classList.remove('hidden');
                });
            });

            if (window.Echo) {
                window.Echo.channel('lab-session-1') 
                    .listen('ProfessorStoppedSharing', (e) => {
                        if (window.electronAPI) {
                            window.electronAPI.unlockScreen();
                        }
                        document.getElementById('lockdown-ui').classList.add('hidden');
                    });
            } else {
                console.warn("Laravel Echo is not initialized.");
            }
        });
    </script>
</x-app-layout>