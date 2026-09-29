<x-app-layout>
    <x-slot name="header"></x-slot>

    @php
        $experimentPayload = $experiments->map(fn($experiment) => [
            'id' => $experiment->id,
            'title' => $experiment->title,
            'status' => $experiment->status,
            'groups' => $experiment->groups->map(fn($group) => [
                'id' => $group->id,
                'type' => $group->group_type,
                'recommendations_enabled' => (bool) $group->recommendations_enabled,
                'class' => $group->labSession ? [
                    'id' => $group->labSession->id,
                    'subject_name' => $group->labSession->subject_name,
                    'class_code' => $group->labSession->class_code,
                    'program' => $group->labSession->program,
                    'year_level' => $group->labSession->year_level,
                    'section' => $group->labSession->section,
                    'faculty_id' => $group->labSession->faculty_id,
                    'faculty_name' => $group->labSession->faculty?->name,
                    'student_count' => $group->labSession->students()->count(),
                ] : null,
            ])->values(),
        ])->values();
    @endphp

    <div class="fixed inset-0 flex overflow-hidden bg-gray-100"
         x-data="localQuizSimulator(@js($experimentPayload))">

        <aside class="mt-[80px] hidden h-[calc(100vh-80px)] w-64 shrink-0 flex-col justify-between border-r border-gray-300 bg-white md:flex">
            <nav class="mt-8 flex-1 space-y-2 overflow-y-auto px-4">
                <p class="mb-2 px-4 text-[10px] font-black uppercase tracking-widest text-gray-400">System Admin</p>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100"><i class="ri-dashboard-line mr-3 text-lg"></i>Dashboard</a>
                <a href="{{ route('admin.classroom') }}" class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100"><i class="ri-folder-5-line mr-3 text-lg"></i>Classroom</a>
                <a href="{{ route('admin.users.index') }}" class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100"><i class="ri-user-line mr-3 text-lg"></i>Users</a>
                 <a href="{{ route('profile.edit') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('profile.edit') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-settings-5-line mr-3 text-lg"></i> Settings
                </a>
                <div class="mx-4 border-t border-gray-100 pt-5 text-[10px] font-black uppercase tracking-widest text-gray-400">Learning Management</div>
                <a href="{{ route('admin.learning-topics.index') }}" class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100"><i class="ri-price-tag-3-line mr-3 text-lg"></i>Learning Topics</a>
                <a href="{{ route('admin.learning-resources.index') }}" class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100"><i class="ri-book-open-line mr-3 text-lg"></i>Resource Library</a>
                <a href="{{ route('admin.research-experiments.index') }}" class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-100"><i class="ri-flask-line mr-3 text-lg"></i>Research Experiment</a>

                 @if(app()->environment(['local', 'testing']))
                    <a href="{{ route('admin.local-quiz-simulator.index') }}"
                        class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold {{ request()->routeIs('admin.local-quiz-simulator.*') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="ri-test-tube-line mr-3 text-lg"></i>
                        Local Quiz Simulator
                    </a>
                @endif
            </nav>
            <div class="border-t border-gray-200 bg-gray-50 p-4">
                <p class="truncate text-xs font-black text-gray-800">{{ auth()->user()->name }}</p>
                <p class="mt-1 text-[9px] font-black uppercase tracking-widest text-amber-600">Local environment only</p>
            </div>
        </aside>

        <main class="mt-[80px] min-w-0 flex-1 overflow-y-auto">
            <div class="space-y-6 p-4 pb-20 sm:p-8">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex gap-3">
                        <i class="ri-alert-line mt-0.5 text-xl text-amber-700"></i>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-amber-800">Synthetic test data only</p>
                            <p class="mt-1 text-sm text-amber-900">This page creates synthetic quiz attempts for local testing. Never use these generated results as thesis respondent data.</p>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <p class="mb-2 font-black">Please review:</p>
                        <ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">LMMS / Developer Tools</p>
                    <h1 class="mt-2 text-3xl font-black uppercase tracking-tight text-[#383838]">Local Quiz Simulator</h1>
                    <p class="mt-2 max-w-3xl text-sm text-gray-500">Create one equivalent quiz for two research sections, map both to the experiment, then automatically generate synthetic student attempts and run the recommendation pipeline.</p>
                </div>

                <form method="POST" action="{{ route('admin.local-quiz-simulator.store') }}" class="space-y-6">
                    @csrf

                    <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">01 / Experiment and classes</p>
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-black uppercase text-gray-500">Research Experiment</span>
                                <select name="experiment_id" x-model="experimentId" @change="resetGroups()" required class="mt-2 w-full rounded-xl border-gray-200 text-sm">
                                    <option value="">Select experiment</option>
                                    @foreach($experiments as $experiment)
                                        <option value="{{ $experiment->id }}">{{ $experiment->title }} · {{ strtoupper($experiment->status) }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-xs font-black uppercase text-gray-500">Assessment purpose</span>
                                <select name="assessment_type" class="mt-2 w-full rounded-xl border-gray-200 text-sm">
                                    <option value="pretest">Pretest</option>
                                    <option value="posttest">Posttest</option>
                                </select>
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-black uppercase text-gray-500">Section 1</span>
                                <select name="group_ids[]" x-model="groupA" required class="mt-2 w-full rounded-xl border-gray-200 text-sm">
                                    <option value="">Select group</option>
                                    <template x-for="g in groups" :key="g.id">
                                        <option :value="g.id" :disabled="String(g.id) === String(groupB)" x-text="groupLabel(g)"></option>
                                    </template>
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-xs font-black uppercase text-gray-500">Section 2</span>
                                <select name="group_ids[]" x-model="groupB" required class="mt-2 w-full rounded-xl border-gray-200 text-sm">
                                    <option value="">Select group</option>
                                    <template x-for="g in groups" :key="g.id">
                                        <option :value="g.id" :disabled="String(g.id) === String(groupA)" x-text="groupLabel(g)"></option>
                                    </template>
                                </select>
                            </label>
                        </div>

                        <div x-show="groupA && groupB" x-cloak class="mt-4 rounded-2xl bg-gray-50 p-4 text-xs text-gray-600">
                            <p class="font-black uppercase tracking-wider text-gray-500">Validation</p>
                            <p class="mt-2" x-text="compatibilityMessage"></p>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">02 / Quiz configuration</p>
                        <div class="mt-4 grid gap-4 lg:grid-cols-3">
                            <label class="lg:col-span-2 block">
                                <span class="text-xs font-black uppercase text-gray-500">Quiz title</span>
                                <input name="title" value="{{ old('title','System Administration Pretest') }}" required class="mt-2 w-full rounded-xl border-gray-200 text-sm">
                            </label>
                            <label class="block">
                                <span class="text-xs font-black uppercase text-gray-500">Time limit</span>
                                <input type="number" name="time_limit" min="1" max="240" value="{{ old('time_limit',30) }}" required class="mt-2 w-full rounded-xl border-gray-200 text-sm">
                            </label>
                        </div>

                        <label class="mt-4 block">
                            <span class="text-xs font-black uppercase text-gray-500">Learning topic</span>
                            <select name="learning_topic_id" required class="mt-2 w-full rounded-xl border-gray-200 text-sm">
                                <option value="">Select standardized topic</option>
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->id }}">{{ $topic->name }}</option>
                                @endforeach
                            </select>
                            <span class="mt-1 block text-[11px] text-gray-400">The current LMMS quiz model supports one standardized learning topic per quiz.</span>
                        </label>
                    </section>

                    <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">03 / Questions</p>
                                <h2 class="mt-1 text-xl font-black text-gray-900">Build test quiz</h2>
                            </div>
                            <button type="button" @click="addQuestion()" class="rounded-xl border border-gray-300 px-4 py-2.5 text-xs font-black uppercase text-gray-700 hover:bg-gray-50"><i class="ri-add-line mr-1"></i>Add Question</button>
                        </div>

                        <div class="mt-5 space-y-4">
                            <template x-for="(q,index) in questions" :key="q.uid">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 sm:p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#383838] text-xs font-black text-white" x-text="index+1"></span>
                                            <select x-model="q.type" :name="`questions[${index}][type]`" @change="normalizeQuestion(q)" class="rounded-xl border-gray-200 text-xs font-black uppercase">
                                                <option value="multiple">Multiple Choice</option>
                                                <option value="true_false">True / False</option>
                                                <option value="select_all">Select All</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="1" max="100" x-model="q.points" :name="`questions[${index}][points]`" class="w-20 rounded-xl border-gray-200 text-xs" title="Points">
                                            <button type="button" @click="removeQuestion(index)" :disabled="questions.length===1" class="rounded-lg px-2 py-2 text-red-600 disabled:opacity-30"><i class="ri-delete-bin-line"></i></button>
                                        </div>
                                    </div>

                                    <textarea x-model="q.text" :name="`questions[${index}][text]`" required rows="2" placeholder="Question text..." class="mt-4 w-full rounded-xl border-gray-200 bg-white text-sm"></textarea>

                                    <div class="mt-4 space-y-2">
                                        <template x-for="(option,oIndex) in q.options" :key="oIndex">
                                            <div class="flex items-center gap-3">
                                                <template x-if="q.type !== 'select_all'">
                                                    <input type="radio" :name="`correct_visual_${index}`" :checked="String(q.correct)===String(oIndex) || String(q.correct)===String(option)" @change="q.correct = q.type === 'true_false' ? option : oIndex" class="rounded-full border-gray-300 text-gray-900">
                                                </template>
                                                <template x-if="q.type === 'select_all'">
                                                    <input type="checkbox" :checked="q.corrects.includes(oIndex)" @change="toggleCorrect(q,oIndex)" class="rounded border-gray-300 text-gray-900">
                                                </template>
                                                <input x-model="q.options[oIndex]" :name="`questions[${index}][options][${oIndex}]`" :readonly="q.type==='true_false'" required class="min-w-0 flex-1 rounded-xl border-gray-200 bg-white text-sm">
                                            </div>
                                        </template>
                                    </div>

                                    <input x-show="q.type !== 'select_all'" type="hidden" :name="`questions[${index}][correct_option]`" :value="q.correct">
                                    <template x-if="q.type === 'select_all'">
                                        <div>
                                            <template x-for="correctIndex in q.corrects" :key="correctIndex">
                                                <input type="hidden" :name="`questions[${index}][correct_options][]`" :value="correctIndex">
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </section>

                    <button type="submit" :disabled="!compatible" class="w-full rounded-2xl bg-[#383838] px-6 py-4 text-sm font-black uppercase tracking-widest text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-40">
                        Create Paired Local Test Quiz
                    </button>
                </form>

                <section class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-7">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">04 / Simulation runner</p>
                        <h2 class="mt-1 text-xl font-black text-gray-900">Recent local test quizzes</h2>
                        <p class="mt-2 text-xs text-gray-500">Each pair represents cloned quiz content assigned to two separate sections.</p>
                    </div>

                    <div class="mt-5 space-y-4">
                        @forelse($recentPairs as $pairTitle => $pair)
                            @if($pair->count() >= 2)
                                @php($pairIds = $pair->take(2)->pluck('id')->values())
                                <div class="rounded-2xl border border-gray-200 p-4 sm:p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-black text-gray-900">{{ $pairTitle }}</p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach($pair->take(2) as $quiz)
                                                    <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-[10px] font-black uppercase text-gray-600">{{ $quiz->labSession?->program }} {{ $quiz->labSession?->year_level }}{{ $quiz->labSession?->section }} · {{ $quiz->labSession?->students()->count() }} students</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-3 lg:grid-cols-[1fr_auto_auto]">
                                        <form method="POST" action="{{ route('admin.local-quiz-simulator.simulate') }}" class="contents">
                                            @csrf
                                            @foreach($pairIds as $id)<input type="hidden" name="quiz_ids[]" value="{{ $id }}">@endforeach
                                            <select name="performance_mode" class="rounded-xl border-gray-200 text-sm">
                                                <option value="random_answers">Literal random answers</option>
                                                <option value="mixed" selected>Mixed performance</option>
                                                <option value="mostly_low">Mostly low scores</option>
                                                <option value="mostly_high">Mostly high scores</option>
                                            </select>
                                            <label class="flex items-center gap-2 rounded-xl border border-gray-200 px-3 text-xs font-bold text-gray-600"><input type="checkbox" name="replace_existing" value="1" class="rounded"> Replace old synthetic attempts</label>
                                            <button class="rounded-xl bg-green-700 px-4 py-3 text-xs font-black uppercase text-white" onclick="return confirm('Generate synthetic attempts for every enrolled student in both sections?')"><i class="ri-play-line mr-1"></i>Run Simulation</button>
                                        </form>
                                    </div>

                                    <form method="POST" action="{{ route('admin.local-quiz-simulator.reset') }}" class="mt-3 text-right" onsubmit="return confirm('Delete only synthetic attempts and recommendations for this quiz pair?')">
                                        @csrf @method('DELETE')
                                        @foreach($pairIds as $id)<input type="hidden" name="quiz_ids[]" value="{{ $id }}">@endforeach
                                        <button class="text-[10px] font-black uppercase text-red-600 underline">Reset synthetic results</button>
                                    </form>
                                </div>
                            @endif
                        @empty
                            <div class="rounded-2xl border-2 border-dashed border-gray-200 py-12 text-center text-sm text-gray-400">No local test quiz pairs yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function localQuizSimulator(experiments) {
            return {
                experiments,
                experimentId: '',
                groupA: '',
                groupB: '',
                questions: [
                    { uid: Date.now(), type: 'multiple', text: '', points: 1, options: ['', '', '', ''], correct: 0, corrects: [] }
                ],
                get groups() {
                    const e = this.experiments.find(x => String(x.id) === String(this.experimentId));
                    return e ? e.groups.filter(g => g.class) : [];
                },
                get selectedA() { return this.groups.find(g => String(g.id) === String(this.groupA)); },
                get selectedB() { return this.groups.find(g => String(g.id) === String(this.groupB)); },
                get compatible() {
                    if (!this.selectedA || !this.selectedB) return false;
                    return this.selectedA.class.subject_name === this.selectedB.class.subject_name &&
                           String(this.selectedA.class.faculty_id) === String(this.selectedB.class.faculty_id);
                },
                get compatibilityMessage() {
                    if (!this.selectedA || !this.selectedB) return 'Select two classes.';
                    if (this.compatible) return `Compatible: ${this.selectedA.class.subject_name} · Prof. ${this.selectedA.class.faculty_name ?? 'Unknown'}`;
                    return 'Not compatible: both sections must use the same exact subject name and professor.';
                },
                groupLabel(g) {
                    const c = g.class;
                    if (!c) return 'Unavailable class';
                    return `${g.type.toUpperCase()} · ${c.program ?? ''} ${c.year_level ?? ''}${c.section ?? ''} · ${c.subject_name} · ${c.student_count} students`;
                },
                resetGroups() { this.groupA=''; this.groupB=''; },
                addQuestion() {
                    this.questions.push({ uid: Date.now()+Math.random(), type: 'multiple', text: '', points: 1, options: ['', '', '', ''], correct: 0, corrects: [] });
                },
                removeQuestion(index) { if (this.questions.length > 1) this.questions.splice(index,1); },
                normalizeQuestion(q) {
                    q.corrects = [];
                    if (q.type === 'true_false') {
                        q.options = ['True','False'];
                        q.correct = 'True';
                    } else {
                        q.options = ['', '', '', ''];
                        q.correct = 0;
                    }
                },
                toggleCorrect(q,index) {
                    if (q.corrects.includes(index)) q.corrects = q.corrects.filter(i => i !== index);
                    else q.corrects.push(index);
                }
            }
        }
    </script>
    <style>[x-cloak]{display:none!important}</style>
</x-app-layout>
