<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100 overflow-hidden" x-data="{ sidebarOpen: false }">

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div x-show="sidebarOpen" 
             x-transition.opacity 
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/50 z-40 md:hidden" 
             style="display: none;">
        </div>

        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed md:static inset-y-0 left-0 z-50 w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)] transform transition-transform duration-300 ease-in-out md:translate-x-0">
            <nav class="mt-8 px-4 space-y-2 overflow-y-auto flex-1">
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
            </nav>

            <div class="p-4 border-t border-gray-200 bg-gray-50/50 relative flex-shrink-0" x-data="{ open: false }"
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

        <main class="flex-1 overflow-y-auto h-full flex flex-col min-w-0">
            <!-- Mobile Header Toggle Bar -->
            <div class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 mt-[80px] flex-shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="ri-menu-2-line text-xl"></i>
                </button>
                <span class="text-xs font-black uppercase text-gray-700 tracking-wider">Navigation</span>
            </div>

            <div class="p-4 sm:p-8 md:mt-[80px]">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">User Management</h1>
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
                        section: '',
                        status: 'enrolled'
                    },
                    openEditModal(user) {
                        this.editUserData = { ...user };
                        this.showEditModal = true;
                    }
                }" x-cloak>
                    <div class="mt-4" x-data="userManagement({{ $users->toJson() }})">
                        <div
                            class="mb-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-sm items-center">
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

                            <select
                                x-model="filterStatus"
                                :disabled="filterRole !== 'student' && filterRole !== ''"
                                :class="filterRole !== 'student' && filterRole !== '' ? 'opacity-50 cursor-not-allowed' : ''"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none transition">

                                <option value="">All Status</option>
                                <option value="enrolled">Enrolled</option>
                                <option value="dropped">Dropped</option>
                                <option value="graduated">Graduated</option>
                            </select>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left min-w-[650px]">
                                    <thead class="bg-gray-50 border-b border-gray-100">
                                        <tr class="text-gray-400 text-[11px] uppercase tracking-wider">
                                            <th class="px-6 py-4 font-semibold">User</th>
                                            <th x-show="filterRole === 'student' || filterRole === ''"
                                                class="px-6 py-4 font-semibold">Program/Year/Section</th>
                                            <th class="px-6 py-4 font-semibold">Role</th>
                                            <th x-show="filterRole === 'student' || filterRole === ''" 
                                                class="px-6 py-4 font-semibold">Status</th>
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
                                                        class="px-2 py-1 bg-gray-100 font-mono font-bold rounded text-[10px] uppercase"
                                                        x-text="user.role"></span>
                                                </td>
                                                <td
                                                    x-show="filterRole === 'student' || filterRole === ''"
                                                    class="px-6 py-4">
                                                    <template x-if="user.role === 'student'">
                                                        <span
                                                            class="px-3 py-1 rounded-full text-[10px] font-bold uppercase"
                                                            :class="{
                                                                'bg-green-100 text-green-700': user.status === 'enrolled',
                                                                'bg-red-100 text-red-700': user.status === 'dropped',
                                                                'bg-blue-100 text-blue-700': user.status === 'graduated'
                                                            }"
                                                            x-text="user.status">
                                                        </span>
                                                    </template>
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex justify-end gap-2">
                                                        <button
                                                            @click="openStudentDrive(user.id, user.first_name, user.last_name)"
                                                            class="p-2 text-gray-600 hover:bg-gray-50 hover:text-black rounded-lg transition"
                                                            title="View Cloud Storage">
                                                            <i class="ri-cloud-line text-lg"></i>
                                                        </button>
                                                        <button @click="viewActivity(user.id, user.name)"
                                                            class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition" title="Activity Timeline">
                                                            <i class="ri-history-line"></i>
                                                        </button>
                                                        <button @click="openEditModal(user)"
                                                            class="p-2 text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition" title="Edit User">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                        <form :action="'/admin/users/' + user.id" method="POST"
                                                            onsubmit="return confirm('Are you sure?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition" title="Delete User"><i
                                                                    class="ri-delete-bin-line"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-if="filteredUsers.length === 0">
                                            <tr>
                                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic text-sm">
                                                    No users found matching your filters.</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <template x-teleport="body">
                            <div x-show="showEditModal" class="fixed inset-0 z-[99999] overflow-y-auto" x-cloak>
                                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity" x-transition.opacity></div>
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div class="relative w-full max-w-2xl transform overflow-hidden rounded-[2.5rem] bg-white p-6 sm:p-8 shadow-2xl transition-all border border-gray-100"
                                        @click.away="showEditModal = false"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                                        <div class="flex items-center justify-between mb-8">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-[#383838] border border-gray-100">
                                                    <i class="ri-user-settings-line text-xl"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-xl font-black text-gray-900 tracking-tight uppercase">
                                                        Edit User Account</h3>
                                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                        Update information for ID: <span x-text="editUserData.school_id"
                                                            class="text-[#383838] font-black"></span>
                                                    </p>
                                                </div>
                                            </div>
                                            <button @click="showEditModal = false"
                                                class="text-gray-400 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 p-2 rounded-full transition-colors">
                                                <i class="ri-close-line text-xl"></i>
                                            </button>
                                        </div>

                                        <form :action="'/admin/users/' + editUserData.id" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 px-1">First Name</label>
                                                    <input type="text" name="first_name" x-model="editUserData.first_name" required
                                                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-2xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 px-1">Middle Name</label>
                                                    <input type="text" name="middle_name" x-model="editUserData.middle_name"
                                                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-2xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 px-1">Last Name</label>
                                                    <input type="text" name="last_name" x-model="editUserData.last_name" required
                                                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-2xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 px-1">Email Address</label>
                                                    <input type="email" name="email" x-model="editUserData.email" required
                                                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-2xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 px-1">System Role</label>
                                                    <select name="role" x-model="editUserData.role" required
                                                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-2xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none cursor-pointer transition-all">
                                                        <option value="student">STUDENT</option>
                                                        <option value="professor">PROFESSOR</option>
                                                        <option value="admin">ADMIN</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div x-show="editUserData.role === 'student'"
                                                x-transition:enter="transition ease-out duration-200"
                                                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-5 bg-gray-50/80 border border-gray-100 rounded-2xl mb-6">
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 px-1">Program</label>
                                                    <input type="text" name="program" x-model="editUserData.program" placeholder="e.g. BSCS"
                                                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 px-1">Year Level</label>
                                                    <input type="number" name="year_level" x-model="editUserData.year_level" placeholder="e.g. 3"
                                                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 px-1">Section</label>
                                                    <input type="text" name="section" x-model="editUserData.section" placeholder="e.g. A"
                                                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none transition-all">
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 px-1">Status</label>
                                                    <select name="status" x-model="editUserData.status"
                                                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-bold text-[#383838] focus:ring-2 focus:ring-[#383838] outline-none cursor-pointer transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                                        <option value="enrolled">Enrolled</option>
                                                        <option value="dropped">Dropped</option>
                                                        <option value="graduated" :disabled="editUserData.year_level >= 1 && editUserData.year_level <= 3">Graduated</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-gray-50">
                                                <button type="button" @click="showEditModal = false"
                                                    class="px-6 py-3 rounded-2xl text-[11px] uppercase tracking-widest text-gray-500 font-black hover:bg-gray-100 transition-colors">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                    class="px-8 py-3 rounded-2xl bg-[#383838] text-white text-[11px] uppercase tracking-widest font-black hover:bg-black shadow-lg shadow-gray-200 transition-all flex items-center justify-center gap-2">
                                                    <i class="ri-save-3-line text-sm"></i> Update Account
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Activity Timeline Modal -->
                        <template x-teleport="body">
                            <div x-show="logModalOpen"
                                class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4"
                                x-transition.opacity x-cloak>

                                <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl flex flex-col max-h-[85vh]"
                                    @click.away="closeActivityTimeline()">
                                    <div class="p-6 border-b flex justify-between items-center bg-white rounded-t-2xl">
                                        <div>
                                            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Activity Timeline</h3>
                                            <p class="text-xs sm:text-sm text-gray-500">History for <span
                                                    class="text-black font-bold" x-text="selectedUserName"></span></p>
                                        </div>
                                        <button @click="closeActivityTimeline()"
                                            class="text-gray-400 hover:text-gray-600 p-2 transition">
                                            <i class="ri-close-line text-2xl"></i>
                                        </button>
                                    </div>

                                    <div class="p-4 sm:p-6 overflow-y-auto bg-gray-50/50 flex-1">
                                        <template x-if="loading">
                                            <div class="flex flex-col items-center justify-center py-20 gap-3">
                                                <i class="ri-loader-4-line animate-spin text-4xl text-gray-500"></i>
                                                <p class="text-gray-400 text-sm font-medium">Syncing timeline...</p>
                                            </div>
                                        </template>

                                        <div class="space-y-8 relative">
                                            <div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-gray-200 hidden sm:block"></div>

                                            <template x-for="(group, date) in groupedLogs" :key="date">
                                                <div class="relative">
                                                    <div class="sticky top-0 z-20 mb-6 flex">
                                                        <span
                                                            class="bg-white border border-gray-200 text-gray-800 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-sm"
                                                            x-text="formatDateHeader(date)"></span>
                                                    </div>

                                                    <div class="space-y-4 sm:ml-4">
                                                        <template x-for="log in group" :key="log.id">
                                                            <div
                                                                class="bg-white p-4 sm:p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row items-start gap-4 sm:gap-5 hover:border-gray-300 transition-all relative group">
                                                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 z-10 shadow-sm"
                                                                    :class="getIconClass(log.log_type, log.content)">
                                                                    <i :class="getIcon(log.log_type, log.content)"
                                                                        class="text-xl"></i>
                                                                </div>

                                                                <div class="flex-1 min-w-0 pt-0.5 w-full">
                                                                    <div
                                                                        class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                                                                        <div>
                                                                            <p class="text-sm font-black text-gray-900 leading-tight"
                                                                                x-text="log.content"></p>
                                                                            <p class="text-[9px] mt-1.5 text-gray-400 font-bold uppercase tracking-widest"
                                                                                x-text="log.log_type === 'attendance' ? 'Verified Check-in' : 
                                                                    log.log_type === 'submission' ? 'Task Submission' : 
                                                                    log.log_type === 'material' ? 'Courseware Access' : 
                                                                    log.log_type === 'quiz' ? 'Assessment Activity' :
                                                                    log.log_type === 'professor_session' ? 'Session Management' :
                                                                    log.log_type === 'screen_share' ? 'Screen Sharing' :
                                                                    log.log_type === 'professor_activity' ? 'Instructor Activity' : 'Navigation Log'">
                                                                            </p>
                                                                        </div>
                                                                        <span
                                                                            class="text-[10px] font-black text-gray-800 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100 self-start sm:self-auto"
                                                                            x-text="formatTime(log.created_at)"></span>
                                                                    </div>

                                                                    <div
                                                                        class="mt-4 flex flex-wrap items-center gap-3 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                                                        <template
                                                                            x-if="log.log_type !== 'attendance' && log.log_type !== 'professor_activity' && !((log.log_type === 'professor_session' || log.log_type === 'screen_share') && !log.content.includes('ended') && !log.content.includes('stopped'))">
                                                                            <span
                                                                                class="flex items-center gap-1.5 bg-gray-50 px-3 py-1 rounded-lg">
                                                                                <i
                                                                                    class="ri-time-line text-gray-400"></i>
                                                                                <span
                                                                                    x-text="log.duration_seconds + 's'"></span>
                                                                            </span>
                                                                        </template>
                                                                        <span
                                                                            class="flex items-center gap-1.5 bg-gray-50 px-3 py-1 rounded-lg truncate max-w-[250px]">
                                                                            <i
                                                                                class="ri-book-open-line text-gray-400"></i>
                                                                            <span class="truncate"
                                                                                x-text="log.class_name || 'General Session'"></span>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            <template x-if="Object.keys(groupedLogs).length === 0 && !loading">
                                                <div
                                                    class="text-center py-20 bg-white border-2 border-dashed border-gray-100 rounded-[2rem] flex flex-col items-center justify-center">
                                                    <div
                                                        class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4">
                                                        <i class="ri-history-line text-2xl text-gray-300"></i>
                                                    </div>
                                                    <h4 class="text-gray-900 font-bold mb-1">No Activity Found</h4>
                                                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">
                                                        This user has no recorded activity.</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Student Cloud Storage Explorer Modal -->
                        <template x-teleport="body">
                            <div x-show="driveModalOpen"
                                class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4"
                                x-transition.opacity x-cloak>

                                <div class="bg-white w-full max-w-4xl rounded-[2.5rem] shadow-2xl flex flex-col h-[80vh] overflow-hidden border border-gray-100"
                                    @click.away="closeDrive()">

                                    <div class="p-6 sm:p-8 border-b flex justify-between items-center bg-white">
                                        <div>
                                            <h3 class="text-lg sm:text-xl font-black text-gray-900 tracking-tight uppercase">
                                                Student Cloud Storage</h3>
                                            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">
                                                Repository Explorer for: <span class="text-black font-black"
                                                    x-text="driveStudentName"></span>
                                            </p>
                                        </div>
                                        <button @click="closeDrive()"
                                            class="text-gray-400 hover:text-gray-600 p-2 transition rounded-full hover:bg-gray-50">
                                            <i class="ri-close-line text-2xl"></i>
                                        </button>
                                    </div>

                                    <div
                                        class="px-6 sm:px-8 py-3 bg-gray-50 border-b border-gray-100 flex items-center gap-2 overflow-x-auto text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">
                                        <button @click="resetDrivePath()"
                                            class="hover:text-black transition flex items-center gap-1 text-[#383838]">
                                            <i class="ri-home-4-line"></i> Root
                                        </button>

                                        <template x-if="drivePath.year">
                                            <div class="flex items-center gap-2">
                                                <i class="ri-arrow-right-s-line text-gray-300"></i>
                                                <button @click="drivePath.semester = null; drivePath.subject = null"
                                                    class="hover:text-black transition"
                                                    x-text="drivePath.year"></button>
                                            </div>
                                        </template>

                                        <template x-if="drivePath.semester">
                                            <div class="flex items-center gap-2">
                                                <i class="ri-arrow-right-s-line text-gray-300"></i>
                                                <button @click="drivePath.subject = null"
                                                    class="hover:text-black transition"
                                                    x-text="drivePath.semester === 1 ? '1st Semester' : '2nd Semester'"></button>
                                            </div>
                                        </template>

                                        <template x-if="drivePath.subject">
                                            <div class="flex items-center gap-2">
                                                <i class="ri-arrow-right-s-line text-gray-300"></i>
                                                <span class="text-gray-400 truncate max-w-[200px]"
                                                    x-text="drivePath.subject"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="p-6 sm:p-8 overflow-y-auto flex-1 bg-white">
                                        <template x-if="loadingDrive">
                                            <div class="flex flex-col items-center justify-center py-24 gap-3">
                                                <div
                                                    class="w-10 h-10 border-4 border-[#383838] border-t-transparent rounded-full animate-spin">
                                                </div>
                                                <p class="text-gray-400 text-xs font-black uppercase tracking-widest mt-2">
                                                    Mounting storage volumes...</p>
                                            </div>
                                        </template>

                                        <template x-if="!loadingDrive">
                                            <div>
                                                <template x-if="getFolderContents().length === 0">
                                                    <div
                                                        class="text-center py-20 text-gray-400 border border-dashed border-gray-200 rounded-3xl bg-gray-50/50">
                                                        <i class="ri-folder-open-line text-5xl text-gray-300 block mb-2"></i>
                                                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">
                                                            Empty Directory</p>
                                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                            No tracked items or submissions exist at this level.</p>
                                                    </div>
                                                </template>

                                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                                    <template x-for="item in getFolderContents()" :key="item.id">
                                                        <div @click="handleItemClick(item)"
                                                            class="p-4 sm:p-5 rounded-2xl border border-gray-100 bg-gray-50/40 hover:bg-white hover:border-gray-300 hover:shadow-sm transition-all cursor-pointer flex flex-col justify-between group h-36">

                                                            <div class="flex justify-between items-start">
                                                                <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-xl flex items-center justify-center text-xl sm:text-2xl"
                                                                    :class="item.is_file ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-[#383838] group-hover:bg-[#383838] group-hover:text-white transition-colors'">
                                                                    <i :class="item.is_file ? 'ri-file-text-line' : 'ri-folder-5-fill'"></i>
                                                                </div>

                                                                <template x-if="item.is_file">
                                                                    <a :href="item.file_url" download
                                                                        class="text-gray-400 hover:text-black p-1 transition rounded-md hover:bg-gray-100"
                                                                        @click.stopPropagation>
                                                                        <i class="ri-download-2-line text-base"></i>
                                                                    </a>
                                                                </template>
                                                            </div>

                                                            <div class="mt-4 min-w-0">
                                                                <p class="text-xs font-black text-gray-800 truncate group-hover:text-black"
                                                                    x-text="item.name"></p>
                                                                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5"
                                                                    x-text="item.is_file ? item.date : 'Directory'">
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
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
                filterStatus: '',

                logModalOpen: false,
                loading: false,
                selectedUserName: '',
                selectedUserId: null,
                logs: [],
                logRefreshInterval: null,

                driveModalOpen: false,
                loadingDrive: false,
                driveStudentName: '',
                rawDriveFiles: [],
                drivePath: {
                    year: null,
                    semester: null,
                    subject: null
                },

                init() {
                    this.$watch('filterRole', value => {
                        if (value === 'professor' || value === 'admin') {
                            this.filterProgram = '';
                            this.filterYear = '';
                            this.filterSection = '';
                            this.filterStatus = '';
                        }
                    });

                    this.$watch('logModalOpen', isOpen => {
                        if (!isOpen) {
                            this.stopTimelineRefresh();
                        }
                    });
                },

                get filteredUsers() {
                    return this.users.filter(user => {
                        const firstName = user.first_name || '';
                        const lastName = user.last_name || '';
                        const fullName = `${firstName} ${lastName}`.toLowerCase();

                        const matchesSearch = this.search === '' || fullName.includes(this.search.toLowerCase());
                        const matchesRole = this.filterRole === '' || user.role === this.filterRole;
                        const matchesProgram = this.filterProgram === '' || (user.program || '') === this.filterProgram;
                        const matchesYear = this.filterYear === '' || String(user.year_level || '') === this.filterYear;
                        const matchesSection = this.filterSection === '' || (user.section || '') === this.filterSection;
                        const matchesStatus = this.filterStatus === '' || (user.status || '') === this.filterStatus;

                        return matchesSearch && matchesRole && matchesProgram && matchesYear && matchesSection && matchesStatus;
                    });
                },

                async openStudentDrive(userId, firstName, lastName) {
                    this.driveStudentName = `${lastName}, ${firstName}`;
                    this.driveModalOpen = true;
                    this.loadingDrive = true;
                    this.resetDrivePath();

                    try {
                        const response = await fetch(`/admin/users/${userId}/drive-files`);
                        this.rawDriveFiles = await response.json();
                    } catch (err) {
                        console.error("Cloud Node Mount Failed", err);
                        this.rawDriveFiles = [];
                    } finally {
                        this.loadingDrive = false;
                    }
                },

                resetDrivePath() {
                    this.drivePath.year = null;
                    this.drivePath.semester = null;
                    this.drivePath.subject = null;
                },

                closeDrive() {
                    this.driveModalOpen = false;
                    this.rawDriveFiles = [];
                },

                getFolderContents() {
                    if (!this.drivePath.year) {
                        return ['1st Year', '2nd Year', '3rd Year', '4th Year'].map((year, index) => ({
                            id: 'year-' + (index + 1),
                            name: year,
                            is_file: false,
                            value: year
                        }));
                    }

                    if (!this.drivePath.semester) {
                        return [
                            { id: 'sem-1', name: '1st Semester', is_file: false, value: 1 },
                            { id: 'sem-2', name: '2nd Semester', is_file: false, value: 2 }
                        ];
                    }

                    const yearMapping = { '1st Year': 1, '2nd Year': 2, '3rd Year': 3, '4th Year': 4 };
                    const numericYear = yearMapping[this.drivePath.year];

                    const scopeFiles = this.rawDriveFiles.filter(f =>
                        parseInt(f.year_level) === numericYear &&
                        parseInt(f.semester) === parseInt(this.drivePath.semester)
                    );

                    if (!this.drivePath.subject) {
                        const uniqueSubjects = [...new Set(scopeFiles.map(f => f.subject_code))];
                        return uniqueSubjects.map((subCode, index) => ({
                            id: 'sub-' + index,
                            name: subCode,
                            is_file: false,
                            value: subCode
                        }));
                    }

                    return scopeFiles
                        .filter(f => f.subject_code === this.drivePath.subject)
                        .map(f => ({
                            id: f.id,
                            name: f.file_name,
                            is_file: true,
                            file_url: f.file_url,
                            date: new Date(f.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                        }));
                },

                handleItemClick(item) {
                    if (item.is_file) {
                        window.open(item.file_url, '_blank');
                        return;
                    }

                    if (!this.drivePath.year) {
                        this.drivePath.year = item.value;
                    } else if (!this.drivePath.semester) {
                        this.drivePath.semester = item.value;
                    } else if (!this.drivePath.subject) {
                        this.drivePath.subject = item.value;
                    }
                },

                async fetchActivityLogs(userId, showLoader = false) {
                    const user = this.users.find(u => u.id === userId);

                    if (showLoader) {
                        this.loading = true;
                    }

                    try {
                        const response = await fetch(`/admin/users/${userId}/activity-logs`);
                        let fetchedLogs = await response.json();
                        if (user && user.attendances) {
                            user.attendances.forEach(att => {
                                fetchedLogs.push({
                                    id: 'att-' + att.id,
                                    log_type: 'attendance',
                                    content: 'Official Attendance Marked',
                                    class_name: (att.lab_session ? att.lab_session.subject_name : null) || (att.labSession ? att.labSession.subject_name : null) || 'Academic Session',
                                    duration_seconds: 0,
                                    created_at: `${att.attendance_date} ${att.joined_at}`
                                });
                            });
                        }
                        this.logs = fetchedLogs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                    } catch (e) {
                        console.error("Log Fetch Failed", e);
                    } finally {
                        if (showLoader) {
                            this.loading = false;
                        }
                    }
                },
                async viewActivity(userId, userName) {
                    const user = this.users.find(u => u.id === userId);
                    this.selectedUserId = userId;
                    this.selectedUserName = user ? `${user.last_name}, ${user.first_name}` : userName;
                    this.logModalOpen = true;
                    this.logs = [];
                    this.stopTimelineRefresh();
                    await this.fetchActivityLogs(userId, true);
                    this.logRefreshInterval = setInterval(() => {
                        if (this.logModalOpen && this.selectedUserId) {
                            this.fetchActivityLogs(this.selectedUserId);
                        }
                    }, 3000);
                },
                closeActivityTimeline() {
                    this.logModalOpen = false;
                    this.selectedUserId = null;
                    this.stopTimelineRefresh();
                },
                stopTimelineRefresh() {
                    if (this.logRefreshInterval) {
                        clearInterval(this.logRefreshInterval);
                        this.logRefreshInterval = null;
                    }
                },
                get groupedLogs() {
                    return this.logs.reduce((groups, log) => {
                        const date = log.created_at.split(/[ T]/)[0];
                        if (!groups[date]) groups[date] = [];
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
                    return new Date(dateStr).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                },
                formatTime(dateStr) {
                    return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                },
                getIcon(type, content = '') {
                    if (type === 'attendance') return 'ri-checkbox-circle-line';
                    if (type === 'navigation') return 'ri-global-line';
                    if (type === 'submission') return 'ri-file-upload-line';
                    if (type === 'material') return 'ri-book-open-line';
                    if (type === 'quiz') return 'ri-task-line';
                    if (type === 'professor_session') return 'ri-broadcast-line';
                    if (type === 'screen_share') return 'ri-projector-2-line';
                    if (type === 'professor_activity') {
                        if (content.includes('Posted')) return 'ri-add-circle-line';
                        if (content.includes('Updated') || content.includes('Edited')) return 'ri-edit-circle-line';
                        if (content.includes('Deleted')) return 'ri-delete-bin-line';
                        return 'ri-briefcase-line';
                    }
                    return 'ri-cursor-line';
                },
                getIconClass(type, content = '') {
                    if (type === 'attendance') return 'bg-green-50 text-green-600 border border-green-200';
                    if (type === 'navigation') return 'bg-amber-50 text-amber-600 border border-amber-200';
                    if (type === 'submission') return 'bg-blue-50 text-blue-600 border border-blue-200';
                    if (type === 'material') return 'bg-purple-50 text-purple-600 border border-purple-200';
                    if (type === 'quiz') return 'bg-indigo-50 text-indigo-600 border border-indigo-200';
                    if (type === 'professor_session') return 'bg-red-50 text-red-600 border border-red-200';
                    if (type === 'screen_share') return 'bg-orange-50 text-orange-600 border border-orange-200';
                    if (type === 'professor_activity') {
                        if (content.includes('Posted')) return 'bg-green-50 text-green-600 border border-green-200';
                        if (content.includes('Updated') || content.includes('Edited')) return 'bg-blue-50 text-blue-600 border border-blue-200';
                        if (content.includes('Deleted')) return 'bg-red-50 text-red-600 border border-red-200';
                        return 'bg-cyan-50 text-cyan-600 border border-cyan-200';
                    }
                    return 'bg-gray-100 text-gray-600';
                }
            }
        }
    </script>
</x-app-layout>