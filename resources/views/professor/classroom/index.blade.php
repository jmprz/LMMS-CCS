<x-app-layout>

   <div class="fixed inset-0 flex bg-gray-100" x-data="{ showModal: false }">

       <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0">


         <nav class="mt-8 px-4 space-y-2">
            <a href="{{ route('professor.dashboard') }}"
               class="flex items-center py-3 px-4 rounded-lg text-gray-600 hover:bg-gray-100 transition">
               <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
            </a>
            <a href="{{ route('professor.classroom') }}"
               class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('professor.classroom') ? 'bg-[#383838] text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }} transition">
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
         <div class="p-8 mt-[80px]">
            
            <div class="flex justify-between items-center mb-8">
               <h1 class="text-2xl font-bold text-gray-800">Classroom Management</h1>
            </div>

            <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
               <div @click.away="showModal = false" class="bg-white rounded-xl shadow-xl w-full max-w-4xl p-8 m-4">
                  <div class="flex justify-between items-center mb-6">
                     <h3 class="text-xl font-bold">New Classroom Session</h3>
                     <button @click="showModal = false" class="text-gray-500 hover:text-black">✕</button>
                  </div>
                
               </div>
            </div>

         <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @forelse ($sessions as $session)
        <a href="{{ route('professor.classroom.show', $session->id) }}" 
           class="block bg-white p-6 rounded-2xl shadow-sm border border-gray-200 hover:border-black transition transform hover:-translate-y-1">
            
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-xl font-black text-gray-900">{{ $session->subject_name }} | {{ $session->program }} - {{ $session->year_level }}{{ $session->section }}</h3>
                <span class="text-[10px] font-bold bg-[#383838] text-white px-2.5 py-1 rounded-full">
                    {{ $session->class_code }}
                </span>
            </div>
            
            <div class="space-y-4">
                <p class="text-sm font-bold text-gray-700">
                  
                </p>

                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                        <i class="ri-calendar-line mr-1.5"></i> {{ $session->schedule_day }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                        <i class="ri-time-line mr-1.5"></i> {{ $session->schedule_time }}
                    </span>
                </div>
            </div>
        </a>
    @empty
        <div class="col-span-3 text-center py-10 text-gray-500">
            No active laboratory sessions. Create one to get started!
        </div>
    @endforelse
</div>
         </div>
      </main>
   </div>
</x-app-layout>