<x-app-layout>
    <div class="p-8 max-w-7xl mx-auto">
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
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($joinedClasses as $item)
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 hover:border-black transition duration-300">
                    <div class="flex justify-between items-start mb-4">
                        <h4 class="text-xl font-black text-gray-900">{{ $item->subject_name }}</h4>
                        <span class="text-[10px] font-bold bg-[#383838] text-white px-2.5 py-1 rounded-full">
                            {{ $item->class_code }}
                        </span>
                    </div>

                    <p class="text-sm font-bold text-gray-700 mb-4">Instructor: {{ $item->faculty->name }}</p>

                    <div class="flex flex-wrap gap-2 mb-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                            <i class="ri-calendar-line mr-1.5"></i> {{ $item->schedule_day }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase tracking-wider">
                            <i class="ri-time-line mr-1.5"></i> {{ $item->schedule_time }}
                        </span>
                    </div>

                    <a href="{{ route('student.subject', $item->id) }}" 
                       class="block text-center w-full bg-[#383838] text-white font-bold py-3 px-4 rounded-xl hover:bg-black transition">
                        Enter Classroom
                    </a>
                </div>
            @empty
                <div class="col-span-3 text-center py-16 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                    <p class="text-gray-500 font-bold italic">No classes joined yet. Use the form above to get started!</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>