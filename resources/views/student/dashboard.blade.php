<x-app-layout>
    <x-slot name="header"></x-slot>
    <div id="lockdown-ui" class="hidden fixed inset-0 z-[9999] bg-white w-full h-screen">
        <div class="flex w-full h-full">
            <div class="w-1/2 h-full bg-black relative">
                <video id="professor-screen" autoplay playsinline
                    class="absolute inset-0 w-full h-full object-contain"></video>
            </div>

            <div
                class="w-1/2 h-full p-8 overflow-y-auto bg-gray-50 border-l border-gray-200 flex flex-col justify-between">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 mb-4 uppercase tracking-tight">Workspace Locked</h2>
                    <p class="text-gray-500 font-medium">The professor has restricted your device viewport. Please track
                        the presentation broadcast shown on the left panel display.</p>
                </div>
                <div class="pt-6 border-t border-gray-200">
                    <span
                        class="inline-flex items-center text-[10px] font-black text-amber-600 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg uppercase tracking-wider">
                        <i class="ri-broadcast-line mr-1.5 animate-pulse text-sm"></i> Live Monitoring Active
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div id="dashboard-root" class="fixed inset-x-0 bottom-0 top-20 flex bg-gray-50 overflow-hidden" x-data="{ 
    sidebarOpen: false,
    classes: @js($joinedClasses->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->subject_name,
                'code' => $item->class_code,
                'program' => $item->program ?? 'N/A',
                'year' => $item->year_level ?? 'N/A',
                'section' => $item->section ?? 'N/A',
                'instructor' => $item->faculty->name,
                'day' => $item->schedule_day,
                'time' => $item->schedule_time,
                'attendance' => $item->total_attended_days ?? 0,
                'isOpen' => (bool) $item->is_active,
                'route' => route('student.subject', $item->id)
            ])),

            isScheduleActive(cls) {
                const now = new Date();
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const currentDay = days[now.getDay()];
                
                if (cls.day !== currentDay) return false;

                try {
                    const [startStr, endStr] = cls.time.split(' - ');
                    const parseTime = (timeStr) => {
                        const [time, modifier] = timeStr.split(' ');
                        let [hours, minutes] = time.split(':');
                        if (modifier === 'PM' && hours < 12) hours = parseInt(hours) + 12;
                        if (modifier === 'AM' && hours == 12) hours = 0;
                        const d = new Date();
                        d.setHours(hours, minutes, 0);
                        return d;
                    };
                    return now >= parseTime(startStr) && now <= parseTime(endStr);
                } catch (e) {
                    return false;
                }
            },

            get activeClasses() {
                return this.classes.filter(c => c.isOpen && this.isScheduleActive(c));
            },

            get offlineClasses() {
                return this.classes.filter(c => !c.isOpen || !this.isScheduleActive(c));
            },

            // Directly triggers screen share protocol and navigates seamlessly
           enterClassroomDirectly(route) {
        // Only run screen share protocol if NOT on mobile/tablet
        if (!this.isMobile() && typeof enterClassroom === 'function') {
        enterClassroom();
        }
     window.location.href = route;
    },
        isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) 
        || (window.innerWidth <= 1024 && navigator.maxTouchPoints > 0);
            }
         }">

         <div x-show="sidebarOpen" 
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-30 bg-black/50 lg:hidden"
             x-cloak>
        </div>

      <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed lg:static z-40 w-64 border-r border-gray-200 bg-white flex-shrink-0 flex flex-col justify-between h-full transition-transform duration-200 ease-in-out">
            <div class="flex flex-col flex-grow overflow-y-auto">

                <nav class="mt-8 px-4 space-y-1">
                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Workspace
                    </div>

                    <a href="#"
                        class="flex items-center py-2.5 px-4 rounded-xl text-xs bg-[#383838] text-white font-black shadow-sm transition duration-150">
                        <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                    </a>
                </nav>

                <nav class="mt-6 px-4 space-y-1">
                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">My Courses
                    </div>

                    <template x-for="cls in classes" :key="cls.id">
                        <div>
                            <a href="#"
                                @click.prevent="if(cls.isOpen && isScheduleActive(cls)) { enterClassroomDirectly(cls.route) }"
                                :class="cls.isOpen && isScheduleActive(cls) 
                               ? 'text-gray-700 hover:bg-green-50/60 border border-transparent cursor-pointer' 
                               : 'text-gray-400 opacity-60 cursor-not-allowed pointer-events-none border border-transparent'"
                                class="flex items-start justify-between py-3 px-4 rounded-xl text-xs transition duration-150">

                                <div class="flex items-start min-w-0 mr-2">
                                    <i :class="cls.isOpen && isScheduleActive(cls) ? 'ri-book-3-line text-green-600' : 'ri-lock-line'"
                                        class="text-lg mr-3 flex-shrink-0 mt-0.5"></i>

                                    <div class="flex flex-col min-w-0">
                                        <span class="truncate font-black text-xs tracking-tight uppercase"
                                            :class="cls.isOpen && isScheduleActive(cls) ? 'text-gray-800' : 'text-gray-500'"
                                            x-text="cls.code + ' | ' + cls.program + ' ' + ' - ' + cls.year + cls.section"></span>
                                        <span class="text-[10px] font-bold text-gray-400 truncate mt-1 tracking-wide"
                                            x-text="cls.day + ' • ' + cls.time"></span>
                                    </div>
                                </div>

                                <template x-if="cls.isOpen && isScheduleActive(cls)">
                                    <span class="flex h-2 w-2 relative flex-shrink-0 mt-1.5">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                    </span>
                                </template>
                            </a>
                        </div>
                    </template>
                </nav>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50/50 relative" x-data="{ open: false }"
                @click.away="open = false">

                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="transform opacity-0 scale-95 translate-y-2"
                    class="absolute bottom-full left-4 right-4 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl p-1.5 z-50 flex flex-col gap-0.5"
                    style="display: none;">


                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                            class="w-full text-left flex items-center px-3.5 py-2.5 text-xs font-black text-red-600 hover:bg-red-50 rounded-xl transition duration-150 tracking-wide">
                            <i class="ri-logout-box-r-line mr-2.5 text-base"></i> Sign Out
                        </button>
                    </form>
                </div>

                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group p-1 -m-1 rounded-xl hover:bg-gray-100/50 transition">
                    <div class="flex items-center min-w-0">
                        @php
                            $nameTokens = explode(' ', Auth::user()->name);
                            $firstInitial = substr($nameTokens[0], 0, 1);
                            $lastInitial = count($nameTokens) > 1 ? substr(end($nameTokens), 0, 1) : '';
                            $profileInitials = strtoupper($firstInitial . $lastInitial);
                        @endphp
                        <div
                            class="h-8 w-8 rounded-xl bg-[#383838] group-hover:bg-black flex items-center justify-center text-white text-[10px] font-black uppercase shadow-sm mr-2.5 flex-shrink-0 transition">
                            {{ $profileInitials }}
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-black text-gray-800 truncate leading-none">{{ Auth::user()->name }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-1 leading-none">
                                Student</p>
                        </div>
                    </div>

                    <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
                        :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
                </div>

            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full p-6 md:p-10">
           <!-- Mobile Header Toggle Bar -->
<div class="lg:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 -mt-6 -mx-6 md:-mt-10 md:-mx-10 mb-6 flex-shrink-0">
    <div class="flex items-center gap-2">
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition">
            <i class="ri-menu-2-line text-xl"></i>
        </button>
        <span class="text-xs font-black uppercase text-gray-700 tracking-wider"></span>
    </div>

    <!-- Right Side Profile & Sign Out Dropdown -->
    <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-1.5 p-1 rounded-xl hover:bg-gray-100 transition focus:outline-none">
            @php
                $nameTokens = explode(' ', Auth::user()->name);
                $firstInitial = substr($nameTokens[0], 0, 1);
                $lastInitial = count($nameTokens) > 1 ? substr(end($nameTokens), 0, 1) : '';
                $profileInitials = strtoupper($firstInitial . $lastInitial);
            @endphp
            <div class="h-8 w-8 rounded-xl bg-[#383838] flex items-center justify-center text-white text-[10px] font-black uppercase shadow-sm flex-shrink-0">
                {{ $profileInitials }}
            </div>
            <i class="ri-arrow-down-s-line text-gray-400 text-sm transition" :class="userMenuOpen ? 'transform rotate-180 text-gray-700' : ''"></i>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="userMenuOpen"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="transform opacity-0 scale-95 -translate-y-2"
             class="absolute right-0 mt-2 w-48 bg-white rounded-2xl border border-gray-200 shadow-xl p-1.5 z-50 flex flex-col gap-1"
             x-cloak
             style="display: none;">
            
            <div class="px-3 py-2 border-b border-gray-100">
                <p class="text-xs font-black text-gray-800 truncate leading-none">{{ Auth::user()->name }}</p>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-1 leading-none">Student</p>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit"
                    class="w-full text-left flex items-center px-3 py-2 text-xs font-black text-red-600 hover:bg-red-50 rounded-xl transition duration-150 tracking-wide">
                    <i class="ri-logout-box-r-line mr-2 text-base"></i> Sign Out
                </button>
            </form>
        </div>
    </div>
</div>
            <div class="max-w-9xl mx-auto space-y-8 pb-16">

                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight uppercase">My Dashboard</h1>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">
                        System environment reporting <span class="text-green-600 font-black"
                            x-text="activeClasses.length"></span> active lab structural pipelines.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                    <div class="lg:col-span-2 space-y-6">
                        <div x-data="{ 
                        gradedTasks: [],
                        init() {
                            this.fetchGradedTasks();
                            setInterval(() => this.fetchGradedTasks(), 5000);
                        },
                        fetchGradedTasks() {
                            fetch('{{ route('student.graded-tasks') }}')
                                .then(res => res.json())
                                .then(data => { this.gradedTasks = data; });
                        }
                     }">
                            <template x-if="gradedTasks.length > 0">
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                                        <h2 class="text-[10px] font-black text-blue-700 uppercase tracking-widest">
                                            Recently Graded</h2>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <template x-for="task in gradedTasks" :key="task.id">
                                            <div
                                                class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between transition-all hover:shadow-sm">
                                                <div>
                                                    <div class="flex justify-between items-start gap-2 mb-3">
                                                        <h3 class="text-sm font-black text-gray-900 leading-tight tracking-tight uppercase truncate max-w-[70%]"
                                                            x-text="task.title"></h3>
                                                        <span
                                                            class="text-[8px] font-black bg-blue-50 border border-blue-200 text-blue-700 px-2 py-0.5 rounded uppercase tracking-wider flex-shrink-0">GRADED</span>
                                                    </div>

                                                    <div
                                                        class="mb-3 p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                                                        <div class="flex justify-between items-center">
                                                            <span
                                                                class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Evaluation
                                                                Score</span>
                                                            <span class="text-base font-black"
                                                                :class="(task.submission.grade / task.points) >= 0.7 ? 'text-green-600' : 'text-amber-600'"
                                                                x-text="task.submission.grade + '/' + task.points"></span>
                                                        </div>

                                                        <div x-show="task.submission.feedback"
                                                            class="mt-2 pt-2 border-t border-gray-200">
                                                            <p
                                                                class="text-[8px] font-black text-gray-400 uppercase tracking-wider mb-0.5">
                                                                Instructor Review Feedback</p>
                                                            <p class="text-[11px] text-gray-600 italic leading-tight"
                                                                x-text="task.submission.feedback"></p>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center justify-between pt-1 text-[10px]">
                                                        <p class="font-bold text-gray-500 truncate mr-2"
                                                            x-text="'Prof. ' + task.lab_session.faculty.name"></p>
                                                        <p class="font-black text-gray-400 uppercase tracking-tighter flex-shrink-0"
                                                            x-text="new Date(task.submission.updated_at).toLocaleDateString()">
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        @if($pendingTasks->count() > 0)
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="flex h-2 w-2 relative">
                                        <span
                                            class="animate-pulse absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                    </span>
                                    <h2 class="text-[10px] font-black text-amber-700 uppercase tracking-widest">Pending
                                        Review</h2>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($pendingTasks as $task)
                                        <div
                                            class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between opacity-95 transition-all hover:scale-[1.01]">
                                            <div>
                                                <div class="flex justify-between items-start gap-2 mb-2">
                                                    <h3
                                                        class="text-sm font-black text-gray-900 leading-tight tracking-tight uppercase truncate max-w-[70%]">
                                                        {{ $task->title }}</h3>
                                                    <span
                                                        class="text-[8px] font-black bg-amber-50 border border-amber-200 text-amber-700 px-2 py-0.5 rounded uppercase tracking-wider flex-shrink-0">SUBMITTED</span>
                                                </div>
                                                <p class="text-[11px] font-medium text-gray-400 mb-3 italic leading-snug">The
                                                    instructor is currently evaluating your submitted artifacts.</p>

                                                <div class="pt-2 border-t border-gray-50 flex flex-col gap-0.5">
                                                    <p class="text-[11px] font-bold text-gray-600 truncate">Prof.
                                                        {{ $task->labSession->faculty->name ?? 'Unknown' }}</p>
                                                    <p
                                                        class="text-[9px] font-black text-gray-400 uppercase tracking-wider truncate">
                                                        {{ $task->labSession->subject_name }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif



                    </div>

                    <div class="lg:col-span-1">
                        <div>
                            <h2 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Class
                                Schedule</h2>
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-gray-50 text-gray-400 border-b border-gray-100">
                                        <tr>
                                            <th class="px-4 py-2.5 text-[9px] font-black uppercase tracking-wider">
                                                Course</th>
                                            <th
                                                class="px-4 py-2.5 text-[9px] font-black uppercase tracking-wider text-right">
                                                Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="cls in classes" :key="cls.id">
                                            <tr class="hover:bg-gray-50/40 transition">
                                                <td class="px-4 py-3">
                                                    <div class="flex flex-col min-w-0">
                                                        <span
                                                            class="text-[11px] font-black text-gray-800 uppercase tracking-tight truncate"
                                                            x-text="cls.code + ' | ' + cls.program + '-' + cls.year + cls.section"></span>
                                                        <span
                                                            class="text-[10px] font-bold text-gray-400 mt-0.5 truncate max-w-[180px]"
                                                            x-text="cls.name"></span>
                                                        <span class="text-[9px] font-medium text-gray-400 mt-1"
                                                            x-text="cls.day + ' • ' + cls.time"></span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right valign-top">
                                                    <span
                                                        class="text-[8px] font-black px-2 py-0.5 border rounded uppercase tracking-wider inline-block"
                                                        :class="cls.isOpen && isScheduleActive(cls) ? 'bg-green-50 border-green-200 text-green-700 animate-pulse' : 'bg-gray-50 border-gray-200 text-gray-400'"
                                                        x-text="cls.isOpen && isScheduleActive(cls) ? 'LIVE NOW' : 'INACTIVE'"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script>
        setInterval(() => {
            fetch("{{ route('student.refresh-class-statuses') }}")
                .then(res => res.json())
                .then(data => {
                    const root = document.getElementById('dashboard-root');
                    if (!root) return;

                    const alpine = Alpine.$data(root);
                    alpine.classes.forEach(cls => {
                        if (data[cls.id] !== undefined) {
                            cls.isOpen = data[cls.id];
                        }
                    });
                    alpine.classes = [...alpine.classes];
                })
                .catch(err => console.error("Error refreshing sessions:", err));
        }, 5000);
    </script>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            const studentId = '{{ auth()->user()->id ?? "default-id" }}';

            // Uniform local server configuration options object (Matches your local server!)
         
         const localPeerOptions = {
        host: 'peer.lmms-ccs.online',
        port: 443,
        path: '/myapp',
        secure: true,
        config: {
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        },
        pingInterval: 5000, // Sends a ping every 5 seconds to prevent Cloudflare from dropping it
        debug: 1            // 1 = Errors only, 2 = Warnings, 3 = Everything
    };

            // Initialize peer using the local configuration options
            const peer = new Peer('STUDENT_' + studentId, localPeerOptions);

            peer.on('open', (id) => {
                console.log("✅ Student dashboard link registered locally with ID:", id);
            });

            peer.on('call', (call) => {
                console.log("📞 Incoming downstream stream feed from professor...");
                call.answer();

                call.on('stream', (professorStream) => {
                    console.log("🟢 Live lecture frame buffers received!");
                    const videoElement = document.getElementById('professor-screen');
                    if (videoElement) {
                        videoElement.srcObject = professorStream;

                        // Electron playback engine wake-up wrapper
                        videoElement.onloadedmetadata = () => {
                            videoElement.play()
                                .catch(err => console.warn("Video playback blocked:", err));
                        };
                    }

                    // Keep your custom Electron frame lockdown functionality intact
                    if (window.electronAPI) {
                        window.electronAPI.lockScreen();
                    }

                    const ui = document.getElementById('lockdown-ui');
                    if (ui) ui.classList.remove('hidden');

                    const normalView = document.getElementById('normal-view');
                    if (normalView) normalView.classList.add('hidden');
                });
            });

            // Catch errors immediately and print them to the console
            peer.on('error', (err) => {
                console.error('PeerJS Connection Error:', err.type, err.message);
            });

            // Laravel Echo dynamic listener state
            if (window.Echo) {
                // Using template variables for the class session channel ensures flexibility
                const channelId = '{{ $class->id ?? "1" }}';
                window.Echo.channel(`lab-session-${channelId}`)
                    .listen('ProfessorStoppedSharing', (e) => {
                        console.log("🔴 Echo received 'ProfessorStoppedSharing' broadcast.");
                        if (window.electronAPI) {
                            window.electronAPI.unlockScreen();
                        }

                        const ui = document.getElementById('lockdown-ui');
                        if (ui) ui.classList.add('hidden');

                        const normalView = document.getElementById('normal-view');
                        if (normalView) normalView.classList.remove('hidden');
                    });
            } else {
                console.warn("Laravel Echo is not initialized yet.");
            }
        });
    </script>
</x-app-layout>