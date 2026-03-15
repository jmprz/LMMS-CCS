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

        <div x-show="activeTab === 'tasks'" class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h2 class="font-bold mb-4">Tasks</h2>
            <p>Manage tasks for this lab session...</p>
        </div>

        <div x-show="activeTab === 'students'" class="bg-white p-6 rounded-xl shadow border border-gray-100">
            <h2 class="font-bold mb-4">Enrolled Students</h2>
            <p>List of students joined in this session...</p>
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