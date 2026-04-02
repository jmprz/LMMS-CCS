<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100">

        <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0">
            <nav class="mt-8 px-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-[#383838] text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }}">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>
                <a href="{{ route('admin.classroom') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.classroom') ? 'bg-black text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="ri-graduation-cap-line mr-3 text-lg"></i> Classroom
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

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Classes</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $activeClassesCount }}</h3>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i class="ri-terminal-box-line text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Students</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $totalStudents }}</h3>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                            <i class="ri-user-line text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Professors</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $totalProfessors }}</h3>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                            <i class="ri-shield-user-line text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Users</p>
                            <h3 class="text-3xl font-black text-gray-800">{{ $totalUsers }}</h3>
                        </div>
                        <div class="p-3 bg-orange-50 text-orange-600 rounded-lg">
                            <i class="ri-group-line text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="font-bold text-lg text-gray-800">Upcoming Class Schedule</h2>
                            <span class="text-xs font-bold bg-gray-100 text-gray-600 px-3 py-1 rounded-full uppercase">Next 24 Hours</span>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-xs font-bold text-gray-400 uppercase border-b border-gray-100">
                                        <th class="pb-3">Subject</th>
                                        <th class="pb-3">Section</th>
                                        <th class="pb-3">Time</th>
                                        <th class="pb-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @forelse($upcomingClasses as $upcoming)
                                    <tr>
                                        <td class="py-4 font-bold text-gray-700">{{ $upcoming->subject_name }}</td>
                                        <td class="py-4 text-gray-600">{{ $upcoming->program }} {{ $upcoming->year_level }}-{{ $upcoming->section }}</td>
                                        <td class="py-4 text-gray-600">{{ $upcoming->schedule_time }}</td>
                                        <td class="py-4 text-right">
                                            <span class="text-[10px] font-bold bg-yellow-50 text-yellow-600 px-2 py-1 rounded-md uppercase">Scheduled</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-gray-400 italic">No upcoming classes found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h2 class="font-bold text-lg text-gray-800 mb-4">Activity Logs</h2>
                        <div class="space-y-6 overflow-y-auto max-h-[400px] pr-2">
                            @forelse($logs as $log)
                            <div class="relative pl-6 border-l-2 {{ $log->type == 'alert' ? 'border-red-400' : 'border-gray-200' }}">
                                <div class="absolute -left-[9px] top-0 w-4 h-4 bg-white border-2 {{ $log->type == 'alert' ? 'border-red-400' : 'border-gray-300' }} rounded-full"></div>
                                <p class="text-sm font-bold text-gray-800 leading-tight">{{ $log->description }}</p>
                                <p class="text-[11px] text-gray-400 mt-1 uppercase">{{ $log->created_at->diffForHumans() }} • {{ $log->user_name ?? 'System' }}</p>
                            </div>
                            @empty
                            <div class="text-center py-8 text-gray-400 italic text-sm">No recent logs.</div>
                            @endforelse
                        </div>
                        <a href="#" class="block text-center mt-6 text-xs font-bold text-gray-400 hover:text-[#383838] transition uppercase tracking-widest">View All Logs</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>