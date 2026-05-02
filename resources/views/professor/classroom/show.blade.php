<x-app-layout>
    <div class="fixed inset-0 flex bg-gray-100"
        x-data="{ showModal: false, isActive: {{ $session->is_active ? 'true' : 'false' }} }">

        <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0">
            <nav class="mt-8 px-4 space-y-2">
                <a href="{{ route('professor.dashboard') }}"
                    class="flex items-center py-3 px-4 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>
                <a href="{{ route('professor.classroom') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('professor.classroom') ? 'text-gray-600 hover:bg-gray-100' : 'bg-[#383838] text-white font-bold' }} transition">
                    <i class="ri-graduation-cap-line mr-3 text-lg"></i>
                    Classroom
                </a>
                <a href="#" class="flex items-center py-3 px-4 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="ri-message-3-line mr-3 text-lg"></i> Message
                </a>
                <a href="#" class="flex items-center py-3 px-4 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="ri-history-line mr-3 text-lg"></i> Activity Log
                </a>
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto h-full">
            <div class="p-6 mt-[80px]">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="md:col-span-2 bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6">
                        <h1 class="text-4xl font-black text-gray-900 mb-3">{{ $session->subject_name }} |
                            {{ $session->program }} - {{ $session->year_level }}{{ $session->section }}
                        </h1>

                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                                <i class="ri-calendar-line mr-2"></i> {{ $session->schedule_day }}
                            </span>

                            <span
                                class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                                <i class="ri-time-line mr-2"></i> {{ $session->schedule_time }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-gray-200 shadow-sm rounded-2xl px-6 py-6 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-xs font-black text-gray-600 uppercase tracking-widest mb-2">Class Code</p>
                            <h2 class="text-3xl font-black text-black tracking-widest">{{ $session->class_code }}</h2>
                        </div>
                    </div>
                </div>


                <div class="mt-8" x-data="{ activeTab: 'monitoring' }">
                    <div class="flex space-x-1 border-b border-gray-200">
                        <button @click="activeTab = 'monitoring'"
                            :class="activeTab === 'monitoring' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Monitoring</button>
                        <button @click="activeTab = 'materials'"
                            :class="activeTab === 'materials' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Materials</button>
                        <button @click="activeTab = 'tasks'"
                            :class="activeTab === 'tasks' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Tasks</button>
                        <button @click="activeTab = 'quizzes'"
                            :class="activeTab === 'quizzes' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Quizzes</button>
                        <button @click="activeTab = 'students'"
                            :class="activeTab === 'students' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Students</button>
                        <button @click="activeTab = 'browser-security'"
                            :class="activeTab === 'browser-security' ? 'border-b-2 border-black text-black' : 'text-gray-500'"
                            class="px-6 py-3 transition">Security</button>
                    </div>

                    <div class="mt-6">
                        <div x-show="activeTab === 'monitoring'">

                            <div
                                class="bg-white p-6 rounded-xl shadow border border-gray-100 mb-6 flex justify-between items-center">
                                <div>
                                    <h2 class="font-bold text-lg">Session Control</h2>
                                    <p class="text-sm text-gray-500">Status:
                                        <span
                                            class="font-bold {{ $session->is_active ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $session->is_active ? 'LIVE' : 'OFFLINE' }}
                                        </span>
                                    </p>
                                </div>

                                <div id="broadcast-wrapper" class="flex items-center space-x-3" x-data="{ 
                                         isActive: {{ $class->is_active ? 'true' : 'false' }},
                                         isBroadcasting: {{ $class->is_broadcasting ? 'true' : 'false' }} 
                                     }">

                                    <button @click.prevent="toggleSession()" x-show="!isActive"
                                        class="px-6 py-2 rounded-lg font-bold text-white bg-green-600 hover:bg-green-700 shadow-sm transition-all flex items-center">
                                        <i class="ri-play-circle-line mr-1"></i> Start Lab Session
                                    </button>

                                    <button @click.prevent="toggleSession()" x-show="isActive" style="display: none;"
                                        class="px-6 py-2 rounded-lg font-bold text-white bg-red-600 hover:bg-red-700 shadow-sm transition-all flex items-center">
                                        <i class="ri-stop-circle-line mr-1"></i> Stop Lab Session
                                    </button>

                                    <button type="button" x-show="isActive && !isBroadcasting" style="display: none;"
                                        @click.prevent="toggleBroadcast()"
                                        class="px-6 py-2 rounded-lg text-white font-bold bg-blue-600 hover:bg-blue-700 transition-all shadow-sm flex items-center">
                                        <i class="ri-computer-line mr-1"></i> Share My Screen
                                    </button>

                                    <button type="button" x-show="isActive && isBroadcasting" style="display: none;"
                                        @click.prevent="toggleBroadcast()"
                                        class="px-6 py-2 rounded-lg text-white font-bold bg-orange-600 hover:bg-orange-700 transition-all shadow-sm flex items-center">
                                        <i class="ri-broadcast-line animate-pulse mr-1"></i> Stop Broadcasting
                                    </button>

                                    <button type="button" x-show="isActive && isBroadcasting" style="display: none;"
                                        @click.prevent="$dispatch('open-task-modal')"
                                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center transition-all shadow-sm">
                                        <i class="ri-add-line mr-1"></i> Give Task
                                    </button>
                                </div>
                            </div>
                            <div x-data="{ 
                                     showTaskModal: false,
                                     taskTitle: '',
                                     taskDesc: '',
                                     taskDeadline: '',
                                     taskPoints: 100,
                                     submitTask() {
                                         fetch('/professor/classroom/{{ $class->id }}/live-tasks', {
                                             method: 'POST',
                                             headers: {
                                                 'Content-Type': 'application/json',
                                                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                             },
                                             body: JSON.stringify({ 
                                                 title: this.taskTitle, 
                                                 description: this.taskDesc,
                                                 deadline: this.taskDeadline,
                                                 points: this.taskPoints
                                             })
                                         })
                                         .then(res => {
                                             if(res.ok) {
                                                 this.showTaskModal = false; 
                                                 this.taskTitle = ''; 
                                                 this.taskDesc = '';
                                                 this.taskDeadline = '';
                                                 this.taskPoints = 100;
                                                 alert('Task pushed to all students instantly!');
                                                 silentRefresh();
                                             }
                                         })
                                         .catch(err => console.error('Failed to assign task:', err));
                                     }
                                 }" @open-task-modal.window="showTaskModal = true">

                                <div x-show="showTaskModal" style="display: none;"
                                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm">
                                    <div class="bg-white w-1/3 p-6 rounded-2xl shadow-2xl"
                                        @click.away="showTaskModal = false">
                                        <h2 class="text-2xl font-black text-gray-800 mb-4">Assign Live Task</h2>

                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 mb-1">Task
                                                    Title</label>
                                                <input type="text" x-model="taskTitle"
                                                    placeholder="e.g. Create a Laravel Route"
                                                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-purple-500 focus:border-purple-500">
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label
                                                        class="block text-sm font-bold text-gray-700 mb-1">Deadline</label>
                                                    <input type="datetime-local" x-model="taskDeadline"
                                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-purple-500 focus:border-purple-500"
                                                        required>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-bold text-gray-700 mb-1">Max
                                                        Points</label>
                                                    <input type="number" x-model="taskPoints"
                                                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-purple-500 focus:border-purple-500"
                                                        required>
                                                </div>
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-bold text-gray-700 mb-1">Instructions</label>
                                                <textarea x-model="taskDesc"
                                                    placeholder="Describe what the students need to do..."
                                                    class="w-full border border-gray-300 rounded-lg p-3 h-28 focus:ring-purple-500 focus:border-purple-500"></textarea>
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-3 mt-6">
                                            <button @click.prevent="showTaskModal = false" type="button"
                                                class="px-5 py-2 font-bold text-gray-500 hover:text-gray-800">Cancel</button>
                                            <button @click.prevent="submitTask()" type="button"
                                                class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition-all">Push
                                                to Students</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($session->is_active)
                                <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
                                    <div class="flex justify-between items-center mb-4">
                                        <h2 class="font-bold text-lg text-gray-800">Live Laboratory Monitor</h2>
                                        <button id="refresh-monitor-btn" class="p-2 hover:bg-gray-100 rounded-lg transition"
                                            title="Refresh student list">
                                            <i class="ri-refresh-line text-xl"></i>
                                        </button>
                                    </div>
                                    @include('professor.partials.monitor-grid', ['activeStudents' => $activeStudents])
                                </div>
                            @else
                                <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed">
                                    <p class="text-gray-500 font-bold">Session is currently offline. Click "Start Session"
                                        to allow students to join.</p>
                                </div>
                            @endif
                        </div>

                        <div x-show="activeTab === 'materials'" x-cloak class="animate-fade-in">

                            <div class="flex justify-between items-center mb-8 ms-4 me-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">LEARNING
                                        MATERIALS MANAGEMENT</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Manage
                                        academic resources</p>
                                </div>
                                <button @click="$dispatch('open-material-modal')"
                                    class="bg-[#383838] text-white px-8 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200 active:scale-95">
                                    + Post Material
                                </button>
                            </div>

                            <div id="materials-list-container"
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                @forelse($class->materials as $material)
                                    <div
                                        class="bg-white p-6 rounded-[32px] border border-gray-100 flex flex-col justify-between group hover:border-black transition-all duration-300 shadow-sm hover:shadow-xl hover:shadow-gray-100">
                                        <div>
                                            <div class="flex items-center justify-between mb-4">
                                                <div
                                                    class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors duration-300">
                                                    @if($material->type == 'pdf')
                                                        <i class="ri-file-pdf-line text-xl"></i>
                                                    @elseif($material->type == 'youtube')
                                                        <i class="ri-youtube-line text-xl"></i>
                                                    @else
                                                        <i class="ri-file-line text-xl"></i>
                                                    @endif
                                                </div>
                                                <span
                                                    class="text-[9px] font-black text-gray-300 uppercase tracking-widest group-hover:text-gray-400 transition-colors">
                                                    {{ $material->type }}
                                                </span>
                                            </div>

                                            <h4
                                                class="font-black text-[#383838] text-lg leading-tight tracking-tight mb-2 group-hover:text-black">
                                                {{ $material->title }}
                                            </h4>
                                        </div>

                                        <div class="mt-6 pt-4 border-t border-gray-50 flex justify-between items-center">
                                            <a href="{{ $material->type === 'youtube' ? $material->content : url('/' . $material->content) }}"
                                                target="_blank"
                                                class="text-[10px] font-black uppercase text-gray-400 hover:text-black tracking-widest transition-colors">
                                                Preview Content
                                            </a>
                                            <button class="text-gray-300 hover:text-red-500 transition-colors">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full py-20 bg-white border-2 border-dashed border-gray-100 rounded-[32px] text-center">
                                        <i class="ri-folder-open-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 font-bold text-sm">No learning materials have been posted
                                            yet.</p>
                                    </div>
                                @endforelse
                            </div>

                            <template x-teleport="body">
                                <div x-data="{ open: false, type: 'pdf' }" @open-material-modal.window="open = true"
                                    x-show="open"
                                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-md p-4"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>

                                    <div @click.away="open = false"
                                        class="bg-white w-full max-w-lg rounded-[40px] shadow-2xl overflow-hidden animate-modal-in">

                                        <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                                            <div>
                                                <h3
                                                    class="text-2xl font-black text-[#383838] tracking-tighter uppercase">
                                                    Upload Content</h3>
                                                <p
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">
                                                    Add materials to student view</p>
                                            </div>
                                            <button @click="open = false"
                                                class="w-10 h-10 flex items-center justify-center bg-gray-50 rounded-full hover:bg-black hover:text-white transition-all">
                                                <i class="ri-close-line text-xl"></i>
                                            </button>
                                        </div>

                                        <form action="{{ route('professor.materials.store', $session->id) }}"
                                            method="POST" enctype="multipart/form-data" class="p-8 space-y-6"
                                            @submit.prevent="submitAjaxForm($event, () => open = false)">
                                            @csrf

                                            <div class="space-y-2">
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Title</label>
                                                <input type="text" name="title" required
                                                    placeholder="e.g., Introduction to Neural Networks"
                                                    class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-black outline-none transition-all font-bold text-[#383838]">
                                            </div>

                                            <div class="space-y-2">
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Content
                                                    Category</label>
                                                <select name="type" x-model="type"
                                                    class="w-full px-5 py-4 bg-gray-50 border-none rounded-2xl outline-none cursor-pointer focus:ring-2 focus:ring-black font-bold text-[#383838]">
                                                    <option value="pdf">PDF Document</option>
                                                    <option value="pptx">PowerPoint Presentation</option>
                                                    <option value="youtube">YouTube Video</option>
                                                </select>
                                            </div>

                                            <div
                                                class="p-6 bg-gray-50 rounded-[24px] border-2 border-dashed border-gray-200">
                                                <template x-if="type === 'youtube'">
                                                    <div class="space-y-2">
                                                        <label
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Video
                                                            URL</label>
                                                        <input type="url" name="content_url" required
                                                            placeholder="https://www.youtube.com/watch?v=..."
                                                            class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl outline-none font-medium">
                                                    </div>
                                                </template>

                                                <template x-if="type === 'pdf' || type === 'pptx'">
                                                    <div class="space-y-2 text-center">
                                                        <label
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-4">Select
                                                            Source File</label>
                                                        <input type="file" name="content_file" required
                                                            :accept="type === 'pdf' ? '.pdf' : '.ppt,.pptx'"
                                                            class="block w-full text-xs text-gray-400 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-black file:text-white hover:file:bg-gray-800 transition-all">
                                                    </div>
                                                </template>
                                            </div>

                                            <button type="submit"
                                                class="w-full bg-[#383838] text-white py-5 rounded-[24px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl shadow-gray-200">
                                                Publish to Classroom
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="activeTab === 'tasks'" x-data="{ 
        showModal: false, 
        selectedTask: null, 
        submissions: [],
        closeSubmissions() { this.selectedTask = null; }
    }" class="space-y-6" x-cloak>

                            <div class="flex justify-between items-center mb-8 ms-4 me-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Task
                                        Management</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Manage
                                        laboratory activities & grading</p>
                                </div>
                                <button @click="showModal = true"
                                    class="bg-[#383838] text-white px-6 py-2.5 rounded-xl font-bold uppercase text-xs hover:bg-black transition-all shadow-sm active:scale-95">
                                    + Create New Task
                                </button>
                            </div>

                            <div id="tasks-list-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @forelse($tasks as $task)
                                    <div
                                        class="bg-white p-5 rounded-2xl border border-gray-100 flex flex-col justify-between group hover:border-[#383838] transition-all shadow-sm">
                                        <div>
                                            <div class="flex justify-between items-start mb-4">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center border group-hover:bg-black group-hover:text-white transition">
                                                    <i class="ri-clipboard-line text-lg"></i>
                                                </div>
                                                <span
                                                    class="bg-gray-100 text-[#383838] px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                                                    {{ $task->points }} PTS
                                                </span>
                                            </div>

                                            <h4 class="font-bold text-gray-900 mb-1 group-hover:text-black transition">
                                                {{ $task->title }}
                                            </h4>

                                            <div class="space-y-2 mt-4">
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-calendar-todo-line mr-2"></i>
                                                    {{ \Carbon\Carbon::parse($task->deadline)->format('M d, h:i A') }}
                                                </div>
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-group-line mr-2"></i>
                                                    {{ $task->submissions->count() }} Submissions
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-6">
                                            <button
                                                @click="selectedTask = {{ json_encode($task) }}; submissions = {{ json_encode($task->submissions()->with('user')->get()) }}"
                                                class="w-full bg-gray-50 text-[#383838] border border-gray-200 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest">
                                                View Submissions
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center">
                                        <i class="ri-inbox-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 italic text-sm">No tasks assigned to this class yet.</p>
                                    </div>
                                @endforelse
                            </div>

                            <template x-if="selectedTask">
                                <div
                                    class="fixed inset-0 z-[100] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4">
                                    <div
                                        class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                                        <div class="p-6 border-b flex justify-between items-center bg-white">
                                            <div>
                                                <h3 class="font-black text-xl text-gray-900 uppercase tracking-tight"
                                                    x-text="selectedTask.title"></h3>
                                                <p
                                                    class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">
                                                    Review & Grading Portal</p>
                                            </div>
                                            <button @click="closeSubmissions()"
                                                class="text-gray-400 hover:text-black transition text-2xl">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>

                                        <div class="overflow-y-auto p-6 bg-gray-50/50">
                                            <table class="w-full text-left border-separate border-spacing-y-3">
                                                <thead>
                                                    <tr
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        <th class="px-6 pb-2">Student</th>
                                                        <th class="px-6 pb-2">Attachment</th>
                                                        <th class="px-6 pb-2 text-right">Grade & Feedback</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="sub in submissions" :key="sub.id">
                                                        <tr
                                                            class="bg-white shadow-sm rounded-2xl transition-all hover:shadow-md">
                                                            <td
                                                                class="px-6 py-4 font-bold text-gray-900 rounded-s-2xl border-y border-l border-gray-100">
                                                                <div class="flex items-center gap-3">
                                                                    <div
                                                                        class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-black">
                                                                        <span
                                                                            x-text="sub.user.first_name.charAt(0) + sub.user.last_name.charAt(0)"></span>
                                                                    </div>
                                                                    <span
                                                                        x-text="sub.user ? `${sub.user.last_name}, ${sub.user.first_name}` : 'N/A'"></span>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 border-y border-gray-100">
                                                                <a :href="'{{ url('/') }}/' + sub.file_path"
                                                                    target="_blank"
                                                                    class="inline-flex items-center text-[10px] font-black text-[#383838] bg-gray-50 px-3 py-2 rounded-lg hover:bg-black hover:text-white transition-all uppercase tracking-widest border border-gray-200">
                                                                    <i class="ri-download-2-line mr-2"></i> File
                                                                </a>
                                                            </td>
                                                            <td
                                                                class="px-6 py-4 rounded-e-2xl border-y border-r border-gray-100">
                                                                <form :action="'/professor/grade/' + sub.id"
                                                                    method="POST"
                                                                    class="flex items-center justify-end gap-3"
                                                                    @submit.prevent="submitGrade($event)">
                                                                    @csrf
                                                                    <div class="flex flex-col gap-1">
                                                                        <div
                                                                            class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-1">
                                                                            <input type="number" name="grade"
                                                                                :value="sub.grade"
                                                                                class="w-12 bg-transparent border-none p-0 text-sm font-black text-center focus:ring-0"
                                                                                placeholder="0">
                                                                            <span
                                                                                class="text-[10px] font-black text-gray-400 ml-1"
                                                                                x-text="'/ ' + selectedTask.points"></span>
                                                                        </div>
                                                                    </div>
                                                                    <textarea name="feedback" :value="sub.feedback"
                                                                        class="w-48 border-gray-200 rounded-xl text-[11px] py-2 px-3 focus:ring-1 focus:ring-black focus:border-black transition-all"
                                                                        placeholder="Feedback..."></textarea>
                                                                    <button type="submit"
                                                                        class="bg-[#383838] text-white p-2.5 rounded-xl hover:bg-black transition shadow-sm">
                                                                        <i class="ri-save-3-line"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>

                                            <template x-if="submissions.length === 0">
                                                <div
                                                    class="text-center py-20 bg-white rounded-3xl border border-gray-100 mt-4">
                                                    <p class="text-gray-400 italic text-sm font-medium">No submissions
                                                        to review yet.</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="showModal"
                                class="fixed inset-0 z-[110] flex items-center justify-center bg-[#383838]/90 backdrop-blur-md p-4">
                                <div
                                    class="bg-white p-8 rounded-[2rem] shadow-2xl w-full max-w-md border border-gray-100">
                                    <div class="mb-6">
                                        <h3 class="font-black text-2xl text-gray-900 tracking-tight uppercase">New Lab
                                            Activity</h3>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                            Fill in the assignment details</p>
                                    </div>

                                    <form action="{{ route('professor.tasks.store') }}" method="POST" class="space-y-5"
                                        @submit.prevent="submitAjaxForm($event, () => showModal = false)">
                                        @csrf
                                        <input type="hidden" name="subject_id" value="{{ $session->id }}">

                                        <div>
                                            <label
                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Activity
                                                Title</label>
                                            <input type="text" name="title" required
                                                class="w-full border-gray-100 bg-gray-50 rounded-2xl p-4 text-sm focus:bg-white focus:ring-2 focus:ring-black/5 outline-none transition-all">
                                        </div>

                                        <div>
                                            <label
                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Instructions</label>
                                            <textarea name="description" rows="3"
                                                class="w-full border-gray-100 bg-gray-50 rounded-2xl p-4 text-sm focus:bg-white focus:ring-2 focus:ring-black/5 outline-none transition-all"></textarea>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Max
                                                    Points</label>
                                                <input type="number" name="points" required
                                                    class="w-full border-gray-100 bg-gray-50 rounded-2xl p-4 text-sm focus:bg-white focus:ring-2 focus:ring-black/5 outline-none transition-all">
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Deadline</label>
                                                <input type="datetime-local" name="deadline" required
                                                    class="w-full border-gray-100 bg-gray-50 rounded-2xl p-4 text-[11px] focus:bg-white focus:ring-2 focus:ring-black/5 outline-none transition-all">
                                            </div>
                                        </div>

                                        <div class="flex gap-3 pt-4">
                                            <button type="button" @click="showModal = false"
                                                class="flex-1 py-4 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase text-[10px] hover:bg-gray-200 transition-all tracking-widest">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                class="flex-1 py-4 bg-[#383838] text-white rounded-2xl font-black uppercase text-[10px] hover:bg-black transition-all shadow-lg shadow-gray-200 tracking-widest">
                                                Save Task
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                      <div x-show="activeTab === 'quizzes'" x-data="{ 
        selectedQuiz: null, 
        scores: [],
        closeResults() { this.selectedQuiz = null; }
    }" class="space-y-6" x-cloak>

    <div class="flex justify-between items-center mb-8 ms-4 me-4">
        <div>
            <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Quiz Management</h2>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Control laboratory quiz & results</p>
        </div>
        <a href="{{ route('professor.quizzes.create', ['session_id' => $session->id]) }}"
            target="_blank"
            class="bg-[#383838] text-white px-6 py-2.5 rounded-xl font-bold uppercase text-xs hover:bg-black transition-all shadow-sm active:scale-95 inline-block">
            + Create Quiz
        </a>
    </div>

    <div id="quizzes-list-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($session->quizzes ?? [] as $quiz)
            <div class="bg-white p-5 rounded-2xl border border-gray-100 flex flex-col justify-between group hover:border-[#383838] transition-all shadow-sm">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center border group-hover:bg-black group-hover:text-white transition">
                            <i class="ri-timer-line text-lg"></i>
                        </div>
                        <span class="bg-gray-100 text-[#383838] px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                            {{ $quiz->questions->count() }} PTS
                        </span>
                    </div>

                    <h4 class="font-bold text-gray-900 mb-1 group-hover:text-black transition">
                        {{ $quiz->title }}
                    </h4>

                    <div class="space-y-2 mt-4">
                        <div class="flex items-center text-gray-500 text-[11px] font-medium">
                            <i class="ri-calendar-todo-line mr-2"></i>
                            {{ \Carbon\Carbon::parse($quiz->deadline)->format('M d, h:i A') }}
                        </div>
                        <div class="flex items-center text-gray-500 text-[11px] font-medium">
                            <i class="ri-time-line mr-2"></i>
                            {{ $quiz->time_limit }} Mins Duration
                        </div>
                        <div class="flex items-center text-gray-500 text-[11px] font-medium">
                            <i class="ri-group-line mr-2"></i>
                            {{ $quiz->attempts->count() }} Answered
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button
                        @click="selectedQuiz = {{ json_encode($quiz) }}; scores = {{ json_encode($quiz->attempts()->with('user')->get()) }}"
                        class="w-full bg-gray-50 text-[#383838] border border-gray-200 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest">
                        View Results
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center">
                <i class="ri-timer-flash-line text-4xl text-gray-200 mb-3 block"></i>
                <p class="text-gray-400 italic text-sm">No quizzes available for this session.</p>
            </div>
        @endforelse
    </div>

    <template x-if="selectedQuiz">
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center bg-white">
                    <div>
                        <h3 class="font-black text-xl text-gray-900 uppercase tracking-tight" x-text="selectedQuiz.title"></h3>
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Quiz Performance & Score Overview</p>
                    </div>
                    <button @click="closeResults()" class="text-gray-400 hover:text-black transition text-2xl">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="overflow-y-auto p-6 bg-gray-50/50">
                    <table class="w-full text-left border-separate border-spacing-y-3">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                <th class="px-6 pb-2">Student Name</th>
                                <th class="px-6 pb-2">Time Taken</th>
                                <th class="px-6 pb-2 text-right">Final Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="attempt in scores" :key="attempt.id">
                                <tr class="bg-white shadow-sm rounded-2xl transition-all hover:shadow-md">
                                    <td class="px-6 py-4 font-bold text-gray-900 rounded-s-2xl border-y border-l border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-[10px] font-black">
                                                <span x-text="attempt.user.first_name.charAt(0) + attempt.user.last_name.charAt(0)"></span>
                                            </div>
                                            <span x-text="attempt.user ? `${attempt.user.last_name}, ${attempt.user.first_name}` : 'N/A'"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 border-y border-gray-100">
                                        <div class="inline-flex items-center text-[10px] font-black text-gray-500 bg-gray-50 px-3 py-2 rounded-lg border border-gray-100 uppercase tracking-widest">
                                            <i class="ri-timer-line mr-2"></i>
                                            <span x-text="Math.floor(attempt.time_spent / 60) + 'm ' + (attempt.time_spent % 60) + 's'"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 rounded-e-2xl border-y border-r border-gray-100">
                                        <div class="flex items-center justify-end gap-3">
                                            <div class="px-4 py-2 border-2 border-gray-100 rounded-xl">
                                                <span class="text-xs font-black text-[#383838]" x-text="Math.round((attempt.score / attempt.total_questions) * 100) + '%'"></span>
                                            </div>
                                            <div class="px-4 py-2 bg-[#383838] text-white rounded-xl">
                                                <span class="text-sm font-black" x-text="attempt.score + ' / ' + attempt.total_questions"></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <template x-if="scores.length === 0">
                        <div class="text-center py-20 bg-white rounded-3xl border border-gray-100 mt-4">
                            <p class="text-gray-400 italic text-sm font-medium">No students have completed this quiz yet.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

                        <div x-show="activeTab === 'students'" x-cloak class="space-y-6 animate-fade-in">

                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 ms-4 me-4 gap-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Enrolled
                                        Students</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Monitor presence and connection status
                                    </p>
                                </div>
                                <div
                                    class="bg-gray-100 text-[#383838] px-5 py-2.5 rounded-xl flex items-center gap-3 border border-gray-200 shadow-sm">
                                    <i class="ri-team-line text-lg"></i>
                                    <span class="text-[10px] font-black uppercase tracking-widest">
                                        Total Enrolled: {{ $class->students->count() ?? 0 }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                @forelse($class->students as $student)
                                    <div
                                        class="bg-white p-5 rounded-3xl border border-gray-100 flex items-center justify-between group hover:border-[#383838] hover:shadow-lg transition-all duration-300">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-[#383838] font-black text-sm border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                                                {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                                            </div>

                                            <div class="flex flex-col min-w-0">
                                                <h4
                                                    class="font-black text-gray-900 text-sm truncate leading-tight group-hover:text-black transition">
                                                    {{ strtoupper($student->last_name) }}, {{ $student->first_name }}
                                                    @if($student->middle_name)
                                                    {{ strtoupper(substr($student->middle_name, 0, 1)) }}. @endif
                                                </h4>
                                                <p class="text-[10px] text-gray-400 font-bold tracking-widest mt-1">
                                                    {{ $student->school_id }}
                                                </p>
                                            </div>
                                        </div>

                                        <div>
                                            @if($student->pivot->is_present)
                                                <div class="w-3 h-3 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)] animate-pulse"
                                                    title="Active"></div>
                                            @else
                                                <div class="w-3 h-3 rounded-full bg-gray-200" title="Offline"></div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center bg-white">
                                        <i class="ri-user-unfollow-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 font-bold text-sm">No students enrolled in this session.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>



                        <!-- BROWSER SECURITY TAB -->
                        <div x-show="activeTab === 'browser-security'" x-data="browserSecurityManager()" x-cloak
                            class="space-y-6 animate-fade-in">

                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 ms-4 me-4 gap-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight uppercase">Browser
                                        Security</h2>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                                        Control and monitor website access
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                                <div class="xl:col-span-1 space-y-6">

                                    <div class="bg-white rounded-[2rem] border border-gray-100 p-8 shadow-sm">
                                        <h3
                                            class="text-xs font-black text-[#383838] uppercase tracking-widest mb-6 flex items-center">
                                            <i class="ri-global-line mr-2 text-lg"></i> Add Allowed Website
                                        </h3>

                                        <form @submit.prevent="addSite()" class="space-y-5">
                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Domain
                                                    *</label>
                                                <input type="text" x-model="newSite.domain" placeholder="youtube.com"
                                                    required
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none transition-all">
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold mt-2 px-1 uppercase tracking-widest">
                                                    Exclude http:// or www.</p>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Display
                                                    Name *</label>
                                                <input type="text" x-model="newSite.name" placeholder="YouTube" required
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none transition-all">
                                            </div>

                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Access
                                                    Scope</label>
                                                <select x-model="newSite.scope"
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none cursor-pointer transition-all">
                                                    <option value="global">Global (All tasks)</option>
                                                    <option value="task">Specific Task Only</option>
                                                </select>
                                            </div>

                                            <div x-show="newSite.scope === 'task'" x-collapse>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Target
                                                    Task</label>
                                                <select x-model="newSite.task_id"
                                                    class="w-full border-none bg-gray-50 rounded-2xl p-4 text-sm font-bold text-[#383838] focus:ring-2 focus:ring-black outline-none cursor-pointer transition-all">
                                                    <option value="">Select a task...</option>
                                                    <template x-for="task in tasks" :key="task.id">
                                                        <option :value="task.id" x-text="task.title"></option>
                                                    </template>
                                                </select>
                                            </div>

                                            <button type="submit" :disabled="adding"
                                                class="w-full py-4 bg-[#383838] text-white rounded-2xl font-black uppercase text-[10px] hover:bg-black transition-all shadow-lg tracking-widest mt-2">
                                                <span x-show="!adding">+ Whitelist Domain</span>
                                                <span x-show="adding"><i class="ri-loader-4-line animate-spin"></i>
                                                    Processing</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="xl:col-span-2 space-y-6">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="bg-gray-50 rounded-[2rem] border border-gray-100 p-8">
                                            <h3
                                                class="text-xs font-black text-gray-500 uppercase tracking-widest mb-6 flex items-center">
                                                <i class="ri-shield-check-line mr-2 text-lg"></i> System Approved
                                            </h3>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <template x-for="site in preApprovedSites" :key="site.id">
                                                    <div
                                                        class="bg-white border border-gray-100 rounded-2xl p-4 flex flex-col justify-center">
                                                        <span class="font-black text-[#383838] text-sm"
                                                            x-text="site.name"></span>
                                                        <span
                                                            class="text-[10px] text-gray-400 font-bold tracking-widest mt-1"
                                                            x-text="site.domain"></span>
                                                    </div>
                                                </template>
                                            </div>
                                            <div x-show="preApprovedSites.length === 0"
                                                class="text-center py-4 text-gray-400 text-[10px] uppercase font-bold tracking-widest">
                                                Loading defaults...
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-[2rem] border border-gray-100 p-8 shadow-sm">
                                            <h3
                                                class="text-xs font-black text-[#383838] uppercase tracking-widest mb-6 flex items-center">
                                                <i class="ri-list-check-2 mr-2 text-lg"></i> Custom Allowed
                                            </h3>

                                            <div class="space-y-3 max-h-[250px] overflow-y-auto pr-2">
                                                <template x-for="site in sessionSites" :key="site.id">
                                                    <div
                                                        class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl group transition-colors hover:bg-white hover:border hover:border-gray-200">
                                                        <div>
                                                            <h4 class="font-black text-sm text-[#383838]"
                                                                x-text="site.name"></h4>
                                                            <p class="text-[10px] text-gray-400 font-bold tracking-widest mt-0.5"
                                                                x-text="site.domain"></p>
                                                        </div>
                                                        <button @click="deleteSite(site.id)"
                                                            class="text-gray-300 hover:text-red-500 transition p-2">
                                                            <i class="ri-delete-bin-line text-lg"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                                <div x-show="sessionSites.length === 0"
                                                    class="text-center py-10 text-gray-400">
                                                    <i class="ri-node-tree text-3xl mb-2 block text-gray-200"></i>
                                                    <p class="text-[10px] uppercase font-bold tracking-widest">No custom
                                                        rules</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm">
                                        <div class="flex justify-between items-center mb-8">
                                            <h3
                                                class="text-xs font-black text-red-500 uppercase tracking-widest flex items-center">
                                                <i class="ri-shield-cross-line mr-2 text-lg"></i> Blocked Attempts Log
                                            </h3>
                                            <button @click="refreshBlockedAttempts()"
                                                class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#383838] transition">
                                                <i class="ri-refresh-line mr-1"></i> Refresh
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-3 gap-4 mb-8">
                                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center">
                                                <p class="text-3xl font-black text-[#383838]"
                                                    x-text="blockedStats.total || 0"></p>
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-2">
                                                    Total Blocked</p>
                                            </div>
                                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center">
                                                <p class="text-3xl font-black text-[#383838]"
                                                    x-text="blockedStats.by_domain?.length || 0"></p>
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-2">
                                                    Unique Sites</p>
                                            </div>
                                            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 text-center">
                                                <p class="text-3xl font-black text-[#383838]"
                                                    x-text="blockedStats.by_student?.length || 0"></p>
                                                <p
                                                    class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-2">
                                                    Students Affected</p>
                                            </div>
                                        </div>

                                        <div class="overflow-x-auto bg-gray-50 rounded-2xl border border-gray-100 p-4">
                                            <table class="w-full text-left">
                                                <thead>
                                                    <tr
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        <th class="px-5 pb-3 border-b border-gray-200">Student</th>
                                                        <th class="px-5 pb-3 border-b border-gray-200">Target URL</th>
                                                        <th class="px-5 pb-3 border-b border-gray-200 text-right">
                                                            Timestamp</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="attempt in blockedAttempts.slice(0, 10)"
                                                        :key="attempt.id">
                                                        <tr class="hover:bg-white transition-colors">
                                                            <td class="px-5 py-4 text-sm font-bold text-[#383838]"
                                                                x-text="attempt.user?.name"></td>
                                                            <td class="px-5 py-4">
                                                                <span class="text-xs font-black text-red-500"
                                                                    x-text="attempt.blocked_domain"></span>
                                                                <span
                                                                    class="block text-[10px] text-gray-400 font-bold mt-1 truncate max-w-[200px]"
                                                                    x-text="attempt.blocked_url"></span>
                                                            </td>
                                                            <td class="px-5 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-widest"
                                                                x-text="formatTime(attempt.attempted_at)"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>

                                            <div x-show="blockedAttempts.length === 0"
                                                class="text-center py-12 text-gray-400">
                                                <i class="ri-shield-check-line text-4xl mb-3 block text-gray-200"></i>
                                                <p class="text-[10px] font-black uppercase tracking-widest">Zero blocked
                                                    attempts</p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- JavaScript for Browser Security Tab -->
                        <script>
                            function browserSecurityManager() {
                                return {
                                    preApprovedSites: [],
                                    sessionSites: [],
                                    taskSites: [],
                                    tasks: [],
                                    blockedAttempts: [],
                                    blockedStats: {},
                                    adding: false,
                                    newSite: {
                                        domain: '',
                                        name: '',
                                        scope: 'global',
                                        task_id: '',
                                        description: '',
                                        lab_session_id: '{{ $session->id }}'
                                    },

                                    init() {
                                        this.loadSites();
                                        this.loadTasks();
                                        this.loadBlockedAttempts();
                                    },

                                    async loadSites() {
                                        try {
                                            const res = await fetch('/professor/classroom/{{ $session->id }}/allowed-sites');
                                            const data = await res.json();
                                            this.preApprovedSites = data.pre_approved;
                                            this.sessionSites = data.session_sites;
                                            this.taskSites = data.task_sites;
                                        } catch (error) {
                                            console.error('Error loading sites:', error);
                                        }
                                    },

                                    async loadTasks() {
                                        try {
                                            const res = await fetch('{{ route("professor.classroom.tasks", $session->id) }}');
                                            if (res.ok) {
                                                this.tasks = await res.json();
                                            } else {
                                                this.tasks = [];
                                            }
                                        } catch (error) {
                                            console.error('Error loading tasks:', error);
                                            this.tasks = []; // Prevent undefined errors
                                        }
                                    },

                                    async addSite() {
                                        if (!this.newSite.domain || !this.newSite.name) {
                                            alert('❌ Please fill in domain and name');
                                            return;
                                        }

                                        this.adding = true;

                                        try {
                                            const res = await fetch('/professor/allowed-sites', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                },
                                                body: JSON.stringify(this.newSite)
                                            });

                                            const data = await res.json();

                                            if (res.ok) {
                                                alert('✅ Website added successfully!');
                                                this.loadSites();

                                                // Reset form
                                                this.newSite = {
                                                    domain: '',
                                                    name: '',
                                                    scope: 'global',
                                                    task_id: '',
                                                    description: '',
                                                    lab_session_id: '{{ $session->id }}'
                                                };
                                            } else {
                                                alert('❌ ' + (data.error || 'Failed to add site'));
                                            }
                                        } catch (error) {
                                            console.error('Error adding site:', error);
                                            alert('❌ Network error');
                                        } finally {
                                            this.adding = false;
                                        }
                                    },

                                    async deleteSite(id) {
                                        if (!confirm('❌ Remove this site from the whitelist?')) return;

                                        try {
                                            const res = await fetch(`/professor/allowed-sites/${id}`, {
                                                method: 'DELETE',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                }
                                            });

                                            if (res.ok) {
                                                alert('✅ Site removed from whitelist');
                                                this.loadSites();
                                            } else {
                                                alert('❌ Failed to remove site');
                                            }
                                        } catch (error) {
                                            console.error('Error deleting site:', error);
                                        }
                                    },

                                    async loadBlockedAttempts() {
                                        try {
                                            const [attemptsRes, statsRes] = await Promise.all([
                                                fetch('/professor/classroom/{{ $session->id }}/blocked-attempts'),
                                                fetch('/professor/classroom/{{ $session->id }}/blocked-stats')
                                            ]);

                                            this.blockedAttempts = await attemptsRes.json();
                                            this.blockedStats = await statsRes.json();
                                        } catch (error) {
                                            console.error('Error loading blocked attempts:', error);
                                        }
                                    },

                                    refreshBlockedAttempts() {
                                        this.loadBlockedAttempts();
                                    },

                                    formatTime(timestamp) {
                                        return new Date(timestamp).toLocaleString();
                                    }
                                }
                            }
                        </script>

                    </div>
        </main>
    </div>

    <div x-data="{ 
        isActive: {{ $class->is_active ? 'true' : 'false' }},
        isBroadcasting: {{ $class->is_broadcasting ? 'true' : 'false' }} 
    }" class="fixed bottom-6 right-6 z-50 flex gap-3 shadow-2xl rounded-lg bg-white p-3 border border-gray-200">
        <button @click="toggleSession()"
            :class="isActive ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
            class="px-6 py-3 rounded-lg text-white font-bold transition-all shadow-md flex items-center gap-2">
            <i :class="isActive ? 'ri-stop-circle-line' : 'ri-play-circle-line'" class="text-lg"></i>
            <span x-text="isActive ? 'Stop Lab Session' : 'Start Lab Session'"></span>
        </button>

        <button type="button" x-show="isActive" @click.prevent="toggleBroadcast()"
            :class="isBroadcasting ? 'bg-orange-600 hover:bg-orange-700' : 'bg-blue-600 hover:bg-blue-700'"
            class="px-6 py-3 rounded-lg text-white font-bold shadow-md transition-all flex items-center gap-2">
            <i :class="isBroadcasting ? 'ri-broadcast-line animate-pulse' : 'ri-computer-line'" class="text-lg"></i>
            <span x-text="isBroadcasting ? 'Stop Broadcasting' : 'Share My Screen'"></span>
        </button>
    </div>

    <script>
        function toggleSession() {
            fetch("{{ route('professor.sessions.toggle', $class->id) }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json' // Tell Laravel we want JSON
                }
            })
                .then(() => {
                    // We don't even wait for the JSON parsing. 
                    // As soon as the server says 'OK', we refresh the whole page.
                    window.location.reload();
                })
                .catch(err => {
                    console.error("Session toggle failed:", err);
                    // Even if there's an error, refresh anyway to show current DB state
                    window.location.reload();
                });
        }
    </script>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>



    <script type="module">
        let receiverPeer = null;   // Phone 1: For receiving student screens
        let broadcastPeer = null;  // Phone 2: For sending professor screen
        let profLocalStream = null;
        let activeBroadcastCalls = [];
        const enrolledStudentIds = @json($class->students->pluck('id'));

        window.addEventListener('beforeunload', () => {
            if (receiverPeer) { receiverPeer.disconnect(); receiverPeer.destroy(); }
            if (broadcastPeer) { broadcastPeer.disconnect(); broadcastPeer.destroy(); }
        });

        // 🟢 PHONE 1: The Receiver (Listens for students on PROF_ID)
        function initReceiverPeer() {
            if (receiverPeer) { receiverPeer.destroy(); receiverPeer = null; }

            const profId = 'PROF_{{ auth()->user()->id }}';
            receiverPeer = new Peer(profId);

            receiverPeer.on('open', (id) => console.log("✅ Receiver Peer Ready:", id));

            receiverPeer.on('call', (call) => {
                const studentId = call.peer.replace('STUDENT_', '');
                console.log("📞 Incoming student monitor from:", studentId);
                call.answer();
                call.on('stream', (remoteStream) => {
                    console.log("🟢 Stream received for student", studentId);

                    const studentName = call.metadata?.studentName || 'Student ' + studentId;

                    // Function to attach stream
                    const attachStream = () => {
                        if (window.liveMonitorInstance) {
                            window.liveMonitorInstance.addStream(studentId, studentName, call, remoteStream);
                        } else {
                            console.warn('liveMonitorInstance not ready, retrying...');
                            setTimeout(attachStream, 200);
                        }
                    };
                    attachStream();
                });
                call.on('close', () => {
                    console.log("🔴 Stream closed for student", studentId);
                    if (window.liveMonitorInstance) {
                        window.liveMonitorInstance.removeStream(studentId);
                    }
                    // Clean up video element
                    const video = document.getElementById('video-' + studentId);
                    if (video) {
                        video.srcObject = null;
                    }
                });
            });

            receiverPeer.on('error', (err) => {
                if (err.type === 'unavailable-id') {
                    console.warn("⚠️ Receiver ID ghosted by public server. Existing incoming screens will still work!");
                }
            });
        }

        // 🟢 PHONE 2: The Broadcaster (Uses a random ID so it NEVER crashes!)
        function initBroadcastPeer() {
            if (broadcastPeer) { broadcastPeer.destroy(); broadcastPeer = null; }

            // Passing empty parameters generates a guaranteed unique random ID
            broadcastPeer = new Peer();

            broadcastPeer.on('open', (id) => console.log("✅ Broadcast Peer Ready (Random ID):", id));

            broadcastPeer.on('disconnected', () => {
                if (broadcastPeer && !broadcastPeer.destroyed) broadcastPeer.reconnect();
            });

            broadcastPeer.on('error', (err) => console.error("Broadcast Peer Error:", err));
        }

        function resetStudentUI(studentId) {
            const video = document.getElementById('video-' + studentId);
            const overlay = document.getElementById('video-overlay-' + studentId);
            const btn = document.querySelector(`#btn-container-${studentId} button`);

            if (video) { video.srcObject = null; video.classList.add('hidden'); }
            if (overlay) overlay.classList.remove('hidden');
            if (btn) {
                btn.innerText = "Waiting...";
                btn.classList.remove('bg-[#383838]', 'text-white', 'hover:bg-black');
                btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                btn.disabled = true;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            initReceiverPeer();
            initBroadcastPeer();
        });

        // 🟢 BROADCASTING NOW USES PHONE 2
        window.toggleBroadcast = async function () {
            const wrapper = document.getElementById('broadcast-wrapper');
            if (!wrapper) return;
            const alpine = Alpine.$data(wrapper);

            if (!alpine.isBroadcasting) {

                // Ensure the Broadcaster is alive
                if (!broadcastPeer || broadcastPeer.destroyed) {
                    initBroadcastPeer();
                    await new Promise(resolve => setTimeout(resolve, 1500));
                } else if (broadcastPeer.disconnected) {
                    broadcastPeer.reconnect();
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }

                try {
                    profLocalStream = await navigator.mediaDevices.getDisplayMedia({
                        video: { cursor: "always" }, audio: false
                    });

                    activeBroadcastCalls = [];
                    enrolledStudentIds.forEach(studentId => {
                        const targetPeerId = 'STUDENT_' + studentId;
                        console.log("📡 Broadcasting to:", targetPeerId);

                        const call = broadcastPeer.call(targetPeerId, profLocalStream);
                        if (call) {
                            activeBroadcastCalls.push(call);
                            call.on('error', err => console.error("Broadcast call error:", err));
                        }
                    });

                    profLocalStream.getVideoTracks()[0].onended = function () {
                        if (alpine.isBroadcasting) window.toggleBroadcast();
                    };

                } catch (err) {
                    console.error("Screen capture failed:", err);
                    return;
                }
            } else {
                if (profLocalStream) profLocalStream.getTracks().forEach(track => track.stop());
                activeBroadcastCalls.forEach(call => call.close());
                activeBroadcastCalls = [];
            }

            fetch("{{ route('professor.sessions.broadcast', $class->id) }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' }
            })
                .then(res => res.json())
                .then(data => {
                    document.querySelectorAll('[x-data]').forEach(el => {
                        const d = Alpine.$data(el);
                        if (d.isBroadcasting !== undefined) d.isBroadcasting = data.is_broadcasting;
                    });
                });
        }
    </script>

    <script>
        // 🟢 1. Silent Refresh with Alpine.js support
        async function silentRefresh() {
            try {
                const response = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Helper to update and re-initialize Alpine components
                const updateContainer = (id) => {
                    const newContent = doc.getElementById(id);
                    const target = document.getElementById(id);
                    if (newContent && target) {
                        target.innerHTML = newContent.innerHTML;
                        // 🟢 CRITICAL: This makes the new buttons clickable again!
                        Alpine.initTree(target);
                    }
                };

                updateContainer('tasks-list-container');
                updateContainer('quizzes-list-container');
                updateContainer('materials-list-container'); // Add this if needed

            } catch (err) {
                console.error("Silent refresh failed:", err);
            }
        }

        // Polling (Every 3 seconds)
        setInterval(silentRefresh, 3000);

        // 🟢 2. Corrected submitAjaxForm
        function submitAjaxForm(event, closeAlpineModal) {
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';
            btn.disabled = true;

            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(async res => {
                    if (res.ok) {
                        closeAlpineModal();
                        form.reset();
                        await silentRefresh(); // Update UI immediately after success
                    } else {
                        const data = await res.json();
                        alert(data.message || "Something went wrong.");
                    }
                })
                .catch(err => console.error("Upload failed:", err))
                .finally(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }

        // 🟢 4. The function that grades submissions silently
        function submitGrade(event) {
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalContent = btn.innerHTML; // Saves the floppy disk icon
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Show loading state
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i>';
            btn.disabled = true;

            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(async res => {
                    if (res.ok) {
                        // Success! Show a green checkmark briefly
                        btn.innerHTML = '<i class="ri-check-double-line"></i>';
                        btn.classList.remove('bg-[#383838]', 'hover:bg-black');
                        btn.classList.add('bg-green-500');

                        // Revert back to normal after 1.5 seconds
                        setTimeout(() => {
                            btn.innerHTML = originalContent;
                            btn.classList.remove('bg-green-500');
                            btn.classList.add('bg-[#383838]', 'hover:bg-black');
                            btn.disabled = false;
                        }, 1500);

                    } else {
                        const data = await res.json();
                        alert(data.message || "Failed to save grade.");
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error("Grading failed:", err);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
        }
    </script>



</x-app-layout>