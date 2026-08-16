   @forelse($session->quizzes ?? [] as $quiz)
                                    <div class="bg-white p-5 rounded-2xl border border-gray-100 flex flex-col justify-between group hover:border-[#383838] transition-all duration-300 shadow-sm"
                                        x-show="!deletedQuizzes.includes({{ $quiz->id }})"
                                        x-transition:leave="transition ease-in duration-200"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95">

                                        <div>
                                            <div class="flex justify-between items-start mb-4">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center border group-hover:bg-black group-hover:text-white transition">
                                                    <i class="ri-timer-line text-lg"></i>
                                                </div>
                                                <span
                                                    class="bg-gray-100 text-[#383838] px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                                                    {{ $quiz->questions->count() }} PTS
                                                </span>
                                            </div>

                                            <h4 class="font-bold text-gray-900 mb-1 group-hover:text-black transition">
                                                {{ $quiz->title }}
                                            </h4>

                                            <div class="space-y-2 mt-4">
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-calendar-todo-line mr-2"></i>
                                                  {{ $quiz->expires_at ? \Carbon\Carbon::parse($quiz->expires_at)->format('M d, h:i A') : 'No deadline set' }}
                                                </div>
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-time-line mr-2"></i>
                                                    {{ $quiz->time_limit }} Mins Duration
                                                </div>
                                                <div class="flex items-center text-gray-500 text-[11px] font-medium">
                                                    <i class="ri-group-line mr-2"></i>
                                                    {{ $quiz->attempts->count() }} Answered
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="mt-6 flex items-center justify-between gap-3 pt-4 border-t border-gray-50">
                                            {{-- View Results Button (Placed prominently on the left side) --}}
                                            <button type="button"
                                                @click="selectedQuiz = {{ json_encode($quiz) }}; scores = {{ json_encode($quiz->attempts()->with('user')->get()) }}"
                                                class="flex-1 bg-gray-50 text-[#383838] border border-gray-200 py-2.5 px-4 rounded-xl text-[10px] font-black uppercase hover:bg-[#383838] hover:text-white transition-all tracking-widest cursor-pointer text-center">
                                                View Results
                                            </button>

                                            <a href="{{ route('professor.quizzes.export-scores', $quiz) }}"
                                                class="flex-shrink-0 bg-[#383838] text-white py-2.5 px-4 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all flex items-center gap-1.5"
                                                title="Export quiz scores to Excel">
                                                <i class="ri-file-excel-2-line text-xs"></i>
                                                Export
                                            </a>
                                            <button type=" button" @if($quiz->attempts->count() > 0) disabled
                                            title="Locked — quiz has student attempts" @endif
                                                @click="$dispatch('open-quiz-editor', { url: '{{ route('professor.quizzes.edit', $quiz->id) }}' })"
                                                class="text-gray-500 hover:text-gray-900 text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1 bg-transparent border-0 cursor-pointer p-2 rounded-lg hover:bg-gray-50 flex-shrink-0">
                                                <i class="ri-edit-line text-xs"></i> Edit
                                            </button>
                                            {{-- Inline Asynchronous Delete Control (Aligned right) --}}
                                            <button type="button"
                                                @click="removeQuiz({{ $quiz->id }}, '{{ route('professor.quizzes.destroy', $quiz->id) }}')"
                                                class="text-red-500 hover:text-red-700 text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1 bg-transparent border-0 cursor-pointer p-2 rounded-lg hover:bg-red-50/50 flex-shrink-0">
                                                <i class="ri-delete-bin-line text-xs"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="col-span-full py-20 border-2 border-dashed border-gray-100 rounded-3xl text-center">
                                        <i class="ri-timer-flash-line text-4xl text-gray-200 mb-3 block"></i>
                                        <p class="text-gray-400 italic text-sm">No quizzes available for this session.</p>
                                    </div>
                                @endforelse