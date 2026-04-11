<x-app-layout>

    <div class="fixed inset-0 flex bg-gray-100" x-data="{ showModal: false }">

        <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0">


            <nav class="mt-8 px-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-3 px-4 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>
                <a href="{{ route('admin.classroom') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.classroom') ? 'bg-[#383838] text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }} transition">
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
                    <button @click="showModal = true"
                        class="bg-[#383838] text-white px-6 py-2.5 rounded-lg font-bold hover:bg-black transition">
                        + Create New Class
                    </button>
                </div>

                <div x-show="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                    style="display: none;">
                    <div @click.away="showModal = false" class="bg-white rounded-xl shadow-xl w-full max-w-4xl p-8 m-4">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-gray-800">New Classroom Session</h3>
                            <button @click="showModal = false" class="text-gray-500 hover:text-black">✕</button>
                        </div>

                        <form action="{{ route('admin.generate-code') }}" method="POST">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700">Assign Faculty
                                        (Professor)</label>
                                    <select name="faculty_id"
                                        class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                        required>
                                        <option value="" disabled selected>Select a Professor</option>
                                        @foreach($professors as $professor)
                                            <option value="{{ $professor->id }}">{{ $professor->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Subject Title</label>
                                        <input type="text" name="subject_name"
                                            placeholder="e.g., Software Engineering 1"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Class Code</label>
                                        <input type="text" name="class_code" placeholder="e.g., SOFTENG1"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">School Year</label>
                                        <select name="school_year"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                            <option value="" disabled selected>Select Academic Year</option>
                                            <option value="2025-2026">2025-2026</option>
                                            <option value="2026-2027">2026-2027</option>
                                            <option value="2027-2028">2027-2028</option>
                                            <option value="2028-2029">2028-2029</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Semester</label>
                                        <select name="semester"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                            <option value="1st Semester">1st Sem</option>
                                            <option value="2nd Semester">2nd Sem</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Program</label>
                                        <select name="program"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                            <option value="BSCS">BSCS</option>
                                            <option value="BSIT">BSIT</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Year Level</label>
                                        <select name="year_level"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                            <option value="1">1st Year</option>
                                            <option value="2">2nd Year</option>
                                            <option value="3">3rd Year</option>
                                            <option value="4">4th Year</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Section</label>
                                        <select name="section"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Schedule Day</label>
                                        <select name="schedule_day"
                                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                            required>
                                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                                                <option value="{{ $day }}">{{ $day }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700">Time Range</label>
                                        <div class="flex items-center gap-2">
                                            <input type="time" name="start_time"
                                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                required>
                                            <span class="text-gray-500 font-bold">to</span>
                                            <input type="time" name="end_time"
                                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                required>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit"
                                    class="w-full bg-[#383838] hover:bg-black text-white font-bold py-3 px-4 rounded-lg shadow transition uppercase tracking-widest text-sm">
                                    Save Session
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-data="{ 
    search: '', 
    filterProgram: 'All',
    filterYear: 'All',
    filterDay: 'All',
    sortOrder: 'asc',
    allSessions: @js($sessions),
    professors: @js($professors),

    // MODAL STATE

    howStudentsModal: false,
    viewingSession: { students: [] }, // Initialize with empty students array

    openStudents(session) {
    if (session.students) {
        // Sort students by name A-Z before displaying
        session.students.sort((a, b) => a.name.localeCompare(b.name));
    }
        this.viewingSession = session;
        this.showStudentsModal = true;
    },

    
    showEditModal: false,
    editingSession: {},

   openEdit(session) {
    // 1. Create a deep copy
    this.editingSession = { ...session };

    // 2. FORCE data types (Important for x-model)
    // Ensure faculty_id is a string if your option values are strings
    if (this.editingSession.faculty_id) {
        this.editingSession.faculty_id = String(this.editingSession.faculty_id);
    }

    // 3. Handle Time formatting (Keep your existing logic)
    if (session.schedule_time && session.schedule_time.includes('-')) {
        const parts = session.schedule_time.split('-');
        
        const formatTo24h = (timeStr) => {
            if (!timeStr) return '';
            const [time, modifier] = timeStr.trim().split(' ');
            let [hours, minutes] = time.split(':');
            
            // Fix for 12:xx PM and 12:xx AM
            let hoursInt = parseInt(hours, 10);
            if (modifier === 'PM' && hoursInt < 12) hoursInt += 12;
            if (modifier === 'AM' && hoursInt === 12) hoursInt = 0;
            
            return `${String(hoursInt).padStart(2, '0')}:${minutes.substring(0, 2)}`;
        };

        this.editingSession.start_time = formatTo24h(parts[0]);
        this.editingSession.end_time = formatTo24h(parts[1]);
    }

    this.showEditModal = true;
},

    get filteredSessions() {
        // 1. First, apply the filters
        let filtered = this.allSessions.filter(s => {
            const matchesSearch = s.subject_name.toLowerCase().includes(this.search.toLowerCase()) || 
                                 s.class_code.toLowerCase().includes(this.search.toLowerCase()) ||
                                 (s.faculty && s.faculty.name.toLowerCase().includes(this.search.toLowerCase()));
            
            const matchesProgram = this.filterProgram === 'All' || s.program === this.filterProgram;
            const matchesYear = this.filterYear === 'All' || String(s.year_level) === this.filterYear;
            const matchesDay = this.filterDay === 'All' || s.schedule_day === this.filterDay;
            
            return matchesSearch && matchesProgram && matchesYear && matchesDay;
        });

        // 2. Then, sort by Subject Name
        return filtered.sort((a, b) => {
            let nameA = a.subject_name.toLowerCase();
            let nameB = b.subject_name.toLowerCase();

            if (this.sortOrder === 'asc') {
                return nameA < nameB ? -1 : (nameA > nameB ? 1 : 0);
            } else {
                return nameA > nameB ? -1 : (nameA < nameB ? 1 : 0);
            }
        });
    }
}">

                    <div
                        class="mb-6 flex flex-col md:flex-row gap-4 bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                        <div class="relative flex-1">
                            <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" x-model="search"
                                placeholder="Search subject, class code, or professor..."
                                class="w-full pl-11 pr-4 py-2.5 rounded-xl border-gray-200 focus:ring-black focus:border-black text-sm">
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <select x-model="filterProgram"
                                class="rounded-xl border-gray-200 text-sm font-bold focus:ring-black focus:border-black">
                                <option value="All">All Programs</option>
                                <option value="BSCS">BSCS</option>
                                <option value="BSIT">BSIT</option>
                            </select>

                            <select x-model="filterYear"
                                class="rounded-xl border-gray-200 text-sm font-bold focus:ring-black focus:border-black">
                                <option value="All">All Years</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>

                            <select x-model="filterDay"
                                class="rounded-xl border-gray-200 text-sm font-bold focus:ring-black focus:border-black">
                                <option value="All">All Days</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>

                            <select x-model="sortOrder"
                                class="rounded-xl border-gray-200 text-sm font-bold focus:ring-black focus:border-black">
                                <option value="asc">A-Z (Subject)</option>
                                <option value="desc">Z-A (Subject)</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest">
                                        Class Code</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest">
                                        Subject & Section</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest">
                                        Schedule</th>
                                    <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest">
                                        Faculty</th>
                                    <th
                                        class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest text-center">
                                        Students</th>
                                    <th
                                        class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest text-right">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="session in filteredSessions" :key="session.id">
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <span
                                                class="font-mono font-bold bg-gray-100 px-2 py-1 rounded text-sm group-hover:bg-black group-hover:text-white transition"
                                                x-text="session.class_code"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-gray-900"
                                                    x-text="session.subject_name"></span>
                                                <span class="text-xs text-gray-500"
                                                    x-text="session.program + ' - ' + session.year_level + session.section"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-xs font-bold text-gray-700">
                                                <i class="ri-calendar-event-line text-gray-400 mr-1"></i>
                                                <span x-text="session.schedule_day"></span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 uppercase"
                                                x-text="session.schedule_time"></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600"
                                            x-text="session.faculty ? session.faculty.name : 'Unassigned'"></td>
                                        <td class="px-6 py-4 text-center">
                                            <button @click="openStudents(session)"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 hover:bg-black hover:text-white transition cursor-pointer">
                                                <i class="ri-user-3-line mr-1"></i>
                                                <span x-text="session.students_count || 0"></span>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end items-center gap-2">
                                                <button @click="openEdit(session)" title="Edit Details"
                                                    class="p-2 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition">
                                                    <i class="ri-edit-line text-lg"></i>
                                                </button>

                                                <form :action="'/admin/classroom/' + session.id" method="POST"
                                                    class="inline-block"
                                                    @submit.prevent="if(confirm('Are you sure? This will permanently delete the class.')) $el.submit()">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                        class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                                        title="Delete Class">
                                                        <i class="ri-delete-bin-line text-lg"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="filteredSessions.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500 italic">
                                        No classroom sessions found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <template x-if="showEditModal">
                        <div class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false">
                            </div>

                            <div
                                class="bg-white rounded-xl shadow-2xl w-full max-w-4xl p-8 relative z-10 animate-in fade-in zoom-in duration-200">
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-xl font-bold text-gray-800">Edit Classroom Session</h3>
                                    <button @click="showEditModal = false"
                                        class="text-gray-500 hover:text-black transition">
                                        <i class="ri-close-circle-fill text-2xl"></i>
                                    </button>
                                </div>

                                <form :action="'/admin/classroom/' + editingSession.id + '/edit'" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700">Assign Faculty
                                                (Professor)</label>
                                            <select name="faculty_id" x-model="editingSession.faculty_id"
                                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                required>
                                                <option value="" disabled>Select a Professor</option>
                                                <template x-for="prof in professors" :key="prof.id">
                                                    <option :value="String(prof.id)"
                                                        :selected="String(prof.id) === String(editingSession.faculty_id)"
                                                        x-text="prof.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Subject
                                                    Title</label>
                                                <input type="text" name="subject_name"
                                                    x-model="editingSession.subject_name"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                    required>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Class Code</label>
                                                <input type="text" name="class_code" x-model="editingSession.class_code"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                    required>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">School Year</label>
                                                <select name="school_year" x-model="editingSession.school_year"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                    required>
                                                    <option value="2025-2026">2025-2026</option>
                                                    <option value="2026-2027">2026-2027</option>
                                                    <option value="2027-2028">2027-2028</option>
                                                    <option value="2028-2029">2028-2029</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Semester</label>
                                                <select name="semester" x-model="editingSession.semester"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                    required>
                                                    <option value="1st Semester">1st Sem</option>
                                                    <option value="2nd Semester">2nd Sem</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Program</label>
                                                <select name="program" x-model="editingSession.program"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black">
                                                    <option value="BSCS">BSCS</option>
                                                    <option value="BSIT">BSIT</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Year Level</label>
                                                <select name="year_level" x-model="editingSession.year_level"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black">
                                                    <option value="1">1st Year</option>
                                                    <option value="2">2nd Year</option>
                                                    <option value="3">3rd Year</option>
                                                    <option value="4">4th Year</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Section</label>
                                                <select name="section" x-model="editingSession.section"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black">
                                                    <option value="A">A</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Schedule
                                                    Day</label>
                                                <select name="schedule_day" x-model="editingSession.schedule_day"
                                                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black">
                                                    <template
                                                        x-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']"
                                                        :key="day">
                                                        <option :value="day"
                                                            :selected="day === editingSession.schedule_day"
                                                            x-text="day"></option>
                                                    </template>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700">Time Range</label>
                                                <div class="flex items-center gap-2">
                                                    <input type="time" name="start_time"
                                                        x-model="editingSession.start_time"
                                                        class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                        required>
                                                    <span class="text-gray-500 font-bold">to</span>
                                                    <input type="time" name="end_time" x-model="editingSession.end_time"
                                                        class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-black focus:border-black"
                                                        required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pt-4 flex gap-3">
                                            <button type="submit"
                                                class="flex-1 bg-[#383838] hover:bg-black text-white font-bold py-3 rounded-lg shadow transition uppercase tracking-widest text-sm">
                                                Update Session
                                            </button>
                                            <button type="button" @click="showEditModal = false"
                                                class="px-6 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-lg transition uppercase tracking-widest text-sm">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </template>
                    <template x-if="showStudentsModal">
                        <div class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                                @click="showStudentsModal = false"></div>

                            <div
                                class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-8 relative z-10 animate-in fade-in zoom-in duration-200">
                                <div class="flex justify-between items-center mb-6">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-800"
                                            x-text="viewingSession.subject_name"></h3>
                                        <p class="text-sm text-gray-500"
                                            x-text="'Enrolled Students (' + (viewingSession.students ? viewingSession.students.length : 0) + ')'">
                                        </p>
                                    </div>
                                    <button @click="showStudentsModal = false"
                                        class="text-gray-500 hover:text-black transition">
                                        <i class="ri-close-circle-fill text-2xl"></i>
                                    </button>
                                </div>

                                <div class="max-h-[400px] overflow-y-auto pr-2">
                                    <table class="w-full text-left">
                                        <thead class="sticky top-0 bg-white border-b border-gray-100">
                                            <tr>
                                                <th class="py-2 text-xs font-black text-gray-400 uppercase">Student Name
                                                </th>
                                                <th class="py-2 text-xs font-black text-gray-400 uppercase">Student ID
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <template x-for="student in viewingSession.students" :key="student.id">
                                                <tr class="hover:bg-gray-50 transition-colors">
                                                    <td class="py-3 px-2">
                                                        <div class="flex items-center">
                                                            <div
                                                                class="h-8 w-8 rounded-full bg-[#383838] text-white flex items-center justify-center text-xs font-bold mr-3">
                                                                <span
                                                                    x-text="student.name.split(' ').map(n => n[0]).filter((_, i, a) => i === 0 || i === a.length - 1).join('').toUpperCase()"></span>
                                                            </div>
                                                            <span class="text-sm font-semibold text-gray-700"
                                                                x-text="student.name"></span>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-2 text-sm text-gray-500 font-mono"
                                                        x-text="student.school_id || 'No ID'"></td>
                                                </tr>
                                            </template>

                                            <template
                                                x-if="!viewingSession.students || viewingSession.students.length === 0">
                                                <tr>
                                                    <td colspan="2"
                                                        class="py-8 text-center text-gray-400 italic text-sm">
                                                        No students enrolled in this session yet.
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-6">
                                    <button @click="showStudentsModal = false"
                                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-lg transition uppercase tracking-widest text-xs">
                                        Close List
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>