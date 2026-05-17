<nav x-data="{ open: false }" class="bg-white border-b border-gray-300 sticky top-0 z-50">
    <div class="max-w-9xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <div class="shrink-0 flex items-center group">
                    <div class="flex items-center space-x-3">
                        <div class="p-1.5 rounded-lg shadow-sm">
                            <img src="{{ asset('/img/ccs_logo.png') }}" class="h-14 w-auto" alt="Logo">
                        </div>
                        <div class="hidden md:flex flex-col">
                            <span class="font-black tracking-tighter text-lg uppercase leading-tight text-gray-900">
                                Learning and Monitoring Management System
                            </span>
                            <span class="font-bold tracking-tighter text-xs uppercase text-gray-500 leading-tight">
                                College of Computing Studies
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center">
                <div class="flex items-center space-x-3 bg-gray-50 px-4 py-2 rounded-xl border border-gray-200 shadow-sm">
                    <div class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                    </div>
                    
                    <div class="flex flex-col text-right">
                        <span id="nav-live-clock" class="font-black text-sm text-gray-800 tracking-tight leading-none">
                            00:00:00 AM
                        </span>
                        <span id="nav-live-date" class="font-bold text-[10px] text-gray-400 uppercase tracking-wider mt-0.5 leading-none">
                            Loading Date...
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        function updateSystemTime() {
            const now = new Date();
            
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: true 
            };
            const dateOptions = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            };

            const clockEl = document.getElementById('nav-live-clock');
            const dateEl = document.getElementById('nav-live-date');
            
            if(clockEl) clockEl.innerText = now.toLocaleTimeString('en-US', timeOptions);
            if(dateEl) dateEl.innerText = now.toLocaleDateString('en-US', dateOptions);
        }

        updateSystemTime();
        setInterval(updateSystemTime, 1000);
    });
</script>