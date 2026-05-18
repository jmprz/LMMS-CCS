<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100">

        <aside
            class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)] sticky top-[80px]">
            <nav class="mt-8 px-4 space-y-2">
                <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">System Admin</div>

                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.dashboard') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>

                <a href="{{ route('admin.classroom') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.classroom*') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-folder-5-line mr-3 text-lg"></i> Classroom
                </a>

                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.users*') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-user-line mr-3 text-lg"></i> Users
                </a>

                <a href="{{ route('profile.edit') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('profile.edit') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-settings-5-line mr-3 text-lg"></i> Settings
                </a>
                <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Platform Support
                </div>

                <a href="#"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.about') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-information-line mr-3 text-lg"></i> About System
                </a>

                <a href="#"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.faqs') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-questionnaire-line mr-3 text-lg"></i> FAQs Hub
                </a>
            </nav>

            <div class="p-4 border-t border-gray-200 bg-gray-50/50 relative" x-data="{ open: false }"
                @click.away="open = false">

                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute bottom-full left-4 right-4 mb-2 w-56 rounded-xl md:w-auto bg-white border border-gray-200 shadow-xl z-50 divide-y divide-gray-100"
                    style="display: none;">

                    <div class="py-1">
                        <form id="admin-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                            @csrf
                        </form>
                        <a href="{{ route('logout') }}"
                            class="flex items-center px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 transition"
                            onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                            <i class="ri-logout-box-r-line mr-2.5 text-red-500 text-sm"></i> Sign Out
                        </a>
                    </div>
                </div>

                <button @click="open = !open"
                    class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-gray-200/60 transition duration-150 text-left">
                    <div class="flex items-center min-w-0">
                        <div
                            class="h-9 w-9 rounded-xl bg-[#383838] flex items-center justify-center text-white uppercase font-black shadow-sm text-xs flex-shrink-0">
                            {{ substr(Auth::user()->name, 0, 1) }}{{ substr(strrchr(Auth::user()->name, " "), 1, 1) }}
                        </div>

                        <div class="ml-3 truncate">
                            <p class="text-xs font-black text-gray-800 truncate leading-snug">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider leading-none mt-0.5">
                                Administrator
                            </p>
                        </div>
                    </div>
                    <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
                        :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
                </button>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full">
            <div class="p-8 mt-[80px]">
                <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                <div x-data="{ 
    showEditModal: false, 
    editUserData: { 
        id: '', 
        first_name: '', 
        middle_name: '', 
        last_name: '', 
        school_id: '',
        email: '',
        role: '',
        program: '',
        year_level: '',
        section: ''
    },
    openEditModal(user) {
        this.editUserData = { ...user };
        this.showEditModal = true;
    }
}" x-cloak>
                    <div class="mt-4" x-data="userManagement({{ $users->toJson() }})">
                        <div
                            class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-sm items-center">
                            <div class="relative">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" x-model="search" placeholder="Search name..."
                                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-black focus:border-black outline-none transition">
                            </div>

                            <select x-model="filterRole"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-black focus:border-black">
                                <option value="">All Roles</option>
                                <option value="student">Student</option>
                                <option value="professor">Professor</option>
                                <option value="admin">Admin</option>
                            </select>

                            <select x-model="filterProgram" :disabled="filterRole !== 'student' && filterRole !== ''"
                                :class="filterRole !== 'student' && filterRole !== '' ? 'opacity-50 cursor-not-allowed' : ''"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none transition focus:ring-black focus:border-black">
                                <option value="">All Programs</option>
                                <option value="BSCS">BSCS</option>
                                <option value="BSIT">BSIT</option>
                            </select>

                            <select x-model="filterYear"
                                :disabled="filterRole === 'professor' || filterRole === 'admin'"
                                :class="filterRole === 'professor' || filterRole === 'admin' ? 'opacity-50 cursor-not-allowed' : ''"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none transition focus:ring-black focus:border-black">
                                <option value="">All Years</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>

                            <select x-model="filterSection" :disabled="filterRole !== 'student' && filterRole !== ''"
                                :class="filterRole !== 'student' && filterRole !== '' ? 'opacity-50 cursor-not-allowed' : ''"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none transition">
                                <option value="">All Sections</option>
                                <option value="A">Section A</option>
                                <option value="B">Section B</option>
                                <option value="C">Section C</option>
                            </select>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr class="text-gray-400 text-[11px] uppercase tracking-wider">
                                        <th class="px-6 py-4 font-semibold">User</th>
                                        <th x-show="filterRole === 'student' || filterRole === ''"
                                            class="px-6 py-4 font-semibold">Program/Year/Section</th>
                                        <th class="px-6 py-4 font-semibold">Role</th>
                                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <template x-for="user in filteredUsers" :key="user.id">
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="px-6 py-4">
                                                <span
                                                    x-text="`${user.last_name}, ${user.first_name} ${user.middle_name ? user.middle_name.charAt(0) + '.' : ''}`"></span>
                                                <div class="text-[10px] text-gray-400" x-text="user.school_id"></div>
                                            </td>

                                            <td x-show="filterRole === 'student' || filterRole === ''"
                                                class="px-6 py-4">
                                                <div class="text-xs text-gray-600">
                                                    <span x-text="user.program || 'N/A'"></span> -
                                                    <span x-text="user.year_level || 'N/A'"></span><span
                                                        x-text="user.section || ''"></span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span
                                                    class="px-2 py-1 bg-gray-100 font-mono font-bold rounded text-[10px] font-bold uppercase"
                                                    x-text="user.role"></span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button @click="viewActivity(user.id, user.name)"
                                                        class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition">
                                                        <i class="ri-history-line"></i>
                                                    </button>
                                                    <button @click="openEditModal(user)"
                                                        class="p-2 text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition">
                                                        <i class="ri-edit-line"></i>
                                                    </button>
                                                    <form :action="'/admin/users/' + user.id" method="POST"
                                                        onsubmit="return confirm('Are you sure?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition"><i
                                                                class="ri-delete-bin-line"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="filteredUsers.length === 0">
                                        <tr>
                                            <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic text-sm">
                                                No
                                                users found matching your filters.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="showEditModal" class="fixed inset-0 z-[100] overflow-y-auto" x-cloak>

                            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

                            <div class="flex min-h-full items-center justify-center p-4">
                                <div class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-8 shadow-2xl transition-all"
                                    @click.away="showEditModal = false">

                                    <div class="flex items-center justify-between mb-6">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900">Edit User Account</h3>
                                            <p class="text-sm text-gray-500">Update information for ID: <span
                                                    x-text="editUserData.school_id" class="font-mono font-bold"></span>
                                            </p>
                                        </div>
                                        <button @click="showEditModal = false"
                                            class="text-gray-400 hover:text-gray-600">
                                            <i class="ri-close-line text-2xl"></i>
                                        </button>
                                    </div>

                                    <form :action="'/admin/users/' + editUserData.id" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-gray-400 mb-1">First
                                                    Name</label>
                                                <input type="text" name="first_name" x-model="editUserData.first_name"
                                                    required
                                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-gray-400 mb-1">Middle
                                                    Name</label>
                                                <input type="text" name="middle_name" x-model="editUserData.middle_name"
                                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-gray-400 mb-1">Last
                                                    Name</label>
                                                <input type="text" name="last_name" x-model="editUserData.last_name"
                                                    required
                                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-gray-400 mb-1">Email
                                                    Address</label>
                                                <input type="email" name="email" x-model="editUserData.email" required
                                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-gray-400 mb-1">System
                                                    Role</label>
                                                <select name="role" x-model="editUserData.role" required
                                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                                    <option value="student">STUDENT</option>
                                                    <option value="professor">PROFESSOR</option>
                                                    <option value="admin">ADMIN</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div x-show="editUserData.role === 'student'"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 transform -translate-y-2"
                                            class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-blue-50 rounded-xl mb-6">
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-blue-400 mb-1">Program</label>
                                                <input type="text" name="program" x-model="editUserData.program"
                                                    placeholder="e.g. BSCS"
                                                    class="w-full px-4 py-2 bg-white border border-blue-100 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-blue-400 mb-1">Year
                                                    Level</label>
                                                <input type="number" name="year_level" x-model="editUserData.year_level"
                                                    placeholder="e.g. 3"
                                                    class="w-full px-4 py-2 bg-white border border-blue-100 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[11px] font-bold uppercase text-blue-400 mb-1">Section</label>
                                                <input type="text" name="section" x-model="editUserData.section"
                                                    placeholder="e.g. A"
                                                    class="w-full px-4 py-2 bg-white border border-blue-100 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-3 mt-8">
                                            <button type="button" @click="showEditModal = false"
                                                class="px-6 py-2.5 rounded-xl text-gray-600 font-semibold hover:bg-gray-100 transition">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                class="px-6 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">
                                                Update User Account
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <template x-teleport="body">
                            <div x-show="logModalOpen"
                                class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
                                x-transition.opacity x-cloak>

                                <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh] m-4"
                                    @click.away="logModalOpen = false">
                                    <div class="p-6 border-b flex justify-between items-center bg-white rounded-t-2xl">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-800">Activity Timeline</h3>
                                            <p class="text-sm text-gray-500">History for <span
                                                    class="text-black font-bold" x-text="selectedUserName"></span></p>
                                        </div>
                                        <button @click="logModalOpen = false"
                                            class="text-gray-400 hover:text-gray-600 p-2 transition">
                                            <i class="ri-close-line text-2xl"></i>
                                        </button>
                                    </div>

                                    <div class="p-6 overflow-y-auto bg-gray-50/50 flex-1">
                                        <template x-if="loading">
                                            <div class="flex flex-col items-center justify-center py-20 gap-3">
                                                <i class="ri-loader-4-line animate-spin text-4xl text-gray-500"></i>
                                                <p class="text-gray-400 text-sm font-medium">Syncing timeline...</p>
                                            </div>
                                        </template>

                                        <div class="space-y-8 relative">
                                            <div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-gray-200"></div>

                                            <template x-for="(group, date) in groupedLogs" :key="date">
                                                <div class="relative">
                                                    <div class="sticky top-0 z-20 mb-6">
                                                        <span
                                                            class="bg-white border border-gray-200 text-gray-600 text-[11px] font-bold uppercase tracking-wider px-4 py-1.5 rounded-full shadow-sm"
                                                            x-text="formatDateHeader(date)"></span>
                                                    </div>

                                                    <div class="space-y-4 ml-4">
                                                        <template x-for="log in group" :key="log.id">
                                                            <div
                                                                class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-start gap-4 hover:border-gray-300 transition-all relative group">
                                                                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 z-10"
                                                                    :class="getIconClass(log.log_type)">
                                                                    <i :class="getIcon(log.log_type)"
                                                                        class="text-lg"></i>
                                                                </div>

                                                                <div class="flex-1 min-w-0">
                                                                    <div class="flex justify-between items-start gap-2">
                                                                        <div>
                                                                            <p class="text-sm font-bold text-gray-800 leading-tight"
                                                                                x-text="log.content"></p>
                                                                            <p class="text-[11px] mt-1 text-gray-500 font-bold uppercase tracking-wider text-[10px]"
                                                                                x-text="log.log_type === 'attendance' ? 'Verified Check-in' : 
                                                                            log.log_type === 'submission' ? 'Task Submission' : 
                                                                            log.log_type === 'material' ? 'Courseware Access' : 
                                                                            log.log_type === 'quiz' ? 'Assessment Activity' : 'Navigation Log'">
                                                                            </p>
                                                                        </div>
                                                                        <span
                                                                            class="text-[10px] font-mono text-gray-500 font-bold bg-gray-100 px-2 py-0.5 rounded"
                                                                            x-text="formatTime(log.created_at)"></span>
                                                                    </div>

                                                                    <div
                                                                        class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] text-gray-400">
                                                                        <template x-if="log.log_type !== 'attendance'">
                                                                            <span class="flex items-center gap-1">
                                                                                <i class="ri-time-line"></i>
                                                                                Duration: <strong class="text-gray-600"
                                                                                    x-text="log.duration_seconds + 's'"></strong>
                                                                            </span>
                                                                        </template>

                                                                        <span class="flex items-center gap-1">
                                                                            <i class="ri-book-open-line"></i>
                                                                            Class: <strong class="text-gray-600"
                                                                                x-text="log.class_name || 'General Session'"></strong>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function userManagement(initialUsers) {
            return {
                users: initialUsers,
                search: '',
                filterRole: '',
                filterProgram: '',
                filterYear: '',
                filterSection: '',

                logModalOpen: false,
                loading: false,
                selectedUserName: '',
                logs: [],

                init() {
                    this.$watch('filterRole', value => {
                        if (value === 'professor' || value === 'admin') {
                            this.filterProgram = '';
                            this.filterYear = '';
                            this.filterSection = '';
                        }
                    });
                },

                get filteredUsers() {
                    return this.users.filter(user => {
                        // FIX: Construct fullName safely since user.name might be null/undefined
                        const firstName = user.first_name || '';
                        const lastName = user.last_name || '';
                        const fullName = `${firstName} ${lastName}`.toLowerCase();

                        // 1. Basic Search and Role check
                        const matchesSearch = this.search === '' || fullName.includes(this.search.toLowerCase());
                        const matchesRole = this.filterRole === '' || user.role === this.filterRole;

                        // 2. Metadata checks (Program, Year, Section)
                        const matchesProgram = this.filterProgram === '' || (user.program || '') === this.filterProgram;
                        const matchesYear = this.filterYear === '' || String(user.year_level || '') === this.filterYear;
                        const matchesSection = this.filterSection === '' || (user.section || '') === this.filterSection;

                        return matchesSearch && matchesRole && matchesProgram && matchesYear && matchesSection;
                    });
                },

                async viewActivity(userId, userName) {
                    // 1. Format the name to "Last Name, First Name"
                    const user = this.users.find(u => u.id === userId);
                    this.selectedUserName = `${user.last_name}, ${user.first_name}`;

                    this.logModalOpen = true;
                    this.loading = true;
                    this.logs = [];

                    try {
                        const response = await fetch(`/admin/users/${userId}/activity-logs`);
                        let fetchedLogs = await response.json();

                        if (user && user.attendances) {
                            user.attendances.forEach(att => {
                                fetchedLogs.push({
                                    id: 'att-' + att.id,
                                    log_type: 'attendance',
                                    content: 'Official Attendance Marked',

                                    // Check both snake_case and camelCase just to be safe
                                    class_name: (att.lab_session ? att.lab_session.subject_name : null) ||
                                        (att.labSession ? att.labSession.subject_name : null) ||
                                        'Academic Session', // This is the fallback you are seeing

                                    duration_seconds: 0,
                                    created_at: `${att.attendance_date} ${att.joined_at}`
                                });
                            });
                        }

                        this.logs = fetchedLogs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                    } catch (e) {
                        console.error("Log Fetch Failed", e);
                    } finally {
                        this.loading = false;
                    }
                },
                get groupedLogs() {
                    return this.logs.reduce((groups, log) => {
                        // Safe split for both "YYYY-MM-DD HH:MM:SS" and ISO "T" formats
                        const date = log.created_at.split(/[ T]/)[0];
                        if (!groups[date]) {
                            groups[date] = [];
                        }
                        groups[date].push(log);
                        return groups;
                    }, {});
                },

                formatDateHeader(dateStr) {
                    const today = new Date().toISOString().split('T')[0];
                    const yesterdayDate = new Date();
                    yesterdayDate.setDate(yesterdayDate.getDate() - 1);
                    const yesterday = yesterdayDate.toISOString().split('T')[0];

                    if (dateStr === today) return 'Today';
                    if (dateStr === yesterday) return 'Yesterday';

                    return new Date(dateStr).toLocaleDateString('en-US', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric'
                    });
                },

                formatTime(dateStr) {
                    return new Date(dateStr).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                },

                getIcon(type) {
                    if (type === 'attendance') return 'ri-checkbox-circle-line';
                    if (type === 'navigation') return 'ri-global-line';
                    if (type === 'submission') return 'ri-file-upload-line';
                    if (type === 'material') return 'ri-book-open-line';
                    if (type === 'quiz') return 'ri-task-line';
                    return 'ri-cursor-line';
                },

                getIconClass(type) {
                    if (type === 'attendance') return 'bg-green-50 text-green-600 border border-green-200';
                    if (type === 'navigation') return 'bg-amber-50 text-amber-600 border border-amber-200';
                    if (type === 'submission') return 'bg-blue-50 text-blue-600 border border-blue-200';
                    if (type === 'material') return 'bg-purple-50 text-purple-600 border border-purple-200';
                    if (type === 'quiz') return 'bg-indigo-50 text-indigo-600 border border-indigo-200';
                    return 'bg-gray-100 text-gray-600';
                }
            }
        }
    </script>
</x-app-layout>