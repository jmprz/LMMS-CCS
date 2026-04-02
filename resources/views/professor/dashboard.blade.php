<x-app-layout>
    <x-slot name="header">
    </x-slot>
    <div class="fixed inset-0 flex bg-gray-100">


        <aside class="w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0">


            <nav class="mt-8 px-4 space-y-2">
                <a href="{{ route('professor.dashboard') }}"
                    class="flex items-center py-3 px-4 rounded-lg bg-[#383838] text-white font-bold">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>
                <a href="{{ route('professor.classroom') }}"
                    class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('professor.classroom') ? 'bg-black text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }} transition">
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Sessions</p>
                            <h3 class="text-3xl font-black text-gray-800">12</h3>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                            <i class="ri-terminal-box-line text-2xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Connected Students</p>
                            <h3 class="text-3xl font-black text-gray-800">148</h3>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                            <i class="ri-user-line text-2xl"></i>
                        </div>
                    </div>

                    <div
                        class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">System Load</p>
                            <h3 class="text-3xl font-black text-gray-800">42%</h3>
                        </div>
                        <div class="p-3 bg-orange-50 text-orange-600 rounded-lg">
                            <i class="ri-dashboard-3-line text-2xl"></i>
                        </div>
                    </div>
                </div>


                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="font-bold text-lg text-gray-800">Student Monitoring</h2>
                            <span
                                class="text-xs font-bold bg-green-100 text-green-700 px-3 py-1 rounded-full">LIVE</span>
                        </div>
                        <div
                            class="h-64 flex items-center justify-center border-2 border-dashed border-gray-200 rounded-lg text-gray-400 font-medium">
                            Monitoring Active...
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h2 class="font-bold text-lg text-gray-800 mb-4">Recent Alerts</h2>
                        <div class="space-y-4">
                            <div class="border-l-2 border-red-500 pl-4">
                                <p class="text-sm font-bold text-gray-800">Attempted Blocked Site</p>
                                <p class="text-xs text-gray-500">Student #402 • 2m ago</p>
                            </div>
                            <div class="border-l-2 border-yellow-500 pl-4">
                                <p class="text-sm font-bold text-gray-800">Connection Latency</p>
                                <p class="text-xs text-gray-500">Room 301 • 15m ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>