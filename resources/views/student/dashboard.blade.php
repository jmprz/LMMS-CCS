<x-app-layout>
    <div id="dashboard-root" 
         class="p-4 md:p-8 max-w-7xl mx-auto" 
         x-data="{ 
            classes: @js($joinedClasses->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->subject_name,
                'code' => $item->class_code,
                'instructor' => $item->faculty->name,
                'day' => $item->schedule_day,
                'time' => $item->schedule_time,
                'attendance' => $item->total_attended_days ?? 0,
                'isOpen' => $item->isCurrentlyScheduled(),
                'route' => route('student.subject', $item->id)
            ])),
            // Filters classes that are currently LIVE
            get activeClasses() {
                return this.classes.filter(c => c.isOpen);
            },
            // Filters classes that are currently CLOSED
            get offlineClasses() {
                return this.classes.filter(c => !c.isOpen);
            }
         }">

        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-gray-900">My Dashboard</h1>
                <p class="text-gray-500 font-bold mt-1">
                    You have <span class="text-green-600" x-text="activeClasses.length"></span> active lab sessions right now.
                </p>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm w-full lg:w-auto">
                <form action="{{ route('student.join') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="class_code" placeholder="Enter Class Code" 
                           class="border-gray-200 rounded-xl focus:ring-black text-sm w-full lg:w-48" required>
                    <button type="submit" class="bg-black text-white px-6 py-2 rounded-xl font-bold hover:bg-gray-800 transition text-sm">
                        Join
                    </button>
                </form>
            </div>
        </div>

        <div x-show="activeClasses.length > 0" x-transition:enter="transition ease-out duration-300" class="mb-12">
            <div class="flex items-center gap-2 mb-6">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <h2 class="text-xs font-black text-green-700 uppercase tracking-widest">Active Lab Sessions</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="cls in activeClasses" :key="cls.id">
                    <div class="bg-white p-6 rounded-3xl border-2 border-green-500 shadow-xl shadow-green-100/50 flex flex-col justify-between transform hover:scale-[1.02] transition duration-300">
                        <div>
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-black text-gray-900 leading-tight" x-text="cls.name"></h3>
                                <span class="text-[10px] font-black bg-green-100 text-green-700 px-3 py-1 rounded-full">LIVE</span>
                            </div>
                            <div class="space-y-1 mb-6">
                                <p class="text-xs font-bold text-gray-500" x-text="'Prof. ' + cls.instructor"></p>
                                <p class="text-[10px] font-black text-gray-400 uppercase" x-text="cls.attendance + ' Sessions Recorded'"></p>
                            </div>
                        </div>
                        <a :href="cls.route" class="block text-center w-full bg-black text-white font-bold py-4 rounded-2xl hover:bg-green-600 transition shadow-lg">
                            Enter Classroom
                        </a>
                    </div>
                </template>
            </div>
        </div>

        <div>
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Class Schedule</h2>
            <div class="bg-white rounded-3xl border border-gray-200 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-gray-400">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black uppercase">Subject</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase">Schedule</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="cls in offlineClasses" :key="cls.id">
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-800" x-text="cls.name"></span>
                                        <span class="text-[10px] font-black text-gray-400" x-text="cls.code"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-bold text-gray-600" x-text="cls.day + ' | ' + cls.time"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-[10px] font-black text-gray-300 bg-gray-100 px-3 py-1 rounded-full">CLOSED</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // Use a 5-second interval for better performance, checks for live sessions automatically
        setInterval(() => {
            fetch("{{ route('student.refresh-class-statuses') }}")
                .then(res => res.json())
                .then(data => {
                    const alpine = Alpine.$data(document.getElementById('dashboard-root'));
                    alpine.classes.forEach(cls => {
                        if (data[cls.id] !== undefined) {
                            cls.isOpen = data[cls.id];
                        }
                    });
                });
        }, 5000);
    </script>
</x-app-layout>