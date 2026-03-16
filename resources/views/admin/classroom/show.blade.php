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
        <h1 class="text-4xl font-black text-gray-900 mb-3">{{ $session->subject_name }} | {{ $session->program }} - {{ $session->year_level }}{{ $session->section }}</h1>
        
        <div class="flex flex-wrap gap-2">
         
            
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                <i class="ri-calendar-line mr-2"></i> {{ $session->schedule_day }}
            </span>
            
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                <i class="ri-time-line mr-2"></i> {{ $session->schedule_time }}
            </span>
        </div>
    </div>

    <div class="bg-white border border-gray-200 shadow-sm rounded-2xl px-6 py-6 flex items-center justify-center">
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
    <div class="bg-white p-6 rounded-xl shadow border border-gray-100 mb-6 flex justify-between items-center">
        <div>
            <h2 class="font-bold text-lg">Session Control</h2>
            <p class="text-sm text-gray-500">Status: 
                <span class="font-bold {{ $session->is_active ? 'text-green-600' : 'text-red-600' }}">
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
            <p class="text-gray-500 font-bold">Session is currently offline. Click "Start Session" to allow students to join.</p>
        </div>
    @endif
</div>

       <div x-show="activeTab === 'tasks'" x-data="{ showModal: false }" class="bg-white p-6 rounded-xl shadow border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="font-bold text-lg">Task Management</h2>
            <p class="text-sm text-gray-500">Create and manage lab tasks for students.</p>
        </div>
        <button @click="showModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold transition">
            + Create Task
        </button>
    </div>

    <div id="task-list" class="space-y-4">
    @forelse($tasks as $task)
        <div class="p-4 border border-gray-200 rounded-lg flex justify-between items-center">
            <div>
                <h4 class="font-bold text-gray-800">{{ $task->title }}</h4>
                <p class="text-sm text-gray-600">{{ $task->description }}</p>
                <span class="text-xs text-gray-400">Deadline: {{ \Carbon\Carbon::parse($task->deadline)->format('M d, Y H:i') }}</span>
            </div>
            <div class="text-right">
                <span class="block font-bold text-blue-600">{{ $task->points }} pts</span>
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-sm">No tasks created yet.</p>
    @endforelse
</div>

    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
        <div class="bg-white p-8 rounded-xl shadow-2xl w-full max-w-md border border-gray-200" @click.away="showModal = false">
            <h3 class="font-bold text-lg mb-6 text-gray-800">Create New Task</h3>
            
            <form action="{{ route('admin.tasks.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <input type="hidden" name="subject_id" value="{{ $session->id }}">
                    <label class="block text-sm font-medium text-gray-700">Task Title</label>
                    <input type="text" name="title" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instructions</label>
                    <textarea name="description" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" rows="3"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Points</label>
                        <input type="number" name="points" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Deadline</label>
                        <input type="datetime-local" name="deadline" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-bold transition">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

       <div x-show="activeTab === 'students'" class="bg-white p-6 rounded-xl shadow border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="font-bold text-lg text-gray-800">Enrolled Students</h2>
        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">
            Total: {{ $class->students->count() }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-100 text-gray-400 uppercase text-xs tracking-wider">
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
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                    <span class="w-2 h-2 mr-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    ACTIVE
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500">
                                    OFFLINE
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-12 text-center">
                            <p class="text-gray-400 italic">No students have enrolled in this session yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

        <div x-show="activeTab === 'settings'" class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h2 class="font-bold mb-4">Session Settings</h2>
            <p>Configure session details and preferences...</p>
        </div>
    </div>
</div>
    </div>
    </main>
    </div>
</x-app-layout>