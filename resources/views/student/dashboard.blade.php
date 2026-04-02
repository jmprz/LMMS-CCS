<x-app-layout>
    <div class="flex min-h-screen bg-gray-50">
        
        <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col sticky top-0 h-screen">
            <div class="px-6 pt-8 pb-4">
                <h2 class="font-black text-[#383838] uppercase tracking-widest text-[10px] opacity-50">
                    Main Navigation
                </h2>
            </div>

            <nav class="px-4 space-y-1 flex-1">
                <a href="{{ route('student.dashboard') }}" 
                   class="flex items-center py-3 px-4 rounded-xl transition duration-200 {{ request()->routeIs('student.dashboard') ? 'bg-[#383838] text-white font-bold shadow-lg shadow-gray-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                    <i class="ri-home-4-line mr-3 text-lg"></i>
                    <span class="text-sm">Home</span>
                </a>

                <div class="my-6 px-2">
                    <hr class="border-gray-100">
                </div>

                <h2 class="px-2 font-black text-[#383838] uppercase tracking-widest text-[10px] opacity-50 mb-4">
                    My Enrolled Classes
                </h2>

                <div class="space-y-1 overflow-y-auto max-h-[calc(100vh-300px)] custom-scrollbar">
                    @foreach(auth()->user()->joinedClasses as $enrolled)
                        @php
                            $isActive = request()->routeIs('student.subject') && request()->route('id') == $enrolled->id;
                        @endphp

                        <a href="{{ route('student.subject', $enrolled->id) }}" 
                           class="flex items-center py-3 px-4 rounded-xl transition duration-200 {{ $isActive ? 'bg-[#383838] text-white font-bold shadow-md' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900' }}">
                            <i class="{{ $isActive ? 'ri-book-open-fill' : 'ri-book-read-line' }} mr-3 text-lg"></i>
                            <div class="flex flex-col truncate">
                                <span class="text-sm truncate leading-tight">{{ $enrolled->subject_name }}</span>
                                <span class="text-[10px] {{ $isActive ? 'text-gray-300' : 'text-gray-400' }} font-medium">
                                   {{ $enrolled->program }} - {{ $enrolled->year_level }}{{ $enrolled->section }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </nav>

            <div class="p-4 border-t border-gray-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center py-3 px-4 rounded-xl text-red-500 hover:bg-red-50 transition duration-200 text-sm font-bold">
                        <i class="ri-logout-box-r-line mr-3 text-lg"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-8">
                    <h2 class="text-xl font-black text-gray-900 mb-4">Join New Class</h2>
                    <form action="{{ route('student.join') }}" method="POST" class="flex gap-4">
                        @csrf
                        <input type="text" name="class_code" placeholder="Enter Class Code (e.g. ABC123)" 
                            class="flex-1 border-gray-300 rounded-xl shadow-sm focus:ring-black focus:border-black p-3" required>
                        <button type="submit" class="bg-[#383838] text-white px-8 py-3 rounded-xl font-bold hover:bg-black transition">
                            Join
                        </button>
                    </form>
                </div>

                <h3 class="text-2xl font-black text-gray-900 mb-6">Your Enrolled Classes</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($joinedClasses as $item)
                        @php $isOpen = $item->isCurrentlyScheduled(); @endphp
                        <div class="bg-white p-6 rounded-2xl shadow-sm border transition duration-300 flex flex-col justify-between {{ $isOpen ? 'border-green-500 ring-1 ring-green-500' : 'border-gray-200 hover:border-black' }}">
                            <div>
                                <div class="flex justify-between items-start mb-4">
                                    <h4 class="text-xl font-black text-gray-900 leading-tight">{{ $item->subject_name }}</h4>
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="text-[10px] font-bold bg-[#383838] text-white px-2.5 py-1 rounded-full">{{ $item->class_code }}</span>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-md {{ $isOpen ? 'bg-green-100 text-green-700 animate-pulse' : 'bg-gray-100 text-gray-400' }}">
                                            {{ $isOpen ? '● LIVE' : 'CLOSED' }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-gray-700 mb-4">Instructor: {{ $item->faculty->name }}</p>
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase">
                                        <i class="ri-calendar-line mr-1.5"></i> {{ $item->schedule_day }}
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase">
                                        <i class="ri-time-line mr-1.5"></i> {{ $item->schedule_time }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                @if($isOpen)
                                    <a href="{{ route('student.subject', $item->id) }}" class="block text-center w-full bg-black text-white font-bold py-3 px-4 rounded-xl hover:scale-[1.02] transition shadow-lg shadow-green-100">Enter Classroom</a>
                                @else
                                    <button disabled class="block text-center w-full bg-gray-100 text-gray-400 font-bold py-3 px-4 rounded-xl cursor-not-allowed"><i class="ri-lock-line mr-2"></i> Closed</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                            <p class="text-gray-500 font-bold italic">No classes joined yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </main>
    </div>
</x-app-layout>