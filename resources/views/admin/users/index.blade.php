<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100">

        <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0">
            <nav class="mt-8 px-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-3 px-4 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>
                <a href="{{ route('admin.classroom') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.classroom') ? 'bg-black text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="ri-graduation-cap-line mr-3 text-lg"></i>
                    Classroom
                </a>
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.users*') ? 'bg-[#383838] text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="ri-user-line mr-3 text-lg"></i> User Management
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
                <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
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

                        <select x-model="filterYear" :disabled="filterRole === 'professor' || filterRole === 'admin'"
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

                                        <td x-show="filterRole === 'student' || filterRole === ''" class="px-6 py-4">
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
                                                <button
                                                    class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition"><i
                                                        class="ri-edit-line"></i></button>

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
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic text-sm">No
                                            users found matching your filters.</td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="logModalOpen"
                        class="fixed inset-0 z-[10000] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
                        x-transition.opacity x-cloak>

                        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh] m-4"
                            @click.away="logModalOpen = false">
                            <div class="p-6 border-b flex justify-between items-center bg-white rounded-t-2xl">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Activity Timeline</h3>
                                    <p class="text-sm text-gray-500">History for <span class="text-black font-bold"
                                            x-text="selectedUserName"></span></p>
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
                                                            <i :class="getIcon(log.log_type)" class="text-lg"></i>
                                                        </div>

                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex justify-between items-start gap-2">
                                                                <div>
                                                                    <p class="text-sm font-bold text-gray-800 leading-tight"
                                                                        x-text="log.content"></p>
                                                                    <p class="text-[11px] mt-1 text-gray-500"
                                                                        x-text="log.log_type === 'attendance' ? 'Verified Check-in' : 'Navigation Log'">
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
                    return type === 'navigation' ? 'ri-global-line' : 'ri-cursor-line';
                },

                getIconClass(type) {
                    if (type === 'attendance') return 'bg-gray-100 text-gray-600';
                    return type === 'navigation' ? 'bg-gray-100 text-gray-600' : 'bg-gray-100 text-gray-600';
                }
            }
        }
    </script>
</x-app-layout>