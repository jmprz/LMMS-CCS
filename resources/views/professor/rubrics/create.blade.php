<x-app-layout>
<div class="min-h-screen bg-gray-50 py-8"
     x-data="rubricBuilder()"
     x-init="init()">
<div class="max-w-6xl mx-auto px-4">

    {{-- Back --}}
    <a href="{{ route('professor.classroom.show', $task->subject_id) }}"
       class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-900 mb-6 font-bold text-sm">
        <i class="ri-arrow-left-line"></i> Back to Classroom
    </a>

    {{-- Page Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-900" x-text="rubricName || 'New Rubric'"></h1>
            <p class="text-sm text-gray-500 mt-1">
                Task: <span class="font-bold text-gray-700">{{ $task->title }}</span>
                &nbsp;·&nbsp; {{ $task->labSession->subject_name ?? 'Classroom Portal' }}
            </p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total Points</p>
            <p class="text-4xl font-black text-indigo-600" x-text="totalPoints()"></p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-6 font-bold text-sm flex items-center gap-2">
        <i class="ri-checkbox-circle-fill text-green-500 text-lg"></i>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-6 text-sm">
        <p class="font-black mb-1">Please fix the following errors:</p>
        @foreach($errors->all() as $error)
            <p>• {{ $error }}</p>
        @endforeach
    </div>
    @endif

    {{-- FORM --}}
    <form method="POST"
          action="{{ route('professor.tasks.rubric.store', $task->id) }}"
          @submit.prevent="submitForm()"
          x-ref="form">
        @csrf
        {{-- Hidden serialised fields --}}
        <input type="hidden" name="criteria_json"        x-ref="criteriaJson">
        <input type="hidden" name="name"                 :value="rubricName">
        <input type="hidden" name="description"          :value="rubricDescription">
        <input type="hidden" name="auto_grade_enabled"   :value="autoGrade ? '1' : '0'">

        {{-- Rubric meta --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 mb-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Rubric Title *</label>
                    <input x-model="rubricName" type="text" required
                           placeholder="e.g. Lab 1 – Python Basics"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Description</label>
                    <input x-model="rubricDescription" type="text"
                           placeholder="Optional description"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100">
                <button type="button" @click="autoGrade = !autoGrade"
                        :class="autoGrade ? 'bg-indigo-600' : 'bg-gray-300'"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none">
                    <span :class="autoGrade ? 'translate-x-6' : 'translate-x-1'"
                          class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                </button>
                <div>
                    <p class="text-sm font-black text-gray-700">Enable Gemini AI Auto-Grading</p>
                    <p class="text-xs text-gray-400">When ON, Gemini will grade submissions automatically using these levels</p>
                </div>
            </div>
        </div>

        {{-- CRITERIA --}}
        <template x-for="(criterion, cIdx) in criteria" :key="criterion.uid">
            <div class="bg-white rounded-2xl border border-gray-200 mb-4 overflow-hidden">

                {{-- Criterion header row --}}
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                    <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-black flex-shrink-0"
                         x-text="cIdx + 1"></div>
                    <input x-model="criterion.name" type="text"
                           placeholder="Criterion name (e.g. Code Correctness)"
                           class="flex-1 bg-transparent border-b-2 border-dashed border-gray-300 focus:border-indigo-500 outline-none text-base font-black text-gray-800 py-1 transition">
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-sm font-black text-gray-400">/<span class="text-indigo-600" x-text="maxPoints(criterion)"></span> pts</span>
                        <button type="button" @click="removeCriterion(criterion.uid)"
                                x-show="criteria.length > 1"
                                class="text-gray-400 hover:text-red-500 transition p-1 rounded-lg hover:bg-red-50">
                            <i class="ri-delete-bin-line text-base"></i>
                        </button>
                    </div>
                </div>

                {{-- Performance levels: horizontal cards --}}
                <div class="px-5 pt-4 pb-2">
                    <div class="flex gap-3 overflow-x-auto pb-3" style="scrollbar-width: thin;">

                        <template x-for="(level, lIdx) in criterion.levels" :key="level.uid">
                            <div class="flex-shrink-0 w-52 border border-gray-200 rounded-xl overflow-hidden bg-white hover:border-indigo-300 hover:shadow-md transition group">

                                {{-- Level header --}}
                                <div class="border-b border-gray-100 px-3 pt-3 pb-2 bg-gray-50 group-hover:bg-indigo-50/50 transition">
                                    <input x-model="level.label" type="text"
                                           placeholder="Level name"
                                           class="w-full text-sm font-black text-gray-800 bg-transparent border-none outline-none placeholder-gray-400 mb-1.5">
                                    <div class="flex items-center gap-1">
                                        <input x-model.number="level.points" type="number" min="0"
                                               class="w-14 text-sm font-black text-indigo-600 bg-white border border-gray-200 rounded-lg px-2 py-1 text-center focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none"
                                               placeholder="0">
                                        <span class="text-xs text-gray-400 font-bold">pts</span>
                                    </div>
                                </div>

                                {{-- Level description --}}
                                <div class="p-3">
                                    <textarea x-model="level.description"
                                              placeholder="Describe what earns this level..."
                                              rows="5"
                                              class="w-full text-xs text-gray-700 bg-transparent border-none outline-none resize-none placeholder-gray-300 leading-relaxed"></textarea>
                                </div>

                                {{-- Remove level --}}
                                <div class="px-3 pb-3">
                                    <button type="button"
                                            @click="removeLevel(criterion, level.uid)"
                                            x-show="criterion.levels.length > 1"
                                            class="text-xs text-gray-400 hover:text-red-500 font-bold transition">
                                        <i class="ri-close-line"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Add level button --}}
                        <div class="flex-shrink-0 w-44">
                            <button type="button" @click="addLevel(criterion)"
                                    class="w-full h-full min-h-[180px] border-2 border-dashed border-gray-200 rounded-xl text-gray-400 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50/30 transition flex flex-col items-center justify-center gap-2 text-xs font-bold">
                                <i class="ri-add-circle-line text-2xl"></i>
                                Add Level
                            </button>
                        </div>
                    </div>
                </div>

                {{-- AI grading note (collapsed by default) --}}
                <div class="px-5 pb-4" x-data="{ showNote: false }">
                    <button type="button" @click="showNote = !showNote"
                            class="text-xs text-gray-400 hover:text-indigo-500 font-bold flex items-center gap-1 transition">
                        <i class="ri-robot-line"></i>
                        <span x-text="showNote ? 'Hide AI instructions' : 'Add extra AI grading instructions (optional)'"></span>
                        <i :class="showNote ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"></i>
                    </button>
                    <div x-show="showNote" x-transition class="mt-2">
                        <textarea x-model="criterion.description"
                                  placeholder="Optional: extra instructions for Gemini. E.g. 'Focus on whether the student uses proper variable naming conventions.'"
                                  rows="2"
                                  class="w-full text-xs border border-gray-200 rounded-xl px-3 py-2 resize-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent outline-none text-gray-700"></textarea>
                    </div>
                </div>

            </div>
        </template>

        {{-- Add criterion --}}
        <button type="button" @click="addCriterion()"
                class="w-full py-4 border-2 border-dashed border-gray-300 rounded-2xl text-gray-500 hover:border-indigo-400 hover:text-indigo-600 hover:bg-indigo-50/30 font-black text-sm transition flex items-center justify-center gap-2 mb-6">
            <i class="ri-add-circle-line text-lg"></i> Add Criterion
        </button>

        {{-- Footer actions --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 flex items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse" x-show="autoGrade"></div>
                    <div class="w-2 h-2 rounded-full bg-gray-400" x-show="!autoGrade"></div>
                    <p class="text-sm font-black text-gray-700"
                       x-text="autoGrade ? 'Auto-Grading ENABLED — Gemini will grade on submission' : 'Auto-Grading DISABLED — Manual grading only'"></p>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    <span x-text="criteria.length"></span> criteria ·
                    <span x-text="totalPoints()"></span> total points
                </p>
            </div>
            <div class="flex gap-3">
                @if($rubric)
                <button type="button" 
                        onclick="if(confirm('Delete this rubric? Students will no longer be auto-graded.')) { document.getElementById('delete-rubric-form').submit(); }"
                        class="px-5 py-3 bg-red-50 hover:bg-red-100 text-red-600 font-black rounded-xl border border-red-200 transition text-sm">
                    <i class="ri-delete-bin-line"></i> Delete
                </button>
                @endif
                <button type="submit"
                        class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl transition shadow-lg shadow-indigo-200 text-sm flex items-center gap-2">
                    <i class="ri-save-line"></i>
                    {{ $rubric ? 'Update Rubric' : 'Save Rubric' }}
                </button>
            </div>
        </div>

    </form>

    @if($rubric)
    <form id="delete-rubric-form" method="POST" action="{{ route('professor.tasks.rubric.destroy', $task->id) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>
</div>

<script>
function rubricBuilder() {
    return {
        rubricName:        @json(old('name', $rubric?->name ?? '')),
        rubricDescription: @json(old('description', $rubric?->description ?? '')),
        autoGrade:         {{ ($rubric?->auto_grade_enabled ?? true) ? 'true' : 'false' }},
        criteria:          @json($existingCriteria),
        nextUid:           {{ max(100, count($existingCriteria ?? []) + 1) }},
        nextLevelUid:      1000,

        init() {
            if (!this.criteria || this.criteria.length === 0) {
                this.criteria = [];
                this.addCriterion();
            }
        },

        addCriterion() {
            this.criteria.push({
                uid:         this.nextUid++,
                name:        '',
                description: '',
                levels: [
                    { uid: this.nextLevelUid++, label: 'Excellent',          points: 5, description: '' },
                    { uid: this.nextLevelUid++, label: 'Good',               points: 4, description: '' },
                    { uid: this.nextLevelUid++, label: 'Satisfactory',       points: 3, description: '' },
                    { uid: this.nextLevelUid++, label: 'Needs Improvement',  points: 1, description: '' },
                ]
            });
        },

        removeCriterion(uid) {
            if (this.criteria.length <= 1) return;
            this.criteria = this.criteria.filter(c => c.uid !== uid);
        },

        addLevel(criterion) {
            criterion.levels.push({ uid: this.nextLevelUid++, label: 'New Level', points: 0, description: '' });
        },

        removeLevel(criterion, uid) {
            if (criterion.levels.length <= 1) return;
            criterion.levels = criterion.levels.filter(l => l.uid !== uid);
        },

        maxPoints(criterion) {
            if (!criterion.levels || criterion.levels.length === 0) return 0;
            return criterion.levels.reduce((max, l) => Math.max(max, parseInt(l.points) || 0), 0);
        },

        totalPoints() {
            if (!this.criteria) return 0;
            return this.criteria.reduce((sum, c) => sum + this.maxPoints(c), 0);
        },

        submitForm() {
            if (!this.rubricName || !this.rubricName.trim()) {
                alert('Please enter a rubric title.');
                return;
            }
            if (!this.criteria || this.criteria.length === 0) {
                alert('Please add at least one criterion.');
                return;
            }
            for (let i = 0; i < this.criteria.length; i++) {
                if (!this.criteria[i].name || !this.criteria[i].name.trim()) {
                    alert(`Criterion #${i + 1} needs a name.`);
                    return;
                }
            }
            this.$refs.criteriaJson.value = JSON.stringify(this.criteria);
            this.$refs.form.submit();
        }
    };
}

</script>

</x-app-layout>