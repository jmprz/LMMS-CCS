<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6 border-l-4 border-black">
    <h3 class="text-lg font-bold text-gray-800 mb-6">Create New Laboratory Session</h3>
    <form action="{{ route('admin.generate-code') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
            
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700">Subject Code</label>
                <input type="text" name="subject_name" placeholder="e.g., SOFTENG1"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black" required>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-bold text-gray-700">Year</label>
                <select name="year_level" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black">
                    <option value="1">1st</option>
                    <option value="2">2nd</option>
                    <option value="3">3rd</option>
                    <option value="4">4th</option>
                </select>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-bold text-gray-700">Section</label>
                <input type="text" name="section" placeholder="e.g., A"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black" required>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-bold text-gray-700">Day</label>
                <select name="schedule_day" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black">
                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-1 flex items-end">
                <button type="submit" class="w-full bg-black hover:bg-gray-800 text-white font-bold py-2.5 px-4 rounded-lg shadow transition text-xs uppercase tracking-wider">
                    Generate
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mt-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700">Time Range</label>
                <div class="flex items-center gap-2">
                    <input type="time" name="start_time" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black" required>
                    <span class="mt-1 text-gray-500 font-bold">to</span>
                    <input type="time" name="end_time" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black" required>
                </div>
            </div>
        </div>
    </form>
</div>