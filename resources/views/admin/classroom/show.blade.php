<x-app-layout>
    <div class="fixed inset-0 flex bg-gray-100" x-data="{ showModal: false }">

        <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0">


            <nav class="mt-8 px-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-3 px-4 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>
                <a href="{{ route('admin.classroom') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.classroom') ? 'text-gray-600 hover:bg-gray-100' : 'bg-[#383838] text-white font-bold' }} transition">
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
                        <button @click="activeTab = 'settings'"
                            :class="activeTab === 'settings' ? 'border-b-2 border-black font-bold' : 'text-gray-500'"
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

                                <form action="{{ route('admin.sessions.toggle', $session->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="px-6 py-2 rounded-lg font-bold text-white transition {{ $session->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}">
                                        {{ $session->is_active ? 'Stop Session' : 'Start Session' }}
                                    </button>
                                </form>
                            </div>

                            @if($session->is_active)
                                <div class="bg-white p-6 rounded-xl shadow border border-gray-100">
                                    <h2 class="font-bold mb-4">Live Laboratory Monitor</h2>
                                    @include('admin.partials.monitor-grid', ['activeStudents' => $activeStudents])
                                </div>
                            @else
                                <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed">
                                    <p class="text-gray-500 font-bold">Session is currently offline. Click "Start Session"
                                        to allow students to join.</p>
                                </div>
                            @endif
                        </div>

                    <div x-show="activeTab === 'tasks'" x-data="{ showModal: false, selectedTask: null, selectedQuiz: null, submissions: [], scores: [] }" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200" x-cloak>
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
        <div>
            <h2 class="font-black text-2xl text-gray-900 tracking-tight text-uppercase">Academic Management</h2>
            <p class="text-sm text-gray-500 mt-1 uppercase tracking-widest text-[10px] font-bold">Control laboratory activities and student examinations.</p>
        </div>
        
        <div class="flex space-x-3 w-full md:w-auto"> 
            <button @click="showModal = true" class="flex-1 md:flex-none bg-[#383838] hover:bg-black text-white px-6 py-2.5 rounded-xl font-bold transition-all active:scale-95 shadow-lg shadow-gray-200 uppercase text-xs">
                + Create Task
            </button>
            <a href="{{ route('admin.quizzes.create', ['session_id' => $session->id]) }}"
                target="_blank" class="flex-1 md:flex-none bg-white border-2 border-[#383838] text-[#383838] hover:bg-gray-50 px-6 py-2.5 rounded-xl font-bold transition-all active:scale-95 inline-flex items-center justify-center uppercase text-xs">
                + Create Quiz
            </a>
            <button @click="$dispatch('open-material-modal')" 
        class="bg-[#383838] text-white px-6 py-3 rounded-xl font-bold uppercase tracking-wider hover:bg-black transition-all shadow-md flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>
    Post Material
</button>
        </div>
    </div>

    <div class="mb-12">
        <h3 class="font-black text-gray-400 mb-5 flex items-center uppercase tracking-widest text-[10px]">
            <i class="fas fa-flask me-2"></i> Lab Tasks
        </h3>
        <div class="grid grid-cols-1 gap-4">
            @forelse($tasks as $task)
                <div class="p-5 border border-gray-200 rounded-2xl flex justify-between items-center bg-white hover:border-gray-900 transition-all group">
                    <div>
                        <h4 class="font-bold text-lg text-gray-900">{{ $task->title }}</h4>
                        <div class="flex items-center gap-4 mt-1">
                            <span class="text-xs text-gray-400 font-medium">Deadline: {{ \Carbon\Carbon::parse($task->deadline)->format('M d, h:i A') }}</span>
                            <span class="text-[10px] font-black text-white bg-gray-900 px-2 py-0.5 rounded-lg uppercase tracking-tighter">{{ $task->submissions->count() }} Submissions</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="font-black text-gray-900 text-sm">{{ $task->points }} PTS</span>
                        <button @click="selectedTask = {{ json_encode($task) }}; submissions = {{ json_encode($task->submissions()->with('user')->get()) }}"
                            class="bg-white text-[#383838] border-2 border-[#383838] px-6 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest">
                            View Submissions
                        </button>
                    </div>
                </div>
            @empty
                <div class="py-10 border-2 border-dashed border-gray-100 rounded-2xl text-center text-gray-400 italic text-sm">No tasks assigned.</div>
            @endforelse
        </div>
    </div>

    <div>
        <h3 class="font-black text-gray-400 mb-5 flex items-center uppercase tracking-widest text-[10px]">
            <i class="fas fa-stopwatch me-2"></i> Active Quizzes
        </h3>
        <div class="grid grid-cols-1 gap-4">
            @forelse($session->quizzes ?? [] as $quiz)
                <div class="p-5 border border-gray-200 rounded-2xl flex justify-between items-center bg-white hover:border-gray-900 transition-all">
                    <div>
                        <h4 class="font-bold text-lg text-gray-900">{{ $quiz->title }}</h4>
                       <div class="flex items-center gap-4 mt-1">
                            <span class="text-xs text-gray-400 font-medium">Deadline: {{ \Carbon\Carbon::parse($quiz->deadline)->format('M d, h:i A') }}</span>
                            <span class="text-[10px] font-black text-white bg-gray-900 px-2 py-0.5 rounded-lg uppercase tracking-tighter">
                                {{ $quiz->attempts->count() }} Answered
                            </span>
                        </div>
                        <div class="flex gap-3 mt-1">
                            <span class="text-[10px] text-gray-400 border-l pl-3 uppercase tracking-tighter">{{ $quiz->time_limit }} Mins</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                     <span class="font-black text-gray-900 text-sm">{{ $quiz->questions->count() }} PTS</span>
                    <button @click="selectedQuiz = {{ json_encode($quiz) }}; scores = {{ json_encode($quiz->attempts()->with('user')->get()) }}"
                        class="bg-white text-[#383838] border-2 border-[#383838] px-6 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest">
                        View Results
                    </button>
                    </div>
                </div>
            @empty
                <div class="py-10 border-2 border-dashed border-gray-100 rounded-2xl text-center text-gray-400 italic text-sm">No quizzes available.</div>
            @endforelse
        </div>
    </div>

    <template x-if="selectedTask">
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center bg-[#383838] text-white">
                    <div>
                        <h3 class="font-black text-xl uppercase tracking-tight" x-text="selectedTask.title"></h3>
                        <p class="text-[10px] uppercase tracking-widest opacity-60">Task Submission Review & Grading</p>
                    </div>
                    <button @click="selectedTask = null" class="text-white hover:text-gray-300 text-3xl font-light">&times;</button>
                </div>
                <div class="overflow-y-auto p-8 bg-gray-50/50">
                    <table class="w-full text-left border-separate border-spacing-y-3">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                <th class="px-6 pb-2">Student Name</th>
                                <th class="px-6 pb-2">Attachment</th>
                                <th class="px-6 pb-2">Grade & Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="sub in submissions" :key="sub.id">
                                <tr class="bg-white shadow-sm rounded-2xl overflow-hidden">
                                    <td class="px-6 py-6 font-bold text-gray-900 rounded-s-2xl border-y border-l border-gray-100" x-text="sub.user.name"></td>
                                    <td class="px-6 py-6 border-y border-gray-100">
                                        <a :href="'{{ url('/') }}/' + sub.file_path" target="_blank" class="inline-flex items-center text-[10px] font-black text-[#383838] bg-gray-100 px-3 py-2 rounded-lg hover:bg-black hover:text-white transition-all uppercase tracking-widest">
                                            <i class="fas fa-file-download me-2"></i> View File
                                        </a>
                                    </td>
                                    <td class="px-6 py-6 rounded-e-2xl border-y border-r border-gray-100">
                                        <form :action="'/admin/grade/' + sub.id" method="POST" class="flex flex-col gap-2">
                                            @csrf
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="grade" :value="sub.grade" class="w-24 border-gray-200 rounded-xl text-sm font-black text-center focus:ring-[#383838]" placeholder="Score">
                                                <span class="text-[10px] font-black text-gray-400" x-text="'/ ' + selectedTask.points + ' PTS'"></span>
                                            </div>
                                            <div class="flex gap-2">
                                                <textarea name="feedback" :value="sub.feedback" class="flex-1 border-gray-200 rounded-xl text-xs p-3 focus:ring-[#383838]" placeholder="Enter student feedback..."></textarea>
                                                <button type="submit" class="bg-[#383838] text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-black transition self-end">Save</button>
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
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-[#383838]/80 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center bg-[#383838] text-white">
                    <div>
                        <h3 class="font-black text-xl uppercase tracking-tight" x-text="selectedQuiz.title"></h3>
                        <p class="text-[10px] uppercase tracking-widest opacity-60">Quiz Performance & Score Overview</p>
                    </div>
                    <button @click="selectedQuiz = null" class="text-white hover:text-gray-300 text-3xl font-light">&times;</button>
                </div>
                <div class="overflow-y-auto p-8 bg-gray-50/50">
                    <table class="w-full text-left border-separate border-spacing-y-3">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                <th class="px-6 pb-2">Student Name</th>
                                <th class="px-6 pb-2">Time Taken</th>
                                <th class="px-6 pb-2">Final Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="attempt in scores" :key="attempt.id">
                                <tr class="bg-white shadow-sm rounded-2xl overflow-hidden">
                                    <td class="px-6 py-6 font-bold text-gray-900 rounded-s-2xl border-y border-l border-gray-100" x-text="attempt.user.name"></td>
                                    <td class="px-6 py-6 border-y border-gray-100">
                                        <span class="text-xs font-bold text-gray-500" x-text="Math.floor(attempt.time_spent / 60) + 'm ' + (attempt.time_spent % 60) + 's'"></span>
                                    </td>
                                    <td class="px-6 py-6 rounded-e-2xl border-y border-r border-gray-100">
                                        <div class="flex items-center gap-4">
                                            <div class="px-4 py-2 bg-gray-900 text-white rounded-xl">
                                                <span class="text-sm font-black" x-text="attempt.score + ' / ' + attempt.total_questions"></span>
                                            </div>
                                            <div class="px-4 py-2 border-2 border-gray-100 rounded-xl">
                                                <span class="text-xs font-black text-[#383838]" x-text="Math.round((attempt.score / attempt.total_questions) * 100) + '%'"></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <template x-if="scores.length === 0">
                        <div class="text-center py-20">
                            <p class="text-gray-400 italic text-sm">No students have completed this quiz yet.</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
<div x-show="showModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-[#383838]/80 backdrop-blur-md p-4">
        <div class="bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md border border-gray-200">
            <h3 class="font-black text-2xl text-gray-900 mb-6">New Lab Activity</h3>
            <form action="{{ route('admin.tasks.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $session->id }}">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Activity Title</label>
                    <input type="text" name="title" class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none" required>
                </div>
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Instructions</label>
                    <textarea name="description" class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none" rows="3"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Max Pts</label>
                        <input type="number" name="points" class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Deadline</label>
                        <input type="datetime-local" name="deadline" class="w-full border-gray-200 bg-gray-50 rounded-xl p-3 focus:ring-2 focus:ring-black outline-none" required>
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="flex-1 py-3 bg-gray-100 text-gray-500 rounded-xl font-bold uppercase text-xs">Cancel</button>
                    <button type="submit" class="flex-1 py-3 bg-[#383838] text-white rounded-xl font-bold uppercase text-xs hover:bg-black transition shadow-lg shadow-gray-200">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<div x-data="{ open: false, type: 'pdf' }" 
     @open-material-modal.window="open = true" 
     x-show="open" 
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
     x-cloak>
    
    <div @click.away="open = false" class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-xl font-black text-gray-900 uppercase">Upload Learning Content</h3>
            <button @click="open = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="{{ route('admin.materials.store', $session->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5 ml-1">Material Title</label>
                <input type="text" name="title" required placeholder="e.g. Lesson 1: Variable Scoping" 
                       class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#383838] focus:border-transparent outline-none transition-all">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5 ml-1">Type of Content</label>
                <select name="type" x-model="type" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none cursor-pointer focus:ring-2 focus:ring-[#383838]">
                    <option value="pdf">PDF Document</option>
                    <option value="pptx">PowerPoint (.pptx)</option>
                    <option value="youtube">YouTube Video URL</option>
                </select>
            </div>

            <div class="p-4 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                
                <template x-if="type === 'youtube'">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Paste YouTube Link</label>
                        <input type="url" name="content_url" required placeholder="https://www.youtube.com/watch?v=..." 
                               class="w-full px-4 py-3 bg-white border border-gray-200 rounded-lg outline-none">
                    </div>
                </template>

                <template x-if="type === 'pdf' || type === 'pptx'">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Select File</label>
                        <input type="file" name="content_file" required 
                               :accept="type === 'pdf' ? '.pdf' : '.ppt,.pptx'"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#383838] file:text-white hover:file:bg-black transition-all">
                    </div>
                </template>
            </div>

            <button type="submit" class="w-full bg-[#383838] text-white py-4 rounded-xl font-bold hover:bg-black transition-all shadow-lg uppercase">
                Publish Material
            </button>
        </form>
    </div>
</div>

                        <div x-show="activeTab === 'students'"
                            class="bg-white p-6 rounded-xl shadow border border-gray-100">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="font-bold text-lg text-gray-800">Enrolled Students</h2>
                                <span class="px-3 py-1 text-white bg-gray-900 text-xs font-bold rounded-full">
                                    Total: {{ $class->students->count() }}
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr
                                            class="border-b border-gray-100 text-gray-400 uppercase text-xs tracking-wider">
                                            <th class="py-3 px-4 font-semibold">Student Name</th>
                                            <th class="py-3 px-4 font-semibold">Email</th>
                                            <th class="py-3 px-4 font-semibold text-center">Session Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @forelse($class->students as $student)
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="py-4 px-4 text-sm font-medium text-gray-900">
                                                    {{ $student->name }}
                                                </td>
                                                <td class="py-4 px-4 text-sm text-gray-500">
                                                    {{ $student->email }}
                                                </td>
                                                <td class="py-4 px-4 text-center">
                                                    @if($student->pivot->is_present)
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                                            <span
                                                                class="w-2 h-2 mr-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                                            ACTIVE
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500">
                                                            OFFLINE
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="py-12 text-center">
                                                    <p class="text-gray-400 italic">No students have enrolled in this
                                                        session yet.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div x-show="activeTab === 'settings'"
                            class="bg-white p-6 rounded-xl shadow border border-gray-100">
                            <h2 class="font-bold mb-4">Session Settings</h2>
                            <p>Configure session details and preferences...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>