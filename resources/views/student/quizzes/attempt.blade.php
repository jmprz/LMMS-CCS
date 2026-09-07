<x-app-layout>
    <style>
        :root {
            --system-primary: #383838;
            --system-bg: #f4f7f9;
        }

        nav,
        header,
        footer {
            display: none !important;
        }

        main {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }


        /* 1. STICKY HEADER WRAPPER (stays in normal flow — no manual offset needed) */
        .quiz-sticky-header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: #f8fafc;
            padding: 0.75rem 0;
        }

        /* 2. MATCHING YOUR SUBJECT HEADER STYLE */
        .subject-style-header {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        @media (min-width: 640px) {
            .subject-style-header {
                padding: 1.25rem 2rem;
            }
        }

        .quiz-container {
            background-color: var(--system-bg);
            min-height: 100vh;
            padding-top: 1.5rem;
            padding-bottom: 100px;
        }

        .content-limiter {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* 3. QUESTION CARD REFINEMENT */
        .question-card {
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            background: #ffffff;
            scroll-margin-top: 200px;
            transition: all 0.2s ease;
        }

        .question-card.answered {
            border-left: 6px solid var(--system-primary);
        }

        /* 4. OPTION BUTTONS */
        .option-wrapper {
            position: relative;
            margin-bottom: 10px;
        }

        .option-wrapper input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .option-label {
            display: block;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            transition: 0.2s;
            font-weight: 500;
            color: #374151;
            z-index: 1;
        }

        @media (min-width: 640px) {
            .option-label {
                padding: 14px 20px;
            }
        }

        .option-wrapper input:checked+.option-label {
            background-color: #f9fafb;
            border-color: var(--system-primary);
            color: var(--system-primary);
            box-shadow: inset 0 0 0 1px var(--system-primary);
        }

        #timer.warning {
            color: #dc2626;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }

        /* Disable Finish Button State */
        .btn-disabled {
            opacity: 0.4;
            pointer-events: none;
            filter: grayscale(1);
        }
    </style>

    <div x-data="{ 
    state: '{{ $completed ? 'results' : ((($notYetAvailable ?? false) || ($deadlinePassed ?? false)) ? 'locked' : 'pre-start') }}', 
    score: {{ $studentScore ?? 0 }} 
}" x-cloak>

        <div x-show="state === 'locked'" class="min-h-screen flex items-center justify-center bg-[#f4f7f9] p-4 sm:p-6">
            <div class="bg-white p-6 sm:p-10 rounded-[24px] sm:rounded-[32px] shadow-xl border border-gray-100 max-w-lg w-full text-center">
                <div
                    class="w-16 h-16 sm:w-20 sm:h-20 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-5 sm:mb-6 border border-red-100">
                    <i class="ri-lock-2-line text-3xl sm:text-4xl text-red-500"></i>
                </div>
                @if($notYetAvailable ?? false)
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 mb-2 leading-tight">Not Yet Available</h1>
                    <p class="text-gray-500 font-medium mb-2 leading-relaxed text-sm sm:text-base">
                        Topic: <span class="text-black font-bold">{{ $quiz->title }}</span><br>
                        This quiz opens on
                        <span class="text-black font-bold">{{ \Carbon\Carbon::parse($quiz->published_at)->format('M d, Y g:i A') }}</span>.
                    </p>
                @else
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 mb-2 leading-tight">Deadline Passed</h1>
                    <p class="text-gray-500 font-medium mb-2 leading-relaxed text-sm sm:text-base">
                        Topic: <span class="text-black font-bold">{{ $quiz->title }}</span><br>
                        The deadline for this quiz was
                        <span class="text-black font-bold">{{ \Carbon\Carbon::parse($quiz->expires_at)->format('M d, Y g:i A') }}</span>.
                    </p>
                @endif
                <p class="text-gray-400 text-xs mt-5">Contact your professor if you believe this is a mistake.</p>
            </div>
        </div>

        <div x-show="state === 'pre-start'" class="min-h-screen flex items-center justify-center bg-[#f4f7f9] p-4 sm:p-6">
            <div class="bg-white p-6 sm:p-10 rounded-[24px] sm:rounded-[32px] shadow-xl border border-gray-100 max-w-lg w-full text-center">
                <div
                    class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-5 sm:mb-6 border border-gray-100">
                    <i class="ri-timer-flash-line text-3xl sm:text-4xl text-[#383838]"></i>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 mb-2 leading-tight">Ready to start?</h1>
                <p class="text-gray-500 font-medium mb-6 sm:mb-8 leading-relaxed text-sm sm:text-base">
                    Topic: <span class="text-black font-bold">{{ $quiz->title }}</span><br>
                    Time Limit: <span class="text-black font-bold">{{ $quiz->time_limit }} minutes</span>
                </p>

                <button @click="state = 'quiz'; startTimer(); window.parent.postMessage('lock-modal', '*')"
                    class="w-full bg-[#383838] hover:bg-black text-white px-8 py-3.5 sm:py-4 rounded-2xl font-bold transition-all shadow-lg active:scale-95">
                    START ATTEMPT
                </button>
            </div>
        </div>

        <div x-show="state === 'quiz'">
            <div class="quiz-sticky-header">
                <div class="container mt-3 sm:mt-6 mx-auto px-3 sm:px-4">
                    <div class="subject-style-header">
                        <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-4">
                            <div class="min-w-0">
                                <h1 class="text-base sm:text-xl md:text-2xl font-black text-gray-900 mb-1.5 sm:mb-2 tracking-tight leading-snug break-words">
                                    {{ $quiz->title }}
                                    <span class="text-gray-400 font-light mx-1 sm:mx-2">|</span>
                                    <span class="text-[#383838]">
                                        {{ $quiz->labSession->subject_name ?? 'No Subject' }}
                                        ({{ $quiz->labSession->program ?? 'N/A' }} -
                                        {{ $quiz->labSession->year_level ?? 'N/A' }}{{ $quiz->labSession->section ?? 'N/A' }})
                                    </span>
                                </h1>
                                <div class="flex flex-wrap gap-1.5 sm:gap-2">
                                    <span
                                        class="inline-flex items-center px-2.5 sm:px-4 py-1 sm:py-1.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                                        <i class="ri-question-line mr-1.5 sm:mr-2"></i> TOTAL QUESTIONS:
                                        {{ $quiz->questions->count() }}
                                    </span>
                                    <span
                                        class="inline-flex items-center px-2.5 sm:px-4 py-1 sm:py-1.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                                        <i class="ri-bar-chart-line mr-1.5 sm:mr-2"></i> PROGRESS: <span
                                            id="progressText">0</span>%
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 sm:gap-6 shrink-0">
                                <div class="text-right">
                                    <span
                                        class="block text-[9px] sm:text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Time
                                        Remaining</span>
                                    <h2 id="timer"
                                        class="text-xl sm:text-2xl md:text-3xl font-black text-[#383838] tracking-tighter tabular-nums">--:--
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="quiz-container">
                <div class="container content-limiter px-4">
                    <form action="{{ route('student.quizzes.submit', $quiz->id) }}" method="POST" id="quizForm"
                        @submit.prevent="submitQuiz($event.target)">
                        @csrf
                        <input type="hidden" name="start_time" value="{{ now() }}">

                        @foreach($questions as $index => $question)
                            <div class="card question-card mb-4 sm:mb-6 shadow-sm border-0" id="q_{{ $question->id }}">
                                <div class="card-body p-4 sm:p-6 md:p-8">
                                    <div class="flex flex-wrap justify-between items-center gap-2 mb-4 sm:mb-6">
                                        <span
                                            class="text-[10px] sm:text-[11px] font-black text-gray-400 uppercase tracking-widest">Question
                                            {{ $index + 1 }}</span>
                                        <span
                                            class="text-[10px] sm:text-[11px] px-2.5 sm:px-3 py-1 bg-[#383838] text-white rounded-md border border-gray-100">{{ str_replace('_', ' ', $question->type) }}</span>
                                    </div>

                                    <h4 class="text-base sm:text-lg md:text-xl font-bold text-gray-900 mb-5 sm:mb-8 leading-relaxed">
                                        {{ $question->question_text }}
                                    </h4>

                                    <div class="space-y-2.5 sm:space-y-3 pt-4 sm:pt-6 border-t border-gray-100">
                                        @php $qType = strtoupper(trim($question->type)); @endphp

                                        @if($qType == 'IDENTIFICATION')
                                            <input type="text" name="answers[{{ $question->id }}]"
                                                class="w-full p-3 sm:p-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#383838] focus:border-[#383838] transition-all text-sm sm:text-base"
                                                placeholder="Enter your answer here..."
                                                oninput="updateProgress('{{ $question->id }}', 'text')">
                                        @elseif($qType == 'SELECT_ALL' || $qType == 'MULTIPLE_CHOICE_MULTIPLE')
                                            @foreach($question->options as $option)
                                                <div
                                                    class="option-wrapper flex items-center gap-3 p-2.5 sm:p-3 rounded-lg border border-gray-50 mb-2">
                                                    <input type="checkbox" name="answers[{{ $question->id }}][]"
                                                        value="{{ $option->option_text }}"
                                                        onchange="updateProgress('{{ $question->id }}', 'check')">
                                                    <div class="option-label text-sm font-medium">{{ $option->option_text }}</div>
                                                </div>
                                            @endforeach
                                        @else
                                            @foreach($question->options as $option)
                                                <div
                                                    class="option-wrapper flex items-center gap-3 p-2.5 sm:p-3 rounded-lg border border-gray-50 mb-2">
                                                    <input type="radio" name="answers[{{ $question->id }}]"
                                                        value="{{ $option->option_text }}"
                                                        onchange="updateProgress('{{ $question->id }}', 'radio')">
                                                    <div class="option-label text-sm font-medium">{{ $option->option_text }}</div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="mt-8 sm:mt-12 p-5 sm:p-6 md:p-8 bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 sm:mb-10">
                            <div class="flex flex-col md:flex-row justify-between items-center gap-6 sm:gap-8">
                                <div class="w-full md:w-2/3">
                                    <h6 class="text-[10px] sm:text-[11px] font-black text-gray-400 uppercase tracking-widest mb-4 sm:mb-6">
                                        Question Review</h6>
                                    <div class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 gap-2 sm:gap-3">
                                        @foreach($questions as $index => $q)
                                            <a href="#q_{{ $q->id }}" id="nav_q_{{ $q->id }}"
                                                class="nav-dot h-9 w-9 sm:h-10 sm:w-10 flex items-center justify-center rounded-lg border border-gray-200 font-bold text-gray-500 hover:border-[#383838] transition-all no-underline text-xs">
                                                {{ $index + 1 }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                <div
                                    class="w-full md:w-1/3 text-center md:text-right border-t md:border-t-0 md:border-l border-gray-100 pt-6 sm:pt-8 md:pt-0 md:pl-8">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">
                                        Submission</p>
                                    <button type="submit" id="finishBtn"
                                        class="btn-disabled w-full bg-[#383838] hover:bg-black text-white px-6 sm:px-8 py-3.5 sm:py-4 rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95">
                                        FINISH ATTEMPT
                                    </button>
                                    <p id="completionWarning"
                                        class="mt-3 text-[10px] font-bold text-red-500 uppercase tracking-tighter">
                                        Please answer all questions to submit</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="state === 'results'" id="resultsScreen"
            class="min-h-screen flex items-center justify-center bg-[#f4f7f9] p-4 sm:p-6" x-cloak>
            <div class="bg-white p-6 sm:p-12 rounded-[28px] sm:rounded-[40px] shadow-2xl border border-gray-100 max-w-lg w-full text-center">
                <div
                    class="w-20 h-20 sm:w-24 sm:h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 sm:mb-8 border-4 border-white shadow-inner">
                    <i class="ri-checkbox-circle-fill text-4xl sm:text-5xl text-green-500"></i>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-gray-900 mb-2">Quiz Complete!</h2>
                <p class="text-gray-400 font-bold uppercase tracking-[0.2em] text-[10px] mb-8 sm:mb-10">Performance Summary</p>

                <div class="bg-gray-50 rounded-3xl p-6 sm:p-8 mb-8 sm:mb-10 border border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Your Final
                        Score</span>
                    <div class="flex items-center justify-center gap-3">
                        <span class="text-5xl sm:text-6xl font-black text-gray-900" x-text="score"></span>
                        <span class="text-xl sm:text-2xl font-bold text-gray-300">/ {{ $quiz->questions->sum('points') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let timeLeft = {{ $quiz->time_limit * 60 }};
        let countdown;
        const totalQuestions = {{ $quiz->questions->count() }};
        let deadline;

        function startTimer() {
            const timerDisplay = document.getElementById('timer');
            deadline = Date.now() + ({{ $quiz->time_limit }} * 60 * 1000);

            countdown = setInterval(() => {
                const remainingMs = deadline - Date.now();

                if (remainingMs <= 0) {
                    clearInterval(countdown);
                    timerDisplay.textContent = '0:00';
                    lockQuizInputs();
                    submitQuiz(document.getElementById('quizForm'), true);
                    return;
                }

                const remainingSec = Math.floor(remainingMs / 1000);
                const minutes = Math.floor(remainingSec / 60);
                const seconds = remainingSec % 60;
                timerDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            }, 1000);
        }

        async function submitQuiz(form, isAutoSubmit = false) {
            const finishBtn = document.getElementById('finishBtn');
            if (!isAutoSubmit && (!finishBtn || finishBtn.disabled)) return;

            // UI Feedback
            finishBtn.disabled = true;
            finishBtn.innerText = "SUBMITTING...";

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();
                console.log("Submission Result:", result);

                if (result.success) {
                    if (countdown) clearInterval(countdown);

                    // 1. UPDATE ALPINE DATA
                    const root = document.querySelector('[x-data]');
                    if (root && window.Alpine) {
                        const alpineData = Alpine.$data(root);
                        alpineData.score = result.score;
                        alpineData.state = 'results';
                    }

                    // 2. FORCE UI SHOW (Bypass Alpine if needed)
                    // This manually hides the quiz and shows results in case Alpine is stuck
                    const quizSection = document.querySelector('[x-show="state === \'quiz\'"]');
                    const resultsSection = document.querySelector('[x-show="state === \'results\'"]');

                    if (quizSection) quizSection.style.display = 'none';
                    if (resultsSection) {
                        resultsSection.style.display = 'flex';
                        resultsSection.removeAttribute('x-cloak');
                        // Manually set the score text
                        const scoreSpan = resultsSection.querySelector('[x-text="score"]');
                        if (scoreSpan) scoreSpan.innerText = result.score;
                    }

                    // 3. UNLOCK MODAL IN PARENT
                    window.parent.postMessage('unlock-modal', '*');

                } else {
                    throw new Error(result.message || 'Submission failed');
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Error submitting quiz. Please try again.");
                finishBtn.disabled = false;
                finishBtn.innerText = "FINISH ATTEMPT";
            }
        }

        function lockQuizInputs() {
            const form = document.getElementById('quizForm');
            if (!form) return;
            form.querySelectorAll('input, textarea, select, button').forEach(el => el.disabled = true);
        }

        function updateProgress(qId, type) {
            const finishBtn = document.getElementById('finishBtn');
            const warningText = document.getElementById('completionWarning');

            let isAnswered = false;
            if (type === 'text') {
                const input = document.querySelector(`input[name="answers[${qId}]"]`);
                isAnswered = input && input.value.trim().length > 0;
            } else if (type === 'check') {
                // FIXED SELECTOR: removed extra brackets and quotes
                isAnswered = document.querySelectorAll(`input[name="answers[${qId}][]"]:checked`).length > 0;
            } else {
                isAnswered = document.querySelector(`input[name="answers[${qId} text]"]:checked`) !== null ||
                    document.querySelector(`input[name="answers[${qId}]"]:checked`) !== null;
            }

            const card = document.getElementById('q_' + qId);
            const nav = document.getElementById('nav_q_' + qId);

            if (isAnswered) {
                card.classList.add('answered');
                if (nav) nav.classList.add('bg-[#383838]', 'text-white', 'border-[#383838]');
            } else {
                card.classList.remove('answered');
                if (nav) nav.classList.remove('bg-[#383838]', 'text-white', 'border-[#383838]');
            }

            const answeredCount = document.querySelectorAll('.question-card.answered').length;
            const progressText = document.getElementById('progressText');
            if (progressText) progressText.innerText = Math.round((answeredCount / totalQuestions) * 100);

            // ACTIVATE BUTTON
            if (answeredCount === totalQuestions) {
                finishBtn.classList.remove('btn-disabled');
                finishBtn.disabled = false; // Add this line to be sure
                if (warningText) warningText.style.display = 'none';
            } else {
                finishBtn.classList.add('btn-disabled');
                finishBtn.disabled = true; // Add this line to be sure
                if (warningText) warningText.style.display = 'block';
            }
        }
    </script>
</x-app-layout>