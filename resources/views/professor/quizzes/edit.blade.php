<x-app-layout>
    <div class="py-12 bg-gray-50/50 min-h-screen" x-data="quizForm()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <fieldset @if($hasAttempts) disabled @endif>
            <form @submit.prevent="submitForm($event.target)" class="space-y-8">
                @csrf

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h2 class="text-2xl font-black text-gray-900 tracking-tight uppercase">Create New Quiz</h2>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">Configure
                                general exam settings</p>
                        </div>
                        <span
                            class="px-4 py-2 bg-gray-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest"
                            x-text="questions.length + ' Questions Total'"></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="md:col-span-2">
                            <label
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Quiz
                                Title</label>
                            <input id="title" name="title" type="text" value="{{ old('title', $quiz->title) }}"
                                class="w-full border-gray-200 bg-gray-50 rounded-2xl p-4 focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none font-bold text-gray-900"
                                placeholder="e.g. Midterm Examination in Web Development" required />
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Time
                                Limit (Minutes)</label>
                            <input id="time_limit" name="time_limit" type="number"
                                value="{{ old('time_limit', $quiz->time_limit) }}"
                                class="w-full border-gray-200 bg-gray-50 rounded-2xl p-4 focus:ring-2 focus:ring-gray-400 focus:border-gray-400  outline-none font-bold text-gray-900"
                                placeholder="60" required />
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Assigned
                                Lab Session</label>
                            <div
                                class="p-4 bg-gray-100 rounded-2xl text-[#383838] font-black text-xs border border-gray-200 uppercase tracking-tight">
                                {{ $quiz->labSession->session_name ?? $quiz->labSession->subject_name }}
                            </div>
                            <input type="hidden" name="lab_session_id" value="{{ $quiz->labSession->id }}">
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Schedule
                                Post</label>
                            <input id="published_at" name="published_at" type="datetime-local"
                                value="{{ $quiz->published_at ? \Carbon\Carbon::parse($quiz->published_at)->format('Y-m-d\TH:i') : '' }}"
                                class="w-full border-gray-200 bg-gray-50 rounded-2xl p-4 focus:ring-2 focus:ring-gray-400 focus:border-gray-400  outline-none font-bold text-xs text-gray-600" />
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Quiz
                                Deadline</label>
                            <input id="expires_at" name="expires_at" type="datetime-local"
                                value="{{ $quiz->expires_at ? \Carbon\Carbon::parse($quiz->expires_at)->format('Y-m-d\TH:i') : '' }}"
                                class="w-full border-gray-200 bg-gray-50 rounded-2xl p-4 focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none font-bold text-xs text-gray-600" />
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <h3 class="font-black text-gray-400 flex items-center uppercase tracking-widest text-[10px] px-2">
                        <i class="fas fa-list-ol me-2"></i> Questionnaire Builder
                    </h3>

                    <template x-for="(question, qIndex) in questions" :key="qIndex">
                        <div
                            class="bg-white p-8 rounded-3xl shadow-sm border border-gray-200 transition-all hover:border-gray-900 group">

                            <div class="flex justify-between items-center mb-6">
                                <div class="flex items-center gap-4">
                                    <span
                                        class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center text-xs font-black"
                                        x-text="qIndex + 1"></span>
                                    <select x-model="question.type" :name="'questions['+qIndex+'][type]'"
                                        @change="handleTypeChange(qIndex)"
                                        class="text-[10px] rounded-xl border-gray-200 bg-gray-50 py-2 px-4 uppercase font-black tracking-widest focus:ring-2 focus:ring-gray-400 focus:border-gray-400">
                                        <option value="multiple">Multiple Choice</option>
                                        <option value="select_all">Select All That Apply</option>
                                        <option value="true_false">True or False</option>
                                        <option value="identification">Identification</option>
                                    </select>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <button type="button" @click="duplicateQuestion(qIndex)"
                                        class="p-2 text-gray-400 hover:text-black hover:bg-gray-100 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                                            </path>
                                        </svg>
                                    </button>
                                    <button type="button" @click="removeQuestion(qIndex)"
                                        class="p-2 text-gray-300 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-6">
                                <div class="col-span-12 md:col-span-10">
                                    <label
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Question
                                        Text</label>
                                    <input type="text" x-model="question.text" :name="'questions['+qIndex+'][text]'"
                                        required
                                        class="w-full border-gray-200 bg-gray-50 rounded-2xl p-4 focus:ring-2 focus:ring-gray-400 focus:border-gray-400  outline-none font-bold text-gray-900 shadow-inner"
                                        placeholder="Enter the question prompt here...">
                                </div>
                                <div class="col-span-12 md:col-span-2">
                                    <label
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Points</label>
                                    <input type="number" x-model="question.points"
                                        :name="'questions['+qIndex+'][points]'" min="1"
                                        class="w-full border-gray-200 bg-gray-50 rounded-2xl p-4 focus:ring-2 focus:ring-gray-400 focus:border-gray-400  outline-none font-bold text-center text-gray-900">
                                </div>
                            </div>

                            <template x-if="question.type === 'multiple' || question.type === 'select_all'">
                                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <template x-for="(option, oIndex) in question.options" :key="oIndex">
                                        <div class="flex items-center space-x-3 p-4 bg-gray-50 rounded-2xl border-2 border-transparent transition-all"
                                            :class="question.type === 'multiple' ? (question.correct == oIndex ? 'border-gray-900 bg-white' : '') : ''">

                                            <input :type="question.type === 'multiple' ? 'radio' : 'checkbox'"
                                                :name="question.type === 'multiple' ? 'questions['+qIndex+'][correct_option]' : 'questions['+qIndex+'][correct_options][]'"
                                                :value="oIndex" x-model="question.correct"
                                                class="w-5 h-5 text-black focus:ring-2 focus:ring-gray-400 focus:border-gray-400  border-gray-300 rounded-full">

                                            <input type="text" x-model="question.options[oIndex]"
                                                :name="'questions['+qIndex+'][options]['+oIndex+']'" required
                                                class="w-full border-none bg-transparent focus:ring-0 text-sm font-bold text-gray-800"
                                                placeholder="Enter option...">
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="question.type === 'true_false'">
                                <div class="mt-8 flex gap-4">
                                    <template x-for="(option, oIndex) in ['True', 'False']" :key="oIndex">
                                        <label
                                            class="flex-1 flex items-center justify-center p-5 bg-gray-50 rounded-2xl border-2 border-transparent cursor-pointer hover:border-black transition-all group-active:scale-95">
                                            <input type="radio" :name="'questions['+qIndex+'][correct_option]'"
                                                :value="option" x-model="question.correct" required
                                                class="w-5 h-5 text-black focus:ring-2 focus:ring-gray-400 focus:border-gray-400  mr-4">
                                            <span class="font-black text-xs uppercase tracking-widest text-gray-700"
                                                x-text="option"></span>
                                            <input type="hidden" :name="'questions['+qIndex+'][options]['+oIndex+']'"
                                                :value="option">
                                        </label>
                                    </template>
                                </div>
                            </template>

                            <template x-if="question.type === 'identification'">
                                <div class="mt-8">
                                    <label
                                        class="text-[10px] font-black text-gray-900 uppercase tracking-widest block mb-2">Expected
                                        Answer (Case Sensitive)</label>
                                    <input type="text" x-model="question.answer" :name="'questions['+qIndex+'][answer]'"
                                        required
                                        class="w-full border-gray-200 bg-gray-50 rounded-2xl p-4 focus:ring-2 focus:ring-gray-400 focus:border-gray-400  outline-none font-black text-gray-900 tracking-tight"
                                        placeholder="Type the exact answer students must provide...">
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="flex justify-between items-center py-10 border-t border-gray-200">
                    <button type="button" @click="addQuestion()"
                        class="px-8 py-4 bg-white border-2 border-[#383838] text-[#383838] rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-[#383838] hover:text-white transition-all active:scale-95">
                        + Add Question
                    </button>
                    @if($hasAttempts)
                        <div
                            class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-4 mb-6 text-sm font-bold">
                            <i class="ri-lock-line mr-2"></i>
                            This quiz already has student attempts and can no longer be edited, to protect existing
                            scores.
                            You can still view its content below.
                        </div>
                    @endif
                    <button type="submit" @if($hasAttempts) disabled @endif
                        class="bg-gray-900 text-white px-10 py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:shadow-2xl hover:shadow-gray-400 transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed">
                        {{ $hasAttempts ? 'Locked (Has Attempts)' : 'Save Changes' }}
                    </button>
                </div>
            </form>
            </fieldset>
        </div>
    </div>

    <script>
        function quizForm() {
            return {
                async submitForm(form) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerText = 'SAVING...';

                    const formData = new FormData(form);
                    formData.append('_method', 'PUT');   // ← spoof it here instead

                    try {
                        const response = await fetch('{{ route('professor.quizzes.update', $quiz->id) }}', {
                            method: 'POST',              // ← genuine POST now
                            headers: { 'Accept': 'application/json' },
                            body: formData,
                        });
                        if (!response.ok) throw new Error('Save failed');
                        window.parent.postMessage('quiz-saved', '*');
                    } catch (err) {
                        alert('Failed to save changes. Please try again.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = 'Save Changes';
                    }
                },
                questions: {!! json_encode($initialQuestions) !!},
                addQuestion() {
                    this.questions.push({ type: 'multiple', text: '', points: 1, options: ['', '', '', ''], correct: 0 });
                },
                removeQuestion(index) {
                    if (this.questions.length > 1) this.questions.splice(index, 1);
                },
                duplicateQuestion(index) {
                    const original = this.questions[index];
                    const copy = JSON.parse(JSON.stringify(original));
                    this.questions.splice(index + 1, 0, copy);
                },
                handleTypeChange(index) {
                    let q = this.questions[index];
                    if (q.type === 'select_all') {
                        q.correct = [];
                    } else if (q.type === 'multiple') {
                        q.correct = 0;
                    }

                    if (q.type === 'true_false') {
                        q.options = ['True', 'False'];
                    } else if (q.type === 'identification') {
                        q.options = [];
                    } else if (q.options.length < 4) {
                        q.options = ['', '', '', ''];
                    }
                }
            }
        }
    </script>
</x-app-layout>