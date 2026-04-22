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


                <div x-data="{ activeTab: 'monitoring' }" class="mt-8">
                    <div class="flex space-x-1 border-b border-gray-200">
                        <button @click="activeTab = 'monitoring'"
                            :class="activeTab === 'monitoring' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Monitoring</button>
                        <button @click="activeTab = 'tasks'"
                            :class="activeTab === 'tasks' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Tasks</button>
                        <button @click="activeTab = 'students'"
                            :class="activeTab === 'students' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
                            class="px-6 py-3 transition">Students</button>
                        <button @click="activeTab = 'browser-security'"
                            :class="activeTab === 'browser-security' ? 'border-b-2 border-black text-black' : 'text-gray-500'"
                            class="px-6 py-3 transition">Settings</button>
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
                                    <h2 class="font-bold mb-4">Live Laboratory Monitor</h2>
                                    @include('professor.partials.monitor-grid', ['activeStudents' => $activeStudents])
                                </div>
                            @else
                                <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed">
                                    <p class="text-gray-500 font-bold">Session is currently offline. Click "Start Session"
                                        to allow students to join.</p>
                                </div>
                            @endif
                        </div>

                        <div x-show="activeTab === 'tasks'"
                            x-data="{ showModal: false, selectedTask: null, selectedQuiz: null, submissions: [], scores: [] }"
                            class="bg-white p-6 rounded-xl shadow-sm border border-gray-200" x-cloak>

                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                                <div>
                                    <h2 class="font-black text-2xl text-gray-900 tracking-tight text-uppercase">Academic
                                        Management</h2>
                                    <p
                                        class="text-sm text-gray-500 mt-1 uppercase tracking-widest text-[10px] font-bold">
                                        Control laboratory activities and student examinations.</p>
                                </div>

                                <div class="flex space-x-3 w-full md:w-auto">
                                    <button @click="showModal = true"
                                        class="flex-1 md:flex-none bg-[#383838] hover:bg-black text-white px-6 py-2.5 rounded-xl font-bold transition-all active:scale-95 shadow-lg shadow-gray-200 uppercase text-xs">
                                        + Create Task
                                    </button>
                                    <a href="{{ route('professor.quizzes.create', ['session_id' => $session->id]) }}"
                                        target="_blank"
                                        class="flex-1 md:flex-none bg-white border-2 border-[#383838] text-[#383838] hover:bg-gray-50 px-6 py-2.5 rounded-xl font-bold transition-all active:scale-95 inline-flex items-center justify-center uppercase text-xs">
                                        + Create Quiz
                                    </a>
                                    <button @click="$dispatch('open-material-modal')"
                                        class="bg-[#383838] text-white px-6 py-3 rounded-xl font-bold uppercase tracking-wider hover:bg-black transition-all shadow-md flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Post Material
                                    </button>
                                </div>
                            </div>

                            <div class="mb-12">
                                <h3
                                    class="font-black text-gray-400 mb-5 flex items-center uppercase tracking-widest text-[10px]">
                                    <i class="fas fa-flask me-2"></i> Lab Tasks
                                </h3>
                                <div id="tasks-list-container" class="grid grid-cols-1 gap-4">
                                    @forelse($tasks as $task)
                                        <div
                                            class="p-5 border border-gray-200 rounded-2xl flex justify-between items-center bg-white hover:border-gray-900 transition-all group">
                                            <div>
                                                <h4 class="font-bold text-lg text-gray-900">{{ $task->title }}</h4>
                                                <div class="flex items-center gap-4 mt-1">
                                                    <span class="text-xs text-gray-400 font-medium">Deadline:
                                                        {{ \Carbon\Carbon::parse($task->deadline)->format('M d, h:i A') }}</span>
                                                    <span
                                                        class="text-[10px] font-black text-white bg-gray-900 px-2 py-0.5 rounded-lg uppercase tracking-tighter">{{ $task->submissions->count() }}
                                                        Submissions</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="font-black text-gray-900 text-sm">{{ $task->points }}
                                                    PTS</span>
                                                <button
                                                    @click="selectedTask = {{ json_encode($task) }}; submissions = {{ json_encode($task->submissions()->with('user')->get()) }}"
                                                    class="bg-white text-[#383838] border-2 border-[#383838] px-6 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest">
                                                    View Submissions
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div
                                            class="py-10 border-2 border-dashed border-gray-100 rounded-2xl text-center text-gray-400 italic text-sm">
                                            No tasks assigned.</div>
                                    @endforelse
                                </div>
                            </div>

                            <div>
                                <h3
                                    class="font-black text-gray-400 mb-5 flex items-center uppercase tracking-widest text-[10px]">
                                    <i class="fas fa-stopwatch me-2"></i> Active Quizzes
                                </h3>
                                <div id="quizzes-list-container" class="grid grid-cols-1 gap-4">
                                    @forelse($session->quizzes ?? [] as $quiz)
                                        <div
                                            class="p-5 border border-gray-200 rounded-2xl flex justify-between items-center bg-white hover:border-gray-900 transition-all">
                                            <div>
                                                <h4 class="font-bold text-lg text-gray-900">{{ $quiz->title }}</h4>
                                                <div class="flex items-center gap-4 mt-1">
                                                    <span class="text-xs text-gray-400 font-medium">Deadline:
                                                        {{ \Carbon\Carbon::parse($quiz->deadline)->format('M d, h:i A') }}</span>
                                                    <span
                                                        class="text-[10px] font-black text-white bg-gray-900 px-2 py-0.5 rounded-lg uppercase tracking-tighter">
                                                        {{ $quiz->attempts->count() }} Answered
                                                    </span>
                                                </div>
                                                <div class="flex gap-3 mt-1">
                                                    <span
                                                        class="text-[10px] text-gray-400 border-l pl-3 uppercase tracking-tighter">{{ $quiz->time_limit }}
                                                        Mins</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="font-black text-gray-900 text-sm">{{ $quiz->questions->count() }}
                                                    PTS</span>
                                                <button
                                                    @click="selectedQuiz = {{ json_encode($quiz) }}; scores = {{ json_encode($quiz->attempts()->with('user')->get()) }}"
                                                    class="bg-white text-[#383838] border-2 border-[#383838] px-6 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest">
                                                    View Results
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div
                                            class="py-10 border-2 border-dashed border-gray-100 rounded-2xl text-center text-gray-400 italic text-sm">
                                            No quizzes available.</div>
                                    @endforelse
                                </div>
                            </div>

                            <template x-if="selectedTask">
                                <div
                                    class="fixed inset-0 z-[100] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4">
                                    <div
                                        class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                                        <div
                                            class="p-6 border-b flex justify-between items-center bg-[#383838] text-white">
                                            <div>
                                                <h3 class="font-black text-xl uppercase tracking-tight"
                                                    x-text="selectedTask.title"></h3>
                                                <p class="text-[10px] uppercase tracking-widest opacity-60">Task
                                                    Submission Review & Grading</p>
                                            </div>
                                            <button @click="selectedTask = null"
                                                class="text-white hover:text-gray-300 text-3xl font-light">&times;</button>
                                        </div>
                                        <div class="overflow-y-auto p-8 bg-gray-50/50">
                                            <table class="w-full text-left border-separate border-spacing-y-3">
                                                <thead>
                                                    <tr
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        <th class="px-6 pb-2">Student Name</th>
                                                        <th class="px-6 pb-2">Attachment</th>
                                                        <th class="px-6 pb-2">Grade & Feedback</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="sub in submissions" :key="sub.id">
                                                        <tr class="bg-white shadow-sm rounded-2xl overflow-hidden">
                                                            <td class="px-6 py-6 font-bold text-gray-900 rounded-s-2xl border-y border-l border-gray-100"
                                                                x-text="sub.user ? `${sub.user.last_name}, ${sub.user.first_name} ${sub.user.middle_name ? sub.user.middle_name.charAt(0) + '.' : ''}` : 'N/A'">
                                                            </td>
                                                            <td class="px-6 py-6 border-y border-gray-100">
                                                                <a :href="'{{ url('/') }}/' + sub.file_path"
                                                                    target="_blank"
                                                                    class="inline-flex items-center text-[10px] font-black text-[#383838] bg-gray-100 px-3 py-2 rounded-lg hover:bg-black hover:text-white transition-all uppercase tracking-widest">
                                                                    <i class="fas fa-file-download me-2"></i> View File
                                                                </a>
                                                            </td>
                                                            <td
                                                                class="px-6 py-6 rounded-e-2xl border-y border-r border-gray-100">
                                                                <form :action="'/professor/grade/' + sub.id"
                                                                    method="POST" class="flex flex-col gap-2">
                                                                    @csrf
                                                                    <div class="flex items-center gap-2">
                                                                        <input type="number" name="grade"
                                                                            :value="sub.grade"
                                                                            class="w-24 border-gray-200 rounded-xl text-sm font-black text-center focus:ring-[#383838]"
                                                                            placeholder="Score">
                                                                        <span
                                                                            class="text-[10px] font-black text-gray-400"
                                                                            x-text="'/ ' + selectedTask.points + ' PTS'"></span>
                                                                    </div>
                                                                    <div class="flex gap-2">
                                                                        <textarea name="feedback" :value="sub.feedback"
                                                                            class="flex-1 border-gray-200 rounded-xl text-xs p-3 focus:ring-[#383838]"
                                                                            placeholder="Enter student feedback..."></textarea>
                                                                        <button type="submit"
                                                                            class="bg-[#383838] text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-black transition self-end">Save</button>
                                                                    </div>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="selectedQuiz">
                                <div
                                    class="fixed inset-0 z-[100] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4">
                                    <div
                                        class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                                        <div
                                            class="p-6 border-b flex justify-between items-center bg-[#383838] text-white">
                                            <div>
                                                <h3 class="font-black text-xl uppercase tracking-tight"
                                                    x-text="selectedQuiz.title"></h3>
                                                <p class="text-[10px] uppercase tracking-widest opacity-60">Quiz
                                                    Performance & Score Overview</p>
                                            </div>
                                            <button @click="selectedQuiz = null"
                                                class="text-white hover:text-gray-300 text-3xl font-light">&times;</button>
                                        </div>
                                        <div class="overflow-y-auto p-8 bg-gray-50/50">
                                            <table class="w-full text-left border-separate border-spacing-y-3">
                                                <thead>
                                                    <tr
                                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        <th class="px-6 pb-2">Student Name</th>
                                                        <th class="px-6 pb-2">Time Taken</th>
                                                        <th class="px-6 pb-2">Final Score</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="attempt in scores" :key="attempt.id">
                                                        <tr class="bg-white shadow-sm rounded-2xl overflow-hidden">
                                                            <td class="px-6 py-6 font-bold text-gray-900 rounded-s-2xl border-y border-l border-gray-100"
                                                                x-text="attempt.user ? `${attempt.user.last_name}, ${attempt.user.first_name} ${attempt.user.middle_name ? attempt.user.middle_name.charAt(0) + '.' : ''}` : 'N/A'">
                                                            </td>
                                                            <td class="px-6 py-6 border-y border-gray-100">
                                                                <span class="text-xs font-bold text-gray-500"
                                                                    x-text="Math.floor(attempt.time_spent / 60) + 'm ' + (attempt.time_spent % 60) + 's'"></span>
                                                            </td>
                                                            <td
                                                                class="px-6 py-6 rounded-e-2xl border-y border-r border-gray-100">
                                                                <div class="flex items-center gap-4">
                                                                    <div
                                                                        class="px-4 py-2 bg-gray-900 text-white rounded-xl">
                                                                        <span class="text-sm font-black"
                                                                            x-text="attempt.score + ' / ' + attempt.total_questions"></span>
                                                                    </div>
                                                                    <div
                                                                        class="px-4 py-2 border-2 border-gray-100 rounded-xl">
                                                                        <span class="text-xs font-black text-[#383838]"
                                                                            x-text="Math.round((attempt.score / attempt.total_questions) * 100) + '%'"></span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                            <template x-if="scores.length === 0">
                                                <div class="text-center py-20">
                                                    <p class="text-gray-400 italic text-sm">No students have completed
                                                        this quiz yet.</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="showModal"
                                class="fixed inset-0 z-[110] flex items-center justify-center bg-[#383838]/80 backdrop-blur-md p-4">
                                <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md border border-gray-200">
                                    <h3 class="font-black text-2xl text-gray-900 mb-6">New Lab Activity</h3>
                                    <form action="{{ route('professor.tasks.store') }}" method="POST" class="space-y-4"
                                        @submit.prevent="submitAjaxForm($event, () => showModal = false)">
                                        @csrf
                                        <input type="hidden" name="subject_id" value="{{ $session->id }}">
                                        <div>
                                            <label
                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Activity
                                                Title</label>
                                            <input type="text" name="title"
                                                class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none"
                                                required>
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Instructions</label>
                                            <textarea name="description"
                                                class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none"
                                                rows="3"></textarea>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Max
                                                    Pts</label>
                                                <input type="number" name="points"
                                                    class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none"
                                                    required>
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Deadline</label>
                                                <input type="datetime-local" name="deadline"
                                                    class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="flex gap-3 pt-4">
                                            <button type="button" @click="showModal = false"
                                                class="flex-1 py-3 bg-gray-100 text-gray-500 rounded-xl font-bold uppercase text-xs">Cancel</button>
                                            <button type="submit"
                                                class="flex-1 py-3 bg-[#383838] text-white rounded-xl font-bold uppercase text-xs hover:bg-black transition shadow-lg shadow-gray-200">Save
                                                Task</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-data="{ open: false, type: 'pdf' }" @open-material-modal.window="open = true" x-show="open"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                        x-cloak>

                        <div @click.away="open = false"
                            class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                <h3 class="text-xl font-black text-gray-900 uppercase">Upload Learning Content</h3>
                                <button @click="open = false"
                                    class="text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <form action="{{ route('professor.materials.store', $session->id) }}" method="POST"
                                enctype="multipart/form-data" class="p-6 space-y-5"
                                @submit.prevent="submitAjaxForm($event, () => open = false)">
                                @csrf

                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5 ml-1">Material
                                        Title</label>
                                    <input type="text" name="title" required
                                        placeholder="e.g. Lesson 1: Variable Scoping"
                                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#383838] focus:border-transparent outline-none transition-all">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5 ml-1">Type
                                        of Content</label>
                                    <select name="type" x-model="type"
                                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none cursor-pointer focus:ring-2 focus:ring-[#383838]">
                                        <option value="pdf">PDF Document</option>
                                        <option value="pptx">PowerPoint (.pptx)</option>
                                        <option value="youtube">YouTube Video URL</option>
                                    </select>
                                </div>

                                <div class="p-4 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">

                                    <template x-if="type === 'youtube'">
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Paste
                                                YouTube Link</label>
                                            <input type="url" name="content_url" required
                                                placeholder="https://www.youtube.com/watch?v=..."
                                                class="w-full px-4 py-3 bg-white border border-gray-200 rounded-lg outline-none">
                                        </div>
                                    </template>

                                    <template x-if="type === 'pdf' || type === 'pptx'">
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Select
                                                File</label>
                                            <input type="file" name="content_file" required
                                                :accept="type === 'pdf' ? '.pdf' : '.ppt,.pptx'"
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#383838] file:text-white hover:file:bg-black transition-all">
                                        </div>
                                    </template>
                                </div>

                                <button type="submit"
                                    class="w-full bg-[#383838] text-white py-4 rounded-xl font-bold hover:bg-black transition-all shadow-lg uppercase">
                                    Publish Material
                                </button>
                            </form>
                        </div>
                    </div>

                    <div x-show="activeTab === 'students'" x-cloak
                        class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200 animate-fade-in">
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="font-black text-2xl text-gray-900 tracking-tight text-uppercase">Enrolled
                                    Students</h2>
                                <p class="text-sm text-gray-500 mt-1 uppercase tracking-widest text-[10px] font-bold">
                                    Monitor student presence and connection status</p>
                            </div>
                            <span
                                class="px-4 py-2 bg-gray-100 text-[#383838] font-black rounded-xl text-xs uppercase tracking-widest border border-gray-200">
                                Total Enrolled: {{ $class->students->count() ?? 0 }}
                            </span>
                        </div>

                        <div class="overflow-hidden border border-gray-200 rounded-xl">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th
                                            class="py-4 px-6 font-black text-gray-400 uppercase text-[10px] tracking-widest">
                                            Student Name</th>
                                        <th
                                            class="py-4 px-6 font-black text-gray-400 uppercase text-[10px] tracking-widest text-center">
                                            Live Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($class->students as $student)
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="py-4 px-6 flex items-center gap-4">
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-bold text-gray-900 leading-none">
                                                        {{-- Formal Format: LAST NAME, FIRST NAME M. --}}
                                                        {{ strtoupper($student->last_name) }},
                                                        {{ $student->first_name }}
                                                        @if($student->middle_name)
                                                            {{ strtoupper(substr($student->middle_name, 0, 1)) }}.
                                                        @endif
                                                    </span>
                                                    <span
                                                        class="text-[10px] text-gray-400 mt-1">{{ $student->school_id }}</span>
                                                </div>
                                            </td>

                                            <td class="py-4 px-6 text-center">
                                                @if($student->pivot->is_present)
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 uppercase tracking-widest shadow-sm">
                                                        <span
                                                            class="w-1.5 h-1.5 mr-2 bg-green-500 rounded-full animate-pulse"></span>
                                                        Active
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-bold bg-gray-50 text-gray-500 border border-gray-200 uppercase tracking-widest">
                                                        Offline
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-12 text-center text-gray-400 italic text-sm">
                                                No students enrolled in this session.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- BROWSER SECURITY TAB -->
                    <div x-show="activeTab === 'browser-security'" x-data="browserSecurityManager()">
                        <!-- Header -->
                        <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
                            <h2 class="text-2xl font-black text-gray-900 mb-2">🔒 Browser Security Settings</h2>
                            <p class="text-sm text-gray-500">Control which websites students can access during lab
                                sessions</p>
                        </div>

                        <!-- Add New Site Form -->
                        <div
                            class="bg-gradient-to-r from-green-50 to-blue-50 rounded-2xl border-2 border-green-200 p-6 mb-6">
                            <h3 class="text-lg font-black text-gray-900 mb-4">➕ Add Allowed Website</h3>

                            <form @submit.prevent="addSite()" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Domain *</label>
                                        <input type="text" x-model="newSite.domain"
                                            placeholder="e.g., youtube.com or github.com"
                                            class="w-full border-gray-300 rounded-lg text-sm" required>
                                        <p class="text-xs text-gray-500 mt-1">Don't include http:// or www.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Name *</label>
                                        <input type="text" x-model="newSite.name" placeholder="e.g., YouTube or GitHub"
                                            class="w-full border-gray-300 rounded-lg text-sm" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Scope</label>
                                        <select x-model="newSite.scope"
                                            class="w-full border-gray-300 rounded-lg text-sm">
                                            <option value="global">✅ Global (All tasks in this class)</option>
                                            <option value="task">📋 Specific Task Only</option>
                                        </select>
                                    </div>

                                    <div x-show="newSite.scope === 'task'">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Select Task</label>
                                        <select x-model="newSite.task_id"
                                            class="w-full border-gray-300 rounded-lg text-sm">
                                            <option value="">Choose a task...</option>
                                            <template x-for="task in tasks" :key="task.id">
                                                <option :value="task.id" x-text="task.title"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Description
                                        (Optional)</label>
                                    <input type="text" x-model="newSite.description"
                                        placeholder="Why students need this site"
                                        class="w-full border-gray-300 rounded-lg text-sm">
                                </div>

                                <button type="submit" :disabled="adding"
                                    :class="adding ? 'bg-gray-400' : 'bg-green-600 hover:bg-green-700'"
                                    class="px-6 py-3 text-white font-black rounded-xl text-sm transition">
                                    <span x-show="!adding">✅ Add Website to Whitelist</span>
                                    <span x-show="adding">Adding...</span>
                                </button>
                            </form>
                        </div>

                        <!-- Pre-Approved Educational Sites -->
                        <div
                            class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-2xl border-2 border-blue-200 p-6 mb-6">
                            <h3 class="text-lg font-black text-gray-900 mb-3">
                                <i class="ri-shield-check-line text-blue-600 mr-2"></i>
                                ✨ Pre-Approved Educational Sites (Always Allowed)
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">These sites are automatically allowed and cannot be
                                removed</p>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <template x-for="site in preApprovedSites" :key="site.id">
                                    <div
                                        class="bg-white border border-blue-200 rounded-lg p-3 text-center hover:shadow-md transition">
                                        <p class="text-xs font-black text-gray-900" x-text="site.name"></p>
                                        <p class="text-[10px] text-gray-500" x-text="site.domain"></p>
                                    </div>
                                </template>
                            </div>

                            <div x-show="preApprovedSites.length === 0" class="text-center py-4 text-gray-400">
                                <p class="text-sm">Loading pre-approved sites...</p>
                            </div>
                        </div>

                        <!-- Your Custom Allowed Sites -->
                        <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
                            <h3 class="text-lg font-black text-gray-900 mb-4">📝 Your Custom Allowed Sites</h3>

                            <div x-show="sessionSites.length === 0" class="text-center py-10 text-gray-400">
                                <i class="ri-global-line text-5xl mb-3"></i>
                                <p class="font-bold">No custom sites added yet</p>
                                <p class="text-sm">Use the form above to add websites</p>
                            </div>

                            <div class="space-y-3">
                                <template x-for="site in sessionSites" :key="site.id">
                                    <div
                                        class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:border-green-500 transition">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900" x-text="site.name"></h4>
                                            <p class="text-xs text-gray-500" x-text="site.domain"></p>
                                            <p x-show="site.description" class="text-xs text-gray-400 mt-1"
                                                x-text="site.description"></p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="text-[10px] font-black bg-green-100 text-green-700 px-3 py-1 rounded-full uppercase">
                                                ✅ ALLOWED
                                            </span>
                                            <button @click="deleteSite(site.id)"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                                <i class="ri-delete-bin-line text-xl"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Task-Specific Sites -->
                        <div x-show="taskSites.length > 0" class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
                            <h3 class="text-lg font-black text-gray-900 mb-4">📋 Task-Specific Sites</h3>

                            <div class="space-y-3">
                                <template x-for="site in taskSites" :key="site.id">
                                    <div
                                        class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:border-purple-500 transition">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900" x-text="site.name"></h4>
                                            <p class="text-xs text-gray-500" x-text="site.domain"></p>
                                            <p class="text-xs text-purple-600 font-bold mt-1">
                                                <i class="ri-task-line mr-1"></i>
                                                <span x-text="site.task?.title || 'Unknown Task'"></span>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="text-[10px] font-black bg-purple-100 text-purple-700 px-3 py-1 rounded-full uppercase">
                                                TASK ONLY
                                            </span>
                                            <button @click="deleteSite(site.id)"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                                <i class="ri-delete-bin-line text-xl"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Blocked Attempts Monitoring -->
                        <div class="bg-white rounded-2xl border-2 border-red-200 p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-black text-gray-900">🚫 Blocked Attempts Log</h3>
                                <button @click="refreshBlockedAttempts()"
                                    class="text-sm text-gray-600 hover:text-black font-bold">
                                    <i class="ri-refresh-line mr-1"></i>Refresh
                                </button>
                            </div>

                            <!-- Stats Cards -->
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4 text-center">
                                    <p class="text-3xl font-black text-red-600" x-text="blockedStats.total || 0"></p>
                                    <p class="text-xs text-red-700 font-bold mt-1">Total Blocked Attempts</p>
                                </div>
                                <div class="bg-orange-50 border-2 border-orange-200 rounded-xl p-4 text-center">
                                    <p class="text-3xl font-black text-orange-600"
                                        x-text="blockedStats.by_domain?.length || 0"></p>
                                    <p class="text-xs text-orange-700 font-bold mt-1">Different Sites Blocked</p>
                                </div>
                                <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-4 text-center">
                                    <p class="text-3xl font-black text-yellow-600"
                                        x-text="blockedStats.by_student?.length || 0"></p>
                                    <p class="text-xs text-yellow-700 font-bold mt-1">Students Affected</p>
                                </div>
                            </div>

                            <!-- Recent Attempts Table -->
                            <div class="overflow-x-auto bg-gray-50 rounded-xl p-4">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                                        <tr>
                                            <th class="px-4 py-3 text-xs font-black text-gray-700 uppercase">Student
                                            </th>
                                            <th class="px-4 py-3 text-xs font-black text-gray-700 uppercase">Blocked
                                                Website</th>
                                            <th class="px-4 py-3 text-xs font-black text-gray-700 uppercase">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="attempt in blockedAttempts.slice(0, 10)" :key="attempt.id">
                                            <tr class="border-b border-gray-200 hover:bg-white">
                                                <td class="px-4 py-3 font-bold text-gray-900"
                                                    x-text="attempt.user?.name"></td>
                                                <td class="px-4 py-3">
                                                    <p class="text-red-600 font-bold" x-text="attempt.blocked_domain">
                                                    </p>
                                                    <p class="text-xs text-gray-400 truncate max-w-xs"
                                                        x-text="attempt.blocked_url"></p>
                                                </td>
                                                <td class="px-4 py-3 text-gray-500 text-xs"
                                                    x-text="formatTime(attempt.attempted_at)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>

                                <div x-show="blockedAttempts.length === 0" class="text-center py-10 text-gray-400">
                                    <i class="ri-shield-check-line text-5xl mb-3"></i>
                                    <p class="font-bold">No blocked attempts yet</p>
                                    <p class="text-sm">Students are browsing safely!</p>
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
                                        const res = await fetch('/student/classroom/{{ $session->id }}/live-tasks');
                                        this.tasks = await res.json();
                                    } catch (error) {
                                        console.error('Error loading tasks:', error);
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
                    const video = document.getElementById('video-' + studentId);
                    const overlay = document.getElementById('video-overlay-' + studentId);
                    const btn = document.querySelector(`#btn-container-${studentId} button`);

                    if (video) {
                        video.srcObject = remoteStream;
                        video.classList.remove('hidden');
                        video.play().catch(e => console.log("Play error:", e));
                    }
                    if (overlay) overlay.classList.add('hidden');
                    if (btn) {
                        btn.innerText = "View Screen";
                        btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                        btn.classList.add('bg-[#383838]', 'text-white', 'hover:bg-black');
                        btn.disabled = false;
                    }
                });
                call.on('close', () => resetStudentUI(studentId));
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
        // 🟢 1. The function that invisibly grabs the newest tasks and updates the screen
        async function silentRefresh() {
            try {
                const response = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Instantly swap the Tasks list
                const newTasks = doc.getElementById('tasks-list-container');
                if (newTasks && document.getElementById('tasks-list-container')) {
                    document.getElementById('tasks-list-container').innerHTML = newTasks.innerHTML;
                }

                // Instantly swap the Quizzes list
                const newQuizzes = doc.getElementById('quizzes-list-container');
                if (newQuizzes && document.getElementById('quizzes-list-container')) {
                    document.getElementById('quizzes-list-container').innerHTML = newQuizzes.innerHTML;
                }
            } catch (err) {
                console.error("Silent refresh failed:", err);
            }
        }

        // 🟢 2. Auto-poll every 3 seconds! 
        // If you create a Quiz in a new tab, this pulls it into the dashboard automatically.
        setInterval(silentRefresh, 3000);

        // 🟢 3. The function that uploads your forms in the background without refreshing
        function submitAjaxForm(event, closeAlpineModal) {
            const form = event.target;
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            // Show a loading state on the button
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';
            btn.disabled = true;

            fetch(form.action, {
                method: form.method,
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' } // Lets Laravel know it's AJAX
            })
                .then(res => {
                    if (res.ok) {
                        closeAlpineModal(); // Closes the popup immediately
                        form.reset();       // Clears your typed inputs
                        silentRefresh();    // Visually pops the new item into the list instantly!
                    } else {
                        alert("Something went wrong. Please check your inputs and try again.");
                    }
                })
                .catch(err => console.error("Upload failed:", err))
                .finally(() => {
                    // Restore the button
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
        }
    </script>
</x-app-layout>