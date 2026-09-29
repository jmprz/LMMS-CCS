<x-app-layout>
     <x-slot name="header"></x-slot>
    <div id="lockdown-ui" class="hidden fixed inset-0 z-[9999] bg-white w-full h-screen">
        <div class="flex w-full h-full">
            <div class="w-1/2 h-full bg-black relative">
                <video id="professor-screen" autoplay playsinline
                    class="absolute inset-0 w-full h-full object-contain"></video>
            </div>

            <div
                class="w-1/2 h-full p-8 overflow-y-auto bg-gray-50 border-l border-gray-200 flex flex-col justify-between">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 mb-4 uppercase tracking-tight">Workspace Locked</h2>
                    <p class="text-gray-500 font-medium">The professor has restricted your device viewport. Please track
                        the presentation broadcast shown on the left panel display.</p>
                </div>
                <div class="pt-6 border-t border-gray-200">
                    <span
                        class="inline-flex items-center text-[10px] font-black text-amber-600 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg uppercase tracking-wider">
                        <i class="ri-broadcast-line mr-1.5 animate-pulse text-sm"></i> Live Monitoring Active
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div id="dashboard-root" class="fixed inset-x-0 bottom-0 top-20 flex bg-gray-50 overflow-hidden" x-data="{ 
    sidebarOpen: false,
    search: '',
    status: 'all',
    viewerOpen: false,
    viewerTitle: '',
    viewerType: '',
    viewerUrl: '',
    viewerSource: '',
    engagementId: null,
    activeSeconds: 0,
    secondTimer: null,
    heartbeatTimer: null,

    init() {
        window.addEventListener('beforeunload', () => this.flushEngagement());
    },

    async openRecommendation(recommendationId) {
        if (this.viewerOpen) {
            await this.closeRecommendation(false);
        }

        try {
            const response = await fetch(`{{ url('/student/roadmap') }}/${recommendationId}/engagement/start`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                }
            });

            if (!response.ok) throw new Error('Unable to open the recommended resource.');

            const data = await response.json();
            this.engagementId = data.engagement_id;
            this.viewerTitle = data.resource.title;
            this.viewerType = data.resource.type;
            this.viewerUrl = data.resource.url;
            this.viewerSource = data.resource.source;
            this.activeSeconds = 0;
            this.viewerOpen = true;

            // PDF/YouTube can be viewed inside LMMS and timed.
            // Normal external URLs may block iframe embedding (X-Frame-Options/CSP),
            // so they are opened manually in a new tab and are not timed.
            if (this.viewerType !== 'url') {
                this.startEngagementTimers();
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Unable to open resource.');
        }
    },

    startEngagementTimers() {
        this.stopEngagementTimers();

        this.secondTimer = setInterval(() => {
            if (this.viewerOpen && document.visibilityState === 'visible') {
                this.activeSeconds++;
            }
        }, 1000);

        this.heartbeatTimer = setInterval(() => {
            this.sendHeartbeat();
        }, 15000);
    },

    stopEngagementTimers() {
        if (this.secondTimer) clearInterval(this.secondTimer);
        if (this.heartbeatTimer) clearInterval(this.heartbeatTimer);
        this.secondTimer = null;
        this.heartbeatTimer = null;
    },

    async sendHeartbeat() {
        if (!this.engagementId) return;

        try {
            await fetch(`{{ url('/student/roadmap/engagement') }}/${this.engagementId}/heartbeat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ duration_seconds: this.activeSeconds }),
            });
        } catch (error) {
            console.warn('Recommendation engagement heartbeat failed.', error);
        }
    },

    flushEngagement() {
        if (!this.engagementId) return;
        const form = new FormData();
        form.append('_token', document.querySelector('meta[name=csrf-token]').content);
        form.append('duration_seconds', this.activeSeconds);
        navigator.sendBeacon(`{{ url('/student/roadmap/engagement') }}/${this.engagementId}/end`, form);
    },

    async closeRecommendation(refresh = true) {
        if (!this.viewerOpen && !this.engagementId) return;

        this.stopEngagementTimers();
        const id = this.engagementId;
        const seconds = this.activeSeconds;

        this.viewerOpen = false;
        this.viewerUrl = '';
        this.engagementId = null;

        if (id) {
            try {
                await fetch(`{{ url('/student/roadmap/engagement') }}/${id}/end`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ duration_seconds: seconds }),
                });
            } catch (error) {
                console.warn('Recommendation engagement close failed.', error);
            }
        }

        if (refresh) window.location.reload();
    },

    async openExternalResource() {
        if (!this.viewerUrl) return;

        window.open(this.viewerUrl, '_blank', 'noopener,noreferrer');

        // Record this as an opened recommendation, but do not claim
        // active time once the student leaves LMMS.
        await this.closeRecommendation();
    },

    embeddedViewerUrl() {
        if (!this.viewerUrl) return '';

        if (this.viewerType !== 'youtube') {
            return this.viewerUrl;
        }

        try {
            const url = new URL(this.viewerUrl);

            if (url.hostname.includes('youtu.be')) {
                const videoId = url.pathname.replace(/^\//, '').split('/')[0];
                return videoId ? `https://www.youtube.com/embed/${videoId}` : this.viewerUrl;
            }

            if (url.hostname.includes('youtube.com')) {
                if (url.pathname.startsWith('/embed/')) {
                    return this.viewerUrl;
                }

                const videoId = url.searchParams.get('v');
                if (videoId) {
                    return `https://www.youtube.com/embed/${videoId}`;
                }

                const shortsMatch = url.pathname.match(/^\/shorts\/([^/?]+)/);
                if (shortsMatch) {
                    return `https://www.youtube.com/embed/${shortsMatch[1]}`;
                }
            }
        } catch (error) {
            console.warn('Unable to normalize YouTube URL.', error);
        }

        return this.viewerUrl;
    },

    formatEngagement(seconds) {
        seconds = Number(seconds || 0);
        if (seconds < 60) return `${seconds}s`;
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        if (mins < 60) return `${mins}m ${secs}s`;
        const hrs = Math.floor(mins / 60);
        return `${hrs}h ${mins % 60}m`;
    },

    classes: @js($joinedClasses->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->subject_name,
                'code' => $item->class_code,
                'program' => $item->program ?? 'N/A',
                'year' => $item->year_level ?? 'N/A',
                'section' => $item->section ?? 'N/A',
                'instructor' => $item->faculty->name,
                'day' => $item->schedule_day,
                'time' => $item->schedule_time,
                'attendance' => $item->total_attended_days ?? 0,
                'isOpen' => (bool) $item->is_active,
                'route' => route('student.subject', $item->id)
            ])),

            isScheduleActive(cls) {
                const now = new Date();
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const currentDay = days[now.getDay()];
                
                if (cls.day !== currentDay) return false;

                try {
                    const [startStr, endStr] = cls.time.split(' - ');
                    const parseTime = (timeStr) => {
                        const [time, modifier] = timeStr.split(' ');
                        let [hours, minutes] = time.split(':');
                        if (modifier === 'PM' && hours < 12) hours = parseInt(hours) + 12;
                        if (modifier === 'AM' && hours == 12) hours = 0;
                        const d = new Date();
                        d.setHours(hours, minutes, 0);
                        return d;
                    };
                    return now >= parseTime(startStr) && now <= parseTime(endStr);
                } catch (e) {
                    return false;
                }
            },

            get activeClasses() {
                return this.classes.filter(c => c.isOpen && this.isScheduleActive(c));
            },

            get offlineClasses() {
                return this.classes.filter(c => !c.isOpen || !this.isScheduleActive(c));
            },

            // Directly triggers screen share protocol and navigates seamlessly
           enterClassroomDirectly(route) {
        // Only run screen share protocol if NOT on mobile/tablet
        if (!this.isMobile() && typeof enterClassroom === 'function') {
        enterClassroom();
        }
     window.location.href = route;
    },
        isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) 
        || (window.innerWidth <= 1024 && navigator.maxTouchPoints > 0);
            }
         }">

         <div x-show="sidebarOpen" 
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-30 bg-black/50 lg:hidden"
             x-cloak>
        </div>

      <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed lg:static z-40 w-64 border-r border-gray-200 bg-white flex-shrink-0 flex flex-col justify-between h-full transition-transform duration-200 ease-in-out">
            <div class="flex flex-col flex-grow overflow-y-auto">

                <nav class="mt-8 px-4 space-y-1">
                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Workspace
                    </div>
                      <a href="{{ route('student.dashboard') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('student.dashboard') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>
                 <a href="{{ route('student.roadmap.index') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('student.roadmap.index') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                   <i class="ri-route-line mr-3 text-lg"></i> My Learning Roadmap
                </a>
                </nav>

                <nav class="mt-6 px-4 space-y-1">
                    <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">My Courses
                    </div>

                    <template x-for="cls in classes" :key="cls.id">
                        <div>
                            <a href="#"
                                @click.prevent="if(cls.isOpen && isScheduleActive(cls)) { enterClassroomDirectly(cls.route) }"
                                :class="cls.isOpen && isScheduleActive(cls) 
                               ? 'text-gray-700 hover:bg-green-50/60 border border-transparent cursor-pointer' 
                               : 'text-gray-400 opacity-60 cursor-not-allowed pointer-events-none border border-transparent'"
                                class="flex items-start justify-between py-3 px-4 rounded-xl text-xs transition duration-150">

                                <div class="flex items-start min-w-0 mr-2">
                                    <i :class="cls.isOpen && isScheduleActive(cls) ? 'ri-book-3-line text-green-600' : 'ri-lock-line'"
                                        class="text-lg mr-3 flex-shrink-0 mt-0.5"></i>

                                    <div class="flex flex-col min-w-0">
                                        <span class="truncate font-black text-xs tracking-tight uppercase"
                                            :class="cls.isOpen && isScheduleActive(cls) ? 'text-gray-800' : 'text-gray-500'"
                                            x-text="cls.code + ' | ' + cls.program + ' ' + ' - ' + cls.year + cls.section"></span>
                                        <span class="text-[10px] font-bold text-gray-400 truncate mt-1 tracking-wide"
                                            x-text="cls.day + ' • ' + cls.time"></span>
                                    </div>
                                </div>

                                <template x-if="cls.isOpen && isScheduleActive(cls)">
                                    <span class="flex h-2 w-2 relative flex-shrink-0 mt-1.5">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                    </span>
                                </template>
                            </a>
                        </div>
                    </template>
                </nav>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50/50 relative" x-data="{ open: false }"
                @click.away="open = false">

                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="transform opacity-0 scale-95 translate-y-2"
                    class="absolute bottom-full left-4 right-4 mb-2 bg-white rounded-2xl border border-gray-200 shadow-xl p-1.5 z-50 flex flex-col gap-0.5"
                    style="display: none;">


                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit"
                            class="w-full text-left flex items-center px-3.5 py-2.5 text-xs font-black text-red-600 hover:bg-red-50 rounded-xl transition duration-150 tracking-wide">
                            <i class="ri-logout-box-r-line mr-2.5 text-base"></i> Sign Out
                        </button>
                    </form>
                </div>

                <div @click="open = !open"
                    class="flex items-center justify-between cursor-pointer group p-1 -m-1 rounded-xl hover:bg-gray-100/50 transition">
                    <div class="flex items-center min-w-0">
                        @php
                            $nameTokens = explode(' ', Auth::user()->name);
                            $firstInitial = substr($nameTokens[0], 0, 1);
                            $lastInitial = count($nameTokens) > 1 ? substr(end($nameTokens), 0, 1) : '';
                            $profileInitials = strtoupper($firstInitial . $lastInitial);
                        @endphp
                        <div
                            class="h-8 w-8 rounded-xl bg-[#383838] group-hover:bg-black flex items-center justify-center text-white text-[10px] font-black uppercase shadow-sm mr-2.5 flex-shrink-0 transition">
                            {{ $profileInitials }}
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-black text-gray-800 truncate leading-none">{{ Auth::user()->name }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-1 leading-none">
                                Student</p>
                        </div>
                    </div>

                    <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
                        :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
                </div>

            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full p-6 md:p-10">
           <!-- Mobile Header Toggle Bar -->
<div class="lg:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 -mt-6 -mx-6 md:-mt-10 md:-mx-10 mb-6 flex-shrink-0">
    <div class="flex items-center gap-2">
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition">
            <i class="ri-menu-2-line text-xl"></i>
        </button>
        <span class="text-xs font-black uppercase text-gray-700 tracking-wider"></span>
    </div>

    <!-- Right Side Profile & Sign Out Dropdown -->
    <div class="relative" x-data="{ userMenuOpen: false }" @click.away="userMenuOpen = false">
        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-1.5 p-1 rounded-xl hover:bg-gray-100 transition focus:outline-none">
            @php
                $nameTokens = explode(' ', Auth::user()->name);
                $firstInitial = substr($nameTokens[0], 0, 1);
                $lastInitial = count($nameTokens) > 1 ? substr(end($nameTokens), 0, 1) : '';
                $profileInitials = strtoupper($firstInitial . $lastInitial);
            @endphp
            <div class="h-8 w-8 rounded-xl bg-[#383838] flex items-center justify-center text-white text-[10px] font-black uppercase shadow-sm flex-shrink-0">
                {{ $profileInitials }}
            </div>
            <i class="ri-arrow-down-s-line text-gray-400 text-sm transition" :class="userMenuOpen ? 'transform rotate-180 text-gray-700' : ''"></i>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="userMenuOpen"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="transform opacity-0 scale-95 -translate-y-2"
             class="absolute right-0 mt-2 w-48 bg-white rounded-2xl border border-gray-200 shadow-xl p-1.5 z-50 flex flex-col gap-1"
             x-cloak
             style="display: none;">
            
            <div class="px-3 py-2 border-b border-gray-100">
                <p class="text-xs font-black text-gray-800 truncate leading-none">{{ Auth::user()->name }}</p>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-1 leading-none">Student</p>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit"
                    class="w-full text-left flex items-center px-3 py-2 text-xs font-black text-red-600 hover:bg-red-50 rounded-xl transition duration-150 tracking-wide">
                    <i class="ri-logout-box-r-line mr-2 text-base"></i> Sign Out
                </button>
            </form>
        </div>
    </div>
</div>

            <div class="max-w-7xl mx-auto space-y-7 pb-16">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Personalized Learning</p>
                        <h1 class="mt-1 text-2xl md:text-3xl font-black uppercase tracking-tight text-gray-900">My Learning Roadmap</h1>
                        <p class="mt-2 text-sm text-gray-500">Review learning resources recommended from your quiz results.</p>
                    </div>
                </div>

                @if(session('success'))
                    <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-bold text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if(!$accessAllowed)
                    <div class="rounded-3xl border border-gray-200 bg-white p-10 text-center shadow-sm">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500">
                            <i class="ri-lock-2-line text-2xl"></i>
                        </div>
                        <h2 class="text-lg font-black uppercase text-gray-800">Roadmap Not Enabled</h2>
                        <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">
                            Personalized recommendations are not currently enabled for this account.
                            Your normal classroom materials, tasks and quizzes remain available.
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Recommendations</p>
                            <p class="mt-2 text-2xl font-black text-gray-900">{{ $stats['total'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">To Review</p>
                            <p class="mt-2 text-2xl font-black text-amber-600">{{ $stats['pending'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Opened</p>
                            <p class="mt-2 text-2xl font-black text-green-600">{{ $stats['opened'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Learning Topics</p>
                            <p class="mt-2 text-2xl font-black text-gray-900">{{ $stats['topics'] }}</p>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_220px]">
                            <div class="relative">
                                <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input x-model="search" type="text" placeholder="Search topic, subject, quiz or resource..."
                                       class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 pl-11 pr-4 text-sm font-semibold focus:border-gray-400 focus:ring-gray-200">
                            </div>
                            <select x-model="status" class="rounded-xl border-gray-200 bg-gray-50 text-sm font-bold text-gray-600">
                                <option value="all">All Status</option>
                                <option value="recommended">Not Opened</option>
                                <option value="opened">Opened</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @forelse($recommendations as $recommendation)
                            @php
                                $resource = $recommendation->libraryResource ?? $recommendation->professorMaterial;
                                $resourceTitle = $resource?->title ?? 'Unavailable resource';
                                $resourceType = $resource?->type ?? 'resource';
                                $sourceLabel = $recommendation->learning_resource_id ? 'Resource Library' : 'Professor Material';
                                $viewCount = $recommendation->engagements->count();
                                $totalSeconds = (int) $recommendation->engagements->sum('duration_seconds');
                                $engagementStatus = $viewCount > 0 ? 'opened' : 'recommended';
                                $durationMinutes = intdiv($totalSeconds, 60);
                                $durationRemainder = $totalSeconds % 60;
                                $durationLabel = $durationMinutes > 0
                                    ? $durationMinutes . 'm ' . $durationRemainder . 's'
                                    : $durationRemainder . 's';
                                $searchText = strtolower(implode(' ', [
                                    $recommendation->topic?->name,
                                    $recommendation->labSession?->subject_name,
                                    $recommendation->quizAttempt?->quiz?->title,
                                    $resourceTitle,
                                    $sourceLabel,
                                ]));
                            @endphp

                            <article
                                x-show="('{{ $searchText }}'.includes(search.toLowerCase())) && (status === 'all' || status === '{{ $engagementStatus }}')"
                                x-transition
                                class="rounded-[28px] border border-gray-200 bg-white p-6 shadow-sm transition hover:border-gray-400 hover:shadow-md">

                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-700">
                                        <i class="{{ $resourceType === 'pdf' ? 'ri-file-pdf-line' : ($resourceType === 'youtube' ? 'ri-youtube-line' : 'ri-links-line') }} text-xl"></i>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-[9px] font-black uppercase tracking-wider
                                        {{ $engagementStatus === 'opened' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $engagementStatus === 'opened' ? 'Opened' : 'Recommended' }}
                                    </span>
                                </div>

                                <div class="mt-5">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">{{ $sourceLabel }}</p>
                                    <h3 class="mt-1 text-lg font-black leading-snug text-gray-900">{{ $resourceTitle }}</h3>
                                    <p class="mt-2 text-xs font-bold text-gray-500">{{ $recommendation->topic?->name ?? 'Learning Topic' }}</p>
                                    <p class="mt-1 text-[11px] text-gray-400">{{ $recommendation->labSession?->subject_name }}</p>
                                </div>

                                @if($recommendation->trigger_quiz_percentage !== null)
                                    <div class="mt-5 rounded-2xl bg-gray-50 p-4 border border-gray-100">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">Trigger Quiz Result</span>
                                            <span class="text-sm font-black text-gray-800">{{ number_format((float) $recommendation->trigger_quiz_percentage, 1) }}%</span>
                                        </div>
                                        @if($recommendation->quizAttempt?->quiz)
                                            <p class="mt-1 truncate text-[10px] font-bold text-gray-400">{{ $recommendation->quizAttempt->quiz->title }}</p>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-5 flex gap-2 border-t border-gray-100 pt-4">
                                    <button type="button"
                                        @click="openRecommendation({{ $recommendation->id }})"
                                        class="flex-1 rounded-xl bg-[#383838] px-4 py-3 text-center text-[10px] font-black uppercase tracking-wider text-white hover:bg-black">
                                        <i class="ri-book-open-line mr-1"></i> Open Resource
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full rounded-3xl border-2 border-dashed border-gray-200 bg-white px-6 py-20 text-center">
                                <i class="ri-route-line text-4xl text-gray-200"></i>
                                <h3 class="mt-4 font-black uppercase text-gray-700">No recommendations yet</h3>
                                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-400">
                                    When an eligible pretest identifies a weak learning topic, approved matching resources will appear here automatically.
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </main>

        {{-- Recommendation viewer: tracks active-visible engagement time automatically. --}}
        <template x-teleport="body">
        <div x-show="viewerOpen" x-cloak 
            @keydown.escape.window="closeRecommendation()"
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
            <div class="flex h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-[32px] bg-white shadow-2xl"
                @click.outside="closeRecommendation()">
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-7">
                    <div class="min-w-0">
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-400" x-text="viewerSource"></p>
                        <h2 class="truncate text-lg font-black text-gray-900" x-text="viewerTitle"></h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <div x-show="viewerType !== 'url'" class="rounded-xl bg-green-50 px-3 py-2 text-right">
                            <p class="text-[8px] font-black uppercase tracking-widest text-green-700">Active viewing</p>
                            <p class="text-xs font-black text-green-800" x-text="formatEngagement(activeSeconds)"></p>
                        </div>
                        <button type="button" @click="closeRecommendation()"
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-600 hover:bg-black hover:text-white">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="relative flex-1 bg-gray-100">
                    {{-- PDF and YouTube resources stay inside the LMMS viewer. --}}
                    <iframe
                        x-show="viewerUrl && viewerType !== 'url'"
                        :src="embeddedViewerUrl()"
                        class="h-full w-full border-0 bg-white"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>

                    {{-- Normal websites often refuse iframe embedding. --}}
                    <div x-show="viewerUrl && viewerType === 'url'"
                        class="flex h-full items-center justify-center p-6 sm:p-10">
                        <div class="w-full max-w-xl rounded-3xl border border-gray-200 bg-white p-7 text-center shadow-sm sm:p-10">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-700">
                                <i class="ri-external-link-line text-2xl"></i>
                            </div>
                            <h3 class="mt-5 text-lg font-black text-gray-900">External Resource</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                                This website cannot be displayed safely inside LMMS. Open it in a new browser tab to continue.
                            </p>
                            <button type="button"
                                @click="openExternalResource()"
                                class="mt-6 inline-flex items-center justify-center rounded-xl bg-[#383838] px-6 py-3 text-[10px] font-black uppercase tracking-wider text-white hover:bg-black">
                                <i class="ri-external-link-line mr-2 text-sm"></i>
                                Open External Resource
                            </button>
                            <p class="mt-4 text-[10px] font-semibold text-gray-400">
                                LMMS records that the resource was opened, but does not measure time spent on an external website.
                            </p>
                        </div>
                    </div>

                    <div x-show="!viewerUrl" class="flex h-full items-center justify-center text-sm font-bold text-gray-400">
                        Resource unavailable.
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-white px-5 py-3 sm:px-7">
                    <p x-show="viewerType !== 'url'" class="text-[10px] font-semibold text-gray-400">
                        Time is counted while this LMMS viewer is visible. Switching tabs pauses the active timer.
                    </p>
                    <p x-show="viewerType === 'url'" class="text-[10px] font-semibold text-gray-400">
                        External websites open in a separate tab. LMMS records the open event only.
                    </p>

                    <a x-show="viewerType !== 'url'" :href="embeddedViewerUrl()" target="_blank" rel="noopener noreferrer"
                        class="text-[10px] font-black uppercase tracking-widest text-gray-600 underline hover:text-black">
                        Open externally
                    </a>
                </div>
            </div>
        </div>
        </template>
    </div>
</x-app-layout>
 