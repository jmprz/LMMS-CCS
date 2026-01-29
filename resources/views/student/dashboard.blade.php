<x-app-layout>
    <div class="p-6">
        <form action="{{ route('student.join') }}" method="POST" class="mb-8">
            @csrf
            <input type="text" name="class_code" placeholder="Enter Class Code" class="border p-2 rounded w-64">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Join Subject</button>
        </form>

        <h3 class="text-lg font-bold mb-4">Your Class</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($joinedClasses as $item)
                <div class="p-4 border rounded shadow-sm bg-gray-50">
                    <h4 class="font-bold text-lg text-indigo-800">{{ $item->subject_name }}</h4>
                    <p class="text-sm text-gray-600">Instructor: {{ $item->faculty->name }}</p>
                    <p class="text-sm text-gray-600">Schedule: {{ $item->schedule_day }} {{ $item->schedule_time }}</p>

                    <a href="{{ route('student.subject', $item->id) }}" 
                       class="mt-4 inline-block bg-indigo-600 text-white px-4 py-2 rounded text-sm">
                        Enter Laboratory Session
                    </a>
                </div>
            @empty
                <div class="col-span-2 text-center py-10 bg-gray-100 rounded border-2 border-dashed">
                    <p class="text-gray-500 italic">You haven't joined any laboratory sessions yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>