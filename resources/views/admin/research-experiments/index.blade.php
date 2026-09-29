<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100 overflow-hidden"
        x-data="{
            sidebarOpen: false,
            createOpen: @js($errors->any() && old('_form') === 'create'),
            settingsOpen: @js($errors->any() && old('_form') === 'settings'),
            groupOpen: false,
            assessmentOpen: false,
            activeSection: 'configuration',
            consentParticipant: null,
            consentName: '',
            consentAction: 'consented'
        }">

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40 md:hidden" style="display: none;">
        </div>

        <!-- Fixed Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed md:static inset-y-0 left-0 z-50 w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)] transform transition-transform duration-300 ease-in-out md:translate-x-0">
            <nav class="mt-8 px-4 space-y-2 overflow-y-auto flex-1">
                <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">System Admin</div>

                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.dashboard') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>

                <a href="{{ route('admin.classroom') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.classroom*') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-folder-5-line mr-3 text-lg"></i> Classroom
                </a>

                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.users*') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-user-line mr-3 text-lg"></i> Users
                </a>

                <a href="{{ route('profile.edit') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('profile.edit') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-settings-5-line mr-3 text-lg"></i> Settings
                </a>

                <div class="mx-4 my-4 border-t border-gray-100"></div>

               <div class="px-4 text-[10px] font-black text-gray-400
            uppercase tracking-widest mb-2">
                    Learning Management
                </div>

                <a href="{{ route('admin.learning-topics.index') }}" class="flex items-center py-2.5 px-4 rounded-xl text-xs transition
                {{ request()->routeIs('admin.learning-topics.*')
                    ? 'bg-[#383838] text-white font-black'
                    : 'text-gray-600 font-bold hover:bg-gray-100' }}">
                                    <i class="ri-price-tag-3-line mr-3 text-lg"></i>
                    Learning Topics
                </a>

                <a href="{{ route('admin.learning-resources.index') }}" class="flex items-center py-2.5 px-4 rounded-xl text-xs transition
                {{ request()->routeIs('admin.learning-resources.*')
                    ? 'bg-[#383838] text-white font-black'
                    : 'text-gray-600 font-bold hover:bg-gray-100' }}">
                                    <i class="ri-book-open-line mr-3 text-lg"></i>
                    Resource Library
                </a>
                <a href="{{ route('admin.research-experiments.index') }}" class="flex items-center py-2.5 px-4 rounded-xl text-xs transition
                {{ request()->routeIs('admin.research-experiments.*')
                    ? 'bg-[#383838] text-white font-black'
                    : 'text-gray-600 font-bold hover:bg-gray-100' }}">
                                <i class="ri-flask-line mr-3 text-lg"></i>
                    Research Experiment
                </a>
                 @if(app()->environment(['local', 'testing']))
                    <a href="{{ route('admin.local-quiz-simulator.index') }}"
                        class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold {{ request()->routeIs('admin.local-quiz-simulator.*') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="ri-test-tube-line mr-3 text-lg"></i>
                        Local Quiz Simulator
                    </a>
                @endif
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
                            <p class="text-xs font-black text-gray-800 truncate leading-snug">{{ Auth::user()->name }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider leading-none mt-0.5">
                                Administrator</p>
                        </div>
                    </div>
                    <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
                        :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
                </button>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full flex flex-col min-w-0">
            <div class="mt-[80px] flex items-center gap-3 border-b bg-white px-4 py-3 md:hidden">
                <button type="button" @click="sidebarOpen=!sidebarOpen" aria-label="Open navigation"><i class="ri-menu-2-line text-xl"></i></button><span class="text-xs font-black uppercase">Research Experiment</span>
            </div>
            <div class="space-y-7 p-4 pb-20 sm:p-8 md:mt-[80px]">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div><p class="text-[10px] font-black uppercase tracking-widest text-gray-400">LMMS Administration / Research</p><h1 class="mt-2 text-2xl font-black uppercase tracking-tight text-[#383838] sm:text-3xl">Experiment Setup</h1><p class="mt-2 text-sm text-gray-500">Configure intervention groups, assessments and verified participation.</p></div>
                    <button type="button" @click="createOpen=true" class="rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase text-white hover:bg-black"><i class="ri-add-line mr-1"></i> New Experiment</button>
                </div>
                @if(session('success'))<div role="status" class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800">{{ session('success') }}</div>@endif
                @if($errors->any())<div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="mb-2 font-black">Please review the setup:</p><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <div class="flex flex-wrap items-end justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4">
                    <form method="GET" action="{{ route('admin.research-experiments.index') }}" class="min-w-0 flex-1 sm:max-w-lg"><label for="experiment-picker" class="mb-1 block text-[10px] font-black uppercase tracking-widest text-gray-400">Select Experiment</label><select id="experiment-picker" name="experiment" onchange="this.form.submit()" class="w-full rounded-xl border-gray-200 text-sm font-semibold"><option value="">Most recent experiment</option>@foreach($experiments as $item)<option value="{{ $item->id }}" @selected($experiment && $experiment->id === $item->id)>{{ $item->title }} ({{ ucfirst($item->status) }})</option>@endforeach</select></form>
                    @if($experiment)<span class="rounded-full px-4 py-2 text-[10px] font-black uppercase tracking-widest {{ $experiment->status === 'active' ? 'bg-green-50 text-green-700' : ($experiment->status === 'paused' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600') }}">{{ $experiment->status }}</span>@endif
                </div>

                @if($experiment)
                    <div class="sticky top-[80px] z-20 rounded-2xl border border-gray-200 bg-white/95 p-2 shadow-sm backdrop-blur">
                        <div class="flex gap-2 overflow-x-auto">
                            @foreach([
                                ['key' => 'configuration', 'label' => 'Configuration', 'icon' => 'ri-settings-3-line'],
                                ['key' => 'groups', 'label' => 'Research Groups', 'icon' => 'ri-group-line'],
                                ['key' => 'assessments', 'label' => 'Assessments', 'icon' => 'ri-survey-line'],
                                ['key' => 'engagement', 'label' => 'Engagement', 'icon' => 'ri-eye-line'],
                                ['key' => 'results', 'label' => 'Results', 'icon' => 'ri-bar-chart-grouped-line'],
                            ] as $tab)
                                <button type="button"
                                    @click="activeSection='{{ $tab['key'] }}'"
                                    :class="activeSection === '{{ $tab['key'] }}'
                                        ? 'bg-[#383838] text-white shadow-sm'
                                        : 'bg-gray-50 text-gray-600 hover:bg-gray-100'"
                                    class="flex min-w-max items-center gap-2 rounded-xl px-4 py-2.5 text-[10px] font-black uppercase tracking-widest transition">
                                    <i class="{{ $tab['icon'] }} text-sm"></i>
                                    {{ $tab['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!$experiment)
                    <div class="rounded-3xl border-2 border-dashed border-gray-200 bg-white px-6 py-24 text-center"><i class="ri-flask-line text-5xl text-gray-300"></i><h2 class="mt-4 text-xl font-black text-gray-800">No experiment configured</h2><p class="mt-2 text-sm text-gray-500">Create your experiment to begin mapping the study.</p><button @click="createOpen=true" class="mt-5 rounded-xl bg-[#383838] px-6 py-3 text-xs font-black uppercase text-white">Create Experiment</button></div>
                @else
                    @php
                        $draft = $experiment->status === 'draft';
                        $experimental = $experiment->groups->where('group_type','experimental');
                        $control = $experiment->groups->where('group_type','control');
                        $participantCount = $experiment->groups->sum(fn($g) => $g->participants->count());
                        $consentedCount = $experiment->groups->sum(fn($g) => $g->participants->where('consent_status','consented')->count());
                    @endphp
                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        @foreach([['label'=>'Assigned classes','value'=>$experiment->groups->count(),'icon'=>'ri-group-line'],['label'=>'Participants','value'=>$participantCount,'icon'=>'ri-user-line'],['label'=>'Consent verified','value'=>$consentedCount,'icon'=>'ri-shield-check-line'],['label'=>'Mapped assessments','value'=>$experiment->assessments->count(),'icon'=>'ri-survey-line']] as $stat)
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"><i class="{{ $stat['icon'] }} text-xl text-gray-400"></i><p class="mt-3 text-2xl font-black text-[#383838]">{{ $stat['value'] }}</p><p class="mt-1 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ $stat['label'] }}</p></div>
                        @endforeach
                    </div>
                    <section x-show="activeSection==='configuration'" x-cloak x-transition.opacity class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-[10px] font-black uppercase tracking-widest text-gray-400">01 / Configuration</p><h2 class="mt-1 text-xl font-black text-[#383838]">{{ $experiment->title }}</h2><p class="mt-2 text-sm text-gray-500">{{ $experiment->description ?: 'No description provided.' }}</p></div>@if($draft)<button @click="settingsOpen=true" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-black text-gray-700 hover:bg-gray-50"><i class="ri-edit-line mr-1"></i>Edit Settings</button>@endif</div>
                        <div class="mt-5 grid gap-3 border-t border-gray-100 pt-5 text-sm sm:grid-cols-3"><div><p class="text-[10px] font-black uppercase text-gray-400">Weak-topic threshold</p><p class="mt-1 font-black text-gray-800">{{ number_format((float)$experiment->weak_topic_threshold,1) }}%</p></div><div><p class="text-[10px] font-black uppercase text-gray-400">Start</p><p class="mt-1 font-bold text-gray-800">{{ $experiment->intervention_starts_at?->format('M d, Y g:i A') ?? 'Not specified' }}</p></div><div><p class="text-[10px] font-black uppercase text-gray-400">End</p><p class="mt-1 font-bold text-gray-800">{{ $experiment->intervention_ends_at?->format('M d, Y g:i A') ?? 'Not specified' }}</p></div></div>
                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-5"><span class="mr-2 text-[10px] font-black uppercase tracking-widest text-gray-400">Experiment status</span>
                            @if($draft || $experiment->status === 'paused')<form method="POST" action="{{ route('admin.research-experiments.status', $experiment) }}" onsubmit="return confirm('Activation enables recommendations only for consented experimental participants. Continue?')">@csrf @method('PATCH')<input type="hidden" name="status" value="active"><button class="rounded-xl bg-green-700 px-4 py-2 text-xs font-black text-white">Activate</button></form>@endif
                            @if($experiment->status === 'active')<form method="POST" action="{{ route('admin.research-experiments.status', $experiment) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="paused"><button class="rounded-xl bg-amber-100 px-4 py-2 text-xs font-black text-amber-900">Pause</button></form>@endif
                            @if(in_array($experiment->status,['active','paused']))<form method="POST" action="{{ route('admin.research-experiments.status', $experiment) }}" onsubmit="return confirm('Complete this experiment? Completed studies cannot be reactivated.')">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-black text-gray-600">Complete</button></form>@endif
                            @if(!$draft)<span class="text-[11px] text-amber-700">Configuration is locked after activation. Withdrawal remains available.</span>@endif
                        </div>
                    </section>
                    <section x-show="activeSection==='groups'" x-cloak x-transition.opacity class="space-y-4"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-[10px] font-black uppercase tracking-widest text-gray-400">02 / Study allocation</p><h2 class="text-xl font-black text-[#383838]">Research Groups</h2></div>@if($draft)<button @click="groupOpen=true" class="rounded-xl bg-[#383838] px-4 py-2.5 text-xs font-black text-white"><i class="ri-add-line mr-1"></i> Assign Class</button>@endif</div>
                        <div class="grid gap-4 xl:grid-cols-2">
                            @foreach(['experimental'=>$experimental,'control'=>$control] as $type=>$groupCollection)
                                <div class="space-y-4 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex flex-wrap items-center justify-between gap-2"><div><span class="rounded-lg px-2.5 py-1 text-[10px] font-black uppercase {{ $type === 'experimental' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ ucfirst($type) }} Group</span><h3 class="mt-2 text-lg font-black text-gray-900">{{ $groupCollection->count() }} class(es)</h3></div><span class="text-[10px] font-black uppercase {{ $type === 'experimental' && $experiment->status === 'active' ? 'text-green-700' : 'text-gray-400' }}">{{ $type === 'control' ? 'Personalized resources off' : ($experiment->status === 'active' ? 'Intervention enabled' : 'Intervention inactive') }}</span></div>
                                @forelse($groupCollection as $group)
                                    @php
                                        $otherGroupUserIds = $experiment->groups->where('id', '!=', $group->id)
                                            ->flatMap(fn ($other) => $other->participants->pluck('user_id'))->all();
                                        $availableStudents = $studentsByClass->get($group->lab_session_id, collect())
                                            ->filter(fn ($student) => !$group->participants->contains('user_id', $student->id)
                                                && !in_array($student->id, $otherGroupUserIds));
                                        $pendingParticipants = $group->participants->where('consent_status', 'pending');
                                    @endphp
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4"
                                         x-data="{ enrollOpen: false, consentOpen: false, selectedEnroll: [], selectedConsent: [], enrollIds: @js($availableStudents->pluck('id')->values()), consentIds: @js($pendingParticipants->pluck('id')->values()) }">
                                        <div class="flex flex-wrap items-start justify-between gap-2"><div><p class="text-sm font-black text-gray-900">{{ $group->labSession?->subject_name ?? 'Class unavailable' }}</p><p class="mt-1 text-xs font-semibold text-gray-500">{{ $group->labSession?->class_code }} · {{ $group->labSession?->program }} {{ $group->labSession?->year_level }}{{ $group->labSession?->section }}</p></div>@if($draft && $group->participants->isEmpty())<form method="POST" action="{{ route('admin.research-experiments.groups.destroy',[$experiment,$group]) }}" onsubmit="return confirm('Remove unused class assignment?')">@csrf @method('DELETE')<button class="text-xs font-black text-red-600">Remove</button></form>@endif</div>
                                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2"><p class="text-[11px] font-bold text-gray-500">{{ $group->participants->count() }} enrolled · {{ $group->participants->where('consent_status','consented')->count() }} consented</p>@if($draft)
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" @click="enrollOpen=!enrollOpen; consentOpen=false" class="rounded-lg bg-[#383838] px-3 py-2 text-[10px] font-black uppercase text-white"><i class="ri-user-add-line mr-1"></i> Enroll Students</button>
                                                <button type="button" @click="consentOpen=!consentOpen; enrollOpen=false" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-[10px] font-black uppercase text-gray-700"><i class="ri-shield-check-line mr-1"></i> Verify Consent</button>
                                            </div>
                                        @endif</div>
                                        <div class="mt-3 space-y-2">
                                            @forelse($group->participants as $participant)
                                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-white p-3"><div class="min-w-0"><p class="truncate text-xs font-black text-gray-800">{{ $participant->student?->name ?? 'Student unavailable' }}</p><p class="text-[10px] font-semibold uppercase {{ $participant->consent_status === 'consented' ? 'text-green-700' : ($participant->consent_status === 'withdrawn' ? 'text-red-700' : 'text-amber-700') }}">{{ $participant->consent_status }}</p></div><div class="flex flex-wrap gap-2">@if($draft && $participant->consent_status !== 'withdrawn')<button type="button" @click="consentParticipant={{ $participant->id }}; consentName=@js($participant->student?->name ?? 'Student'); consentAction='consented'" class="text-[10px] font-black text-gray-800 underline">Consent status</button>@endif
                                                    @if($draft && in_array($participant->consent_status,['pending','declined']))<form method="POST" action="{{ route('admin.research-experiments.participants.destroy',[$experiment,$participant]) }}" onsubmit="return confirm('Remove this unconsented participant from this draft?')">@csrf @method('DELETE')<button class="text-[10px] font-black text-red-600 underline">Remove</button></form>@endif
                                                    @if(in_array($experiment->status,['draft','active','paused']) && $participant->consent_status !== 'withdrawn')<form method="POST" action="{{ route('admin.research-experiments.participants.consent',[$experiment,$participant]) }}" onsubmit="return confirm('Record withdrawal and immediately revoke further personalized recommendation access?')">@csrf @method('PATCH')<input type="hidden" name="consent_status" value="withdrawn"><button class="text-[10px] font-black text-red-700 underline">Withdraw</button></form>@endif</div></div>
                                            @empty<p class="py-4 text-center text-xs text-gray-400">No participants assigned. Add only students enrolled in this class.</p>@endforelse
                                        </div>
                                        @if($draft)
                                            {{-- Select any or all unassigned students from this classroom. --}}
                                            <div x-cloak x-show="enrollOpen" x-transition class="mt-4 space-y-3 rounded-2xl border border-gray-200 bg-white p-4">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div><h4 class="text-xs font-black uppercase text-gray-800">Enroll classroom students</h4><p class="mt-1 text-[11px] text-gray-500">New participants start with pending consent.</p></div>
                                                    <button type="button" @click="enrollOpen=false" class="text-xs font-bold text-gray-400">Close</button>
                                                </div>
                                                <form action="{{ route('admin.research-experiments.groups.enroll-bulk', [$experiment,$group]) }}" method="POST" class="space-y-3">
                                                    @csrf
                                                    @if($availableStudents->isNotEmpty())
                                                        <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-gray-100 p-3 text-xs font-black text-gray-700">
                                                            <input type="checkbox" class="rounded border-gray-300 text-gray-800"
                                                                :checked="enrollIds.length > 0 && selectedEnroll.length === enrollIds.length"
                                                                @change="selectedEnroll = $event.target.checked ? [...enrollIds] : []">
                                                            Select all available students ({{ $availableStudents->count() }})
                                                        </label>
                                                        <div class="max-h-64 space-y-1 overflow-y-auto rounded-xl border border-gray-100 p-2">
                                                            @foreach($availableStudents as $student)
                                                                <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-gray-50">
                                                                    <input type="checkbox" value="{{ $student->id }}" x-model.number="selectedEnroll" class="rounded border-gray-300 text-gray-800">
                                                                    <span class="text-xs font-semibold text-gray-800">{{ $student->name }}</span>
                                                                    @if($student->school_id)<span class="ml-auto text-[10px] text-gray-400">{{ $student->school_id }}</span>@endif
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                        <template x-for="id in selectedEnroll" :key="id"><input type="hidden" name="user_ids[]" :value="id"></template>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-[11px] text-gray-500" x-text="selectedEnroll.length + ' selected'"></span>
                                                            <button type="submit" :disabled="selectedEnroll.length === 0" class="rounded-xl bg-[#383838] px-4 py-2.5 text-[10px] font-black uppercase text-white disabled:cursor-not-allowed disabled:opacity-40">Enroll Selected</button>
                                                        </div>
                                                    @else
                                                        <p class="rounded-xl bg-gray-50 p-4 text-xs text-gray-500">All eligible classroom students are already assigned, or no unassigned students are available.</p>
                                                    @endif
                                                </form>
                                            </div>
                                            {{-- Bulk recording is only for previously documented consent. --}}
                                            <div x-cloak x-show="consentOpen" x-transition class="mt-4 space-y-3 rounded-2xl border border-gray-200 bg-white p-4">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div><h4 class="text-xs font-black uppercase text-gray-800">Verify recorded consent</h4><p class="mt-1 text-[11px] text-gray-500">Only pending participants are selectable. Withdrawn and declined students are excluded.</p></div>
                                                    <button type="button" @click="consentOpen=false" class="text-xs font-bold text-gray-400">Close</button>
                                                </div>
                                                <form action="{{ route('admin.research-experiments.groups.consent-bulk', [$experiment,$group]) }}" method="POST" class="space-y-3"
                                                      onsubmit="return confirm('Have you verified documented consent for EACH selected student? This will record their consent status, not collect consent.');">
                                                    @csrf @method('PATCH')
                                                    @if($pendingParticipants->isNotEmpty())
                                                        <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-gray-100 p-3 text-xs font-black text-gray-700">
                                                            <input type="checkbox" class="rounded border-gray-300 text-gray-800"
                                                                :checked="consentIds.length > 0 && selectedConsent.length === consentIds.length"
                                                                @change="selectedConsent = $event.target.checked ? [...consentIds] : []">
                                                            Select all pending participants ({{ $pendingParticipants->count() }})
                                                        </label>
                                                        <div class="max-h-64 space-y-1 overflow-y-auto rounded-xl border border-gray-100 p-2">
                                                            @foreach($pendingParticipants as $participant)
                                                                <label class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-gray-50">
                                                                    <input type="checkbox" value="{{ $participant->id }}" x-model.number="selectedConsent" class="rounded border-gray-300 text-gray-800">
                                                                    <span class="text-xs font-semibold text-gray-800">{{ $participant->student?->name ?? 'Student unavailable' }}</span>
                                                                    <span class="ml-auto text-[10px] font-bold text-amber-700">Pending</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                        <template x-for="id in selectedConsent" :key="id"><input type="hidden" name="participant_ids[]" :value="id"></template>
                                                        <label class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-semibold text-amber-900">
                                                            <input type="checkbox" name="consent_verified" value="1" required class="mt-0.5 rounded border-amber-300">
                                                            I confirm that documented consent has already been obtained and verified separately for every selected student.
                                                        </label>
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="text-[11px] text-gray-500" x-text="selectedConsent.length + ' selected'"></span>
                                                            <button type="submit" :disabled="selectedConsent.length === 0" class="rounded-xl bg-green-700 px-4 py-2.5 text-[10px] font-black uppercase text-white disabled:cursor-not-allowed disabled:opacity-40">Save Verified Consent</button>
                                                        </div>
                                                    @else
                                                        <p class="rounded-xl bg-gray-50 p-4 text-xs text-gray-500">No participants currently have pending consent.</p>
                                                    @endif
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @empty<div class="rounded-2xl border-2 border-dashed border-gray-200 px-4 py-12 text-center text-xs text-gray-400">No {{ $type }} class assigned.</div>@endforelse
                                </div>
                            @endforeach
                        </div>
                    </section>
                    <section x-show="activeSection==='assessments'" x-cloak x-transition.opacity class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-[10px] font-black uppercase tracking-widest text-gray-400">03 / Existing quizzes</p><h2 class="text-xl font-black text-gray-900">Assessment Mapping</h2><p class="mt-2 text-xs text-gray-500">Use existing, topic-tagged quizzes. Each assigned class requires pretest and posttest mappings.</p></div>@if($draft)<button type="button" @click="assessmentOpen=true" class="rounded-xl bg-[#383838] px-4 py-2.5 text-xs font-black text-white"><i class="ri-add-line mr-1"></i> Map Quiz</button>@endif</div>
                        <div class="mt-5 overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead class="border-b border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-400"><tr><th class="py-3">Quiz</th><th class="py-3">Class</th><th class="py-3">Purpose</th><th class="py-3">Version</th><th class="py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-gray-100">@forelse($experiment->assessments as $assessment)<tr><td class="py-4 font-black text-gray-800">{{ $assessment->quiz?->title ?? 'Quiz unavailable' }}</td><td class="py-4 text-xs text-gray-500">{{ $assessment->quiz?->labSession?->class_code }}</td><td class="py-4"><span class="rounded-lg px-2 py-1 text-[10px] font-black uppercase {{ $assessment->assessment_type === 'pretest' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-700' }}">{{ $assessment->assessment_type }}</span></td><td class="py-4 text-xs text-gray-500">{{ $assessment->assessment_version ?? '—' }}</td><td class="py-4 text-right">@if($draft)<form method="POST" action="{{ route('admin.research-experiments.assessments.destroy',[$experiment,$assessment]) }}" onsubmit="return confirm('Remove only the study mapping? The quiz and its attempts will be kept.')">@csrf @method('DELETE')<button class="text-xs font-black text-red-600">Unmap</button></form>@else<span class="text-[10px] text-gray-400">Locked</span>@endif</td></tr>@empty<tr><td colspan="5" class="py-12 text-center text-sm text-gray-400">No assessment mappings yet.</td></tr>@endforelse</tbody></table></div>
                    </section>
                    <section x-show="activeSection==='engagement'" x-cloak x-transition.opacity class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">04 / Intervention monitoring</p>
                                <h2 class="text-xl font-black text-gray-900">Recommendation Engagement</h2>
                                <p class="mt-2 max-w-3xl text-xs text-gray-500">
                                    Research-facing engagement data for personalized resources. Active time records measurable interaction with the LMMS resource viewer; it should not be interpreted as proof of comprehension.
                                </p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-gray-600">
                                Experimental recommendations
                            </span>
                        </div>

                        @php
                            $formatEngagementDuration = function ($seconds) {
                                $seconds = (int) $seconds;
                                if ($seconds <= 0) return '—';

                                $hours = intdiv($seconds, 3600);
                                $minutes = intdiv($seconds % 3600, 60);
                                $secs = $seconds % 60;

                                if ($hours > 0) return $hours . 'h ' . $minutes . 'm';
                                if ($minutes > 0) return $minutes . 'm ' . $secs . 's';
                                return $secs . 's';
                            };
                        @endphp

                        <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
                            @foreach([
                                ['label' => 'Recommendations', 'value' => $engagementSummary['recommendations'], 'icon' => 'ri-route-line'],
                                ['label' => 'Students engaged', 'value' => $engagementSummary['students_engaged'] . ' / ' . $engagementSummary['students_recommended'], 'icon' => 'ri-user-follow-line'],
                                ['label' => 'Engagement rate', 'value' => number_format((float) $engagementSummary['engagement_rate'], 1) . '%', 'icon' => 'ri-percent-line'],
                                ['label' => 'Total views', 'value' => $engagementSummary['total_views'], 'icon' => 'ri-eye-line'],
                                ['label' => 'Active time', 'value' => $formatEngagementDuration($engagementSummary['active_seconds']), 'icon' => 'ri-time-line'],
                            ] as $metric)
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4">
                                    <i class="{{ $metric['icon'] }} text-lg text-gray-400"></i>
                                    <p class="mt-3 text-xl font-black text-gray-900">{{ $metric['value'] }}</p>
                                    <p class="mt-1 text-[9px] font-black uppercase tracking-widest text-gray-400">{{ $metric['label'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 overflow-x-auto rounded-2xl border border-gray-100">
                            <table class="w-full min-w-[900px] text-left">
                                <thead class="bg-gray-50 text-[9px] font-black uppercase tracking-widest text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Student</th>
                                        <th class="px-4 py-3">Weak topic(s)</th>
                                        <th class="px-4 py-3 text-center">Recommended</th>
                                        <th class="px-4 py-3 text-center">Opened</th>
                                        <th class="px-4 py-3 text-center">Views</th>
                                        <th class="px-4 py-3 text-right">Active time</th>
                                        <th class="px-4 py-3 text-right">Last viewed</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white text-xs">
                                    @forelse($engagementRows as $row)
                                        <tr class="hover:bg-gray-50/70">
                                            <td class="px-4 py-4">
                                                <p class="font-black text-gray-900">{{ $row->student_name }}</p>
                                                <p class="mt-1 text-[10px] font-semibold text-gray-400">{{ $row->school_id ?: 'No school ID' }}</p>
                                            </td>
                                            <td class="max-w-xs px-4 py-4 text-gray-600">
                                                {{ $row->weak_topics ?: '—' }}
                                            </td>
                                            <td class="px-4 py-4 text-center font-black text-gray-800">
                                                {{ (int) $row->recommendations_count }}
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <span class="rounded-lg px-2.5 py-1 text-[10px] font-black
                                                    {{ (int) $row->opened_resources_count > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                    {{ (int) $row->opened_resources_count }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-center font-bold text-gray-700">
                                                {{ (int) $row->total_views }}
                                            </td>
                                            <td class="px-4 py-4 text-right font-mono font-black text-gray-800">
                                                {{ $formatEngagementDuration($row->active_seconds) }}
                                            </td>
                                            <td class="px-4 py-4 text-right text-[10px] font-semibold text-gray-500">
                                                {{ $row->last_viewed_at ? \Carbon\Carbon::parse($row->last_viewed_at)->format('M d, Y g:i A') : 'Never' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-14 text-center">
                                                <i class="ri-eye-off-line text-3xl text-gray-200"></i>
                                                <p class="mt-3 text-xs font-black uppercase tracking-widest text-gray-400">No recommendation engagement yet</p>
                                                <p class="mx-auto mt-2 max-w-xl text-xs text-gray-400">
                                                    Once eligible experimental students receive and open personalized resources, their viewing activity will appear here.
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section x-show="activeSection==='results'" x-cloak x-transition.opacity class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">05 / Simulation results</p>
                                <h2 class="text-xl font-black text-gray-900">Pretest vs Posttest Results</h2>
                                <p class="mt-2 max-w-3xl text-xs text-gray-500">
                                    Displays the latest recorded attempt for each mapped pretest and posttest. Score change is shown in percentage points. These values are suitable for checking the simulation pipeline; synthetic runs must not be treated as thesis respondent results.
                                </p>
                            </div>
                        </div>

                        @php
                            $formatResultDuration = function ($seconds) {
                                $seconds = (int) $seconds;
                                if ($seconds <= 0) return '—';
                                $hours = intdiv($seconds, 3600);
                                $minutes = intdiv($seconds % 3600, 60);
                                $secs = $seconds % 60;
                                if ($hours > 0) return $hours . 'h ' . $minutes . 'm';
                                if ($minutes > 0) return $minutes . 'm ' . $secs . 's';
                                return $secs . 's';
                            };
                        @endphp

                        <div class="mt-6 grid gap-4 lg:grid-cols-2">
                            @foreach(['experimental' => 'Experimental Group', 'control' => 'Control Group'] as $key => $label)
                                @php $summary = $resultSummary[$key]; @endphp
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest {{ $key === 'experimental' ? 'text-green-700' : 'text-gray-500' }}">{{ $label }}</p>
                                            <p class="mt-1 text-xs text-gray-400">{{ $summary['students'] }} student(s) with both assessments</p>
                                        </div>
                                        <span class="rounded-lg px-2.5 py-1 text-[9px] font-black uppercase {{ $key === 'experimental' ? 'bg-green-50 text-green-700' : 'bg-white text-gray-600 border border-gray-200' }}">
                                            {{ $key }}
                                        </span>
                                    </div>

                                    <div class="mt-5 grid grid-cols-3 gap-3">
                                        <div class="rounded-xl bg-white p-4">
                                            <p class="text-lg font-black text-gray-900">{{ $summary['pretest_avg'] !== null ? number_format($summary['pretest_avg'], 1) . '%' : '—' }}</p>
                                            <p class="mt-1 text-[9px] font-black uppercase tracking-widest text-gray-400">Avg pretest</p>
                                        </div>
                                        <div class="rounded-xl bg-white p-4">
                                            <p class="text-lg font-black text-gray-900">{{ $summary['posttest_avg'] !== null ? number_format($summary['posttest_avg'], 1) . '%' : '—' }}</p>
                                            <p class="mt-1 text-[9px] font-black uppercase tracking-widest text-gray-400">Avg posttest</p>
                                        </div>
                                        <div class="rounded-xl bg-white p-4">
                                            <p class="text-lg font-black {{ ($summary['avg_change'] ?? 0) > 0 ? 'text-green-700' : (($summary['avg_change'] ?? 0) < 0 ? 'text-red-600' : 'text-gray-900') }}">
                                                {{ $summary['avg_change'] !== null ? (($summary['avg_change'] > 0 ? '+' : '') . number_format($summary['avg_change'], 1) . ' pp') : '—' }}
                                            </p>
                                            <p class="mt-1 text-[9px] font-black uppercase tracking-widest text-gray-400">Avg change</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 overflow-x-auto rounded-2xl border border-gray-100">
                            <table class="w-full min-w-[1100px] text-left">
                                <thead class="bg-gray-50 text-[9px] font-black uppercase tracking-widest text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Student</th>
                                        <th class="px-4 py-3">Group</th>
                                        <th class="px-4 py-3 text-center">Pretest</th>
                                        <th class="px-4 py-3 text-center">Posttest</th>
                                        <th class="px-4 py-3 text-center">Change</th>
                                        <th class="px-4 py-3 text-center">Recommendations</th>
                                        <th class="px-4 py-3 text-center">Opened</th>
                                        <th class="px-4 py-3 text-center">Views</th>
                                        <th class="px-4 py-3 text-right">Active time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white text-xs">
                                    @forelse($resultRows as $row)
                                        <tr class="hover:bg-gray-50/70">
                                            <td class="px-4 py-4">
                                                <p class="font-black text-gray-900">{{ $row->student_name }}</p>
                                                <p class="mt-1 text-[10px] font-semibold text-gray-400">{{ $row->school_id ?: 'No school ID' }}</p>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="rounded-lg px-2.5 py-1 text-[9px] font-black uppercase {{ $row->group_type === 'experimental' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ $row->group_type }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-center font-black text-gray-800">
                                                {{ $row->pretest_percentage !== null ? number_format($row->pretest_percentage, 1) . '%' : '—' }}
                                            </td>
                                            <td class="px-4 py-4 text-center font-black text-gray-800">
                                                {{ $row->posttest_percentage !== null ? number_format($row->posttest_percentage, 1) . '%' : '—' }}
                                            </td>
                                            <td class="px-4 py-4 text-center font-black
                                                {{ ($row->change_percentage_points ?? 0) > 0 ? 'text-green-700' : (($row->change_percentage_points ?? 0) < 0 ? 'text-red-600' : 'text-gray-500') }}">
                                                @if($row->change_percentage_points !== null)
                                                    {{ $row->change_percentage_points > 0 ? '+' : '' }}{{ number_format($row->change_percentage_points, 1) }} pp
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            @if($row->group_type === 'experimental')
                                                <td class="px-4 py-4 text-center font-bold text-gray-700">{{ $row->recommendations_count }}</td>
                                                <td class="px-4 py-4 text-center font-bold text-gray-700">{{ $row->opened_resources_count }}</td>
                                                <td class="px-4 py-4 text-center font-bold text-gray-700">{{ $row->total_views }}</td>
                                                <td class="px-4 py-4 text-right font-mono font-black text-gray-800">{{ $formatResultDuration($row->active_seconds) }}</td>
                                            @else
                                                <td class="px-4 py-4 text-center text-gray-300">—</td>
                                                <td class="px-4 py-4 text-center text-gray-300">—</td>
                                                <td class="px-4 py-4 text-center text-gray-300">—</td>
                                                <td class="px-4 py-4 text-right text-gray-300">—</td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-6 py-14 text-center">
                                                <i class="ri-bar-chart-grouped-line text-3xl text-gray-200"></i>
                                                <p class="mt-3 text-xs font-black uppercase tracking-widest text-gray-400">No mapped assessment results yet</p>
                                                <p class="mx-auto mt-2 max-w-xl text-xs text-gray-400">
                                                    Run the mapped pretest and posttest simulations for consented participants. Results will appear here automatically.
                                                </p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
            </div>
        </main>
        {{-- Create experiment modal --}}
        <div x-cloak x-show="createOpen" @keydown.escape.window="createOpen=false" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Create experiment" @click.self="createOpen=false"><div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-8"><div class="mb-5 flex items-center justify-between"><h2 class="text-xl font-black uppercase">New Experiment</h2><button type="button" @click="createOpen=false" aria-label="Close"><i class="ri-close-line text-xl"></i></button></div><form method="POST" action="{{ route('admin.research-experiments.store') }}" class="space-y-4">@csrf<input type="hidden" name="_form" value="create"><label class="block text-xs font-black uppercase text-gray-500">Title *<input name="title" value="{{ old('title') }}" maxlength="255" required class="mt-2 w-full rounded-xl border-gray-200 text-sm normal-case"></label><label class="block text-xs font-black uppercase text-gray-500">Description<textarea name="description" maxlength="3000" rows="3" class="mt-2 w-full rounded-xl border-gray-200 text-sm normal-case">{{ old('description') }}</textarea></label><label class="block text-xs font-black uppercase text-gray-500">Weak-topic threshold (%) *<input type="number" name="weak_topic_threshold" min="0" max="100" step="0.01" value="{{ old('weak_topic_threshold',75) }}" required class="mt-2 w-full rounded-xl border-gray-200 text-sm"></label><div class="grid grid-cols-2 gap-3"><label class="block text-xs font-black uppercase text-gray-500">Start<input name="intervention_starts_at" type="datetime-local" value="{{ old('intervention_starts_at') }}" class="mt-2 w-full rounded-xl border-gray-200 text-xs"></label><label class="block text-xs font-black uppercase text-gray-500">End<input name="intervention_ends_at" type="datetime-local" value="{{ old('intervention_ends_at') }}" class="mt-2 w-full rounded-xl border-gray-200 text-xs"></label></div><p class="text-xs text-gray-500">A new experiment always starts as a draft. Assign groups, quizzes and consented participants before activation.</p><button class="w-full rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase text-white">Create Draft</button></form></div></div>
        @if($experiment)
            <div x-cloak x-show="settingsOpen" @keydown.escape.window="settingsOpen=false" @click.self="settingsOpen=false" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Edit experiment"><div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl sm:p-8"><div class="mb-5 flex items-center justify-between"><h2 class="text-xl font-black uppercase">Experiment Settings</h2><button type="button" @click="settingsOpen=false" aria-label="Close"><i class="ri-close-line text-xl"></i></button></div><form action="{{ route('admin.research-experiments.update',$experiment) }}" method="POST" class="space-y-4">@csrf @method('PUT')<input type="hidden" name="_form" value="settings"><label class="block text-xs font-black uppercase text-gray-500">Title *<input name="title" value="{{ old('title',$experiment->title) }}" required maxlength="255" class="mt-2 w-full rounded-xl border-gray-200 text-sm normal-case"></label><label class="block text-xs font-black uppercase text-gray-500">Description<textarea name="description" rows="3" maxlength="3000" class="mt-2 w-full rounded-xl border-gray-200 text-sm normal-case">{{ old('description',$experiment->description) }}</textarea></label><label class="block text-xs font-black uppercase text-gray-500">Threshold (%)<input name="weak_topic_threshold" type="number" min="0" max="100" step="0.01" required value="{{ old('weak_topic_threshold',$experiment->weak_topic_threshold) }}" class="mt-2 w-full rounded-xl border-gray-200 text-sm"></label><div class="grid grid-cols-2 gap-3"><label class="block text-xs font-black uppercase text-gray-500">Start<input name="intervention_starts_at" type="datetime-local" value="{{ old('intervention_starts_at',$experiment->intervention_starts_at?->format('Y-m-d\TH:i')) }}" class="mt-2 w-full rounded-xl border-gray-200 text-xs"></label><label class="block text-xs font-black uppercase text-gray-500">End<input name="intervention_ends_at" type="datetime-local" value="{{ old('intervention_ends_at',$experiment->intervention_ends_at?->format('Y-m-d\TH:i')) }}" class="mt-2 w-full rounded-xl border-gray-200 text-xs"></label></div><button class="w-full rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase text-white">Save Settings</button></form></div></div>
            <div x-cloak x-show="groupOpen" @keydown.escape.window="groupOpen=false" @click.self="groupOpen=false" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Assign class"><div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl"><div class="mb-5 flex justify-between"><h2 class="text-xl font-black uppercase">Assign Class</h2><button type="button" @click="groupOpen=false" aria-label="Close"><i class="ri-close-line text-xl"></i></button></div><form method="POST" action="{{ route('admin.research-experiments.groups.store',$experiment) }}" class="space-y-4">@csrf<label class="block text-xs font-black uppercase text-gray-500">Existing classroom<select name="lab_session_id" required class="mt-2 w-full rounded-xl border-gray-200 text-sm"><option value="">Select a class</option>@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->class_code }} · {{ $session->program }} {{ $session->year_level }}{{ $session->section }} · {{ $session->subject_name }}</option>@endforeach</select></label><label class="block text-xs font-black uppercase text-gray-500">Study condition<select name="group_type" required class="mt-2 w-full rounded-xl border-gray-200 text-sm"><option value="experimental">Experimental · personalized recommendations</option><option value="control">Control · no personalized recommendations</option></select></label><p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-800">Allocation changes are locked after activation. Choose the actual class records; don't infer group membership from section text.</p><button class="w-full rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase text-white">Assign Class</button></form></div></div>
            <div x-cloak x-show="assessmentOpen" @keydown.escape.window="assessmentOpen=false" @click.self="assessmentOpen=false" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Map quiz"><div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl"><div class="mb-5 flex justify-between"><h2 class="text-xl font-black uppercase">Map Assessment</h2><button type="button" @click="assessmentOpen=false" aria-label="Close"><i class="ri-close-line text-xl"></i></button></div><form method="POST" action="{{ route('admin.research-experiments.assessments.store',$experiment) }}" class="space-y-4">@csrf<label class="block text-xs font-black uppercase text-gray-500">Topic-tagged quiz<select name="quiz_id" required class="mt-2 w-full rounded-xl border-gray-200 text-sm"><option value="">Select an existing quiz</option>@foreach($quizzes as $quiz)@if($experiment->groups->contains('lab_session_id',$quiz->subject_id))<option value="{{ $quiz->id }}">{{ $quiz->labSession?->class_code }} · {{ $quiz->title }}</option>@endif @endforeach</select></label><label class="block text-xs font-black uppercase text-gray-500">Assessment type<select name="assessment_type" class="mt-2 w-full rounded-xl border-gray-200 text-sm"><option value="pretest">Pretest</option><option value="posttest">Posttest</option></select></label><label class="block text-xs font-black uppercase text-gray-500">Version (optional)<input name="assessment_version" maxlength="32" placeholder="e.g. A" class="mt-2 w-full rounded-xl border-gray-200 text-sm normal-case"></label><p class="text-xs text-gray-500">Each assigned class needs both pretest and posttest mappings. Mapping does not edit quiz content.</p><button class="w-full rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase text-white">Map Quiz</button></form></div></div>
            <div x-cloak x-show="consentParticipant !== null" @keydown.escape.window="consentParticipant=null" @click.self="consentParticipant=null" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-label="Record consent"><div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl"><div class="mb-5 flex justify-between"><h2 class="text-xl font-black uppercase">Consent Record</h2><button type="button" @click="consentParticipant=null" aria-label="Close"><i class="ri-close-line text-xl"></i></button></div><p class="mb-4 text-sm text-gray-600" x-text="consentName"></p><form method="POST" :action="@js(route('admin.research-experiments.participants.consent', [$experiment, '__PARTICIPANT__'])).replace('__PARTICIPANT__', consentParticipant)" class="space-y-4">@csrf @method('PATCH')<label class="block text-xs font-black uppercase text-gray-500">Consent status<select name="consent_status" x-model="consentAction" class="mt-2 w-full rounded-xl border-gray-200 text-sm"><option value="consented">Consented</option><option value="declined">Declined</option><option value="withdrawn">Withdrawn</option></select></label><label x-show="consentAction==='consented'" class="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-semibold text-amber-900"><input type="checkbox" name="consent_verified" value="1" :required="consentAction==='consented'" class="mt-0.5 rounded border-amber-300">I have verified the student's documented consent outside this system. This action does not collect consent itself.</label><button class="w-full rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase text-white">Save Consent Status</button></form></div></div>
        @endif
    </div>
    <style>[x-cloak]{display:none!important}</style>
</x-app-layout>