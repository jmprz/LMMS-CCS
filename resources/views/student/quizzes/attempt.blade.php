<x-app-layout>
<style>
    :root {
        --system-primary: #383838;
        --system-bg: #f4f7f9;
    }

    /* 1. FIXED HEADER WRAPPER */
    .quiz-sticky-header {
        position: fixed;
        top: 64px; 
        left: 0;
        right: 0;
        z-index: 40;
        background: #f8fafc; 
        padding: 1rem 0;
    }

    /* 2. MATCHING YOUR SUBJECT HEADER STYLE */
    .subject-style-header {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border-radius: 1rem;
        padding: 1.25rem 2rem;
        max-width: 1000px;
        margin: 0 auto;
    }

    .quiz-container { 
        background-color: var(--system-bg); 
        min-height: 100vh; 
        padding-top: 160px; 
        padding-bottom: 120px;
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
    .option-wrapper { position: relative; margin-bottom: 10px; }
    .option-wrapper input {
        position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; z-index: 2;
    }
    .option-label {
        display: block; padding: 14px 20px; background: #fff;
        border: 1px solid #e5e7eb; border-radius: 0.75rem;
        transition: 0.2s; font-weight: 500; color: #374151; z-index: 1;
    }
    .option-wrapper input:checked + .option-label {
        background-color: #f9fafb; 
        border-color: var(--system-primary); 
        color: var(--system-primary);
        box-shadow: inset 0 0 0 1px var(--system-primary);
    }

    #timer.warning { color: #dc2626; animation: pulse 1s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

    /* Disable Finish Button State */
    .btn-disabled {
        opacity: 0.4;
        pointer-events: none;
        filter: grayscale(1);
    }
</style>

<div id="successModal" class="hidden">
    <div class="modal-card">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="ri-checkbox-circle-fill text-5xl"></i>
        </div>
        
        <h2 class="text-2xl font-black text-gray-900 mb-2">Quiz Submitted!</h2>
        <p class="text-gray-500 text-sm mb-8">Your attempt has been recorded successfully.</p>

        <div class="grid grid-cols-2 gap-3 mb-8">
            <div class="stat-box col-span-2">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Final Score</span>
                <span class="text-2xl font-black text-[#383838]"><span id="modalScore">--</span> / {{ $quiz->questions->count() }}</span>
            </div>
            <div class="stat-box">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Time Spent</span>
                <span id="modalTimeSpent" class="text-lg font-bold text-gray-700">00:00</span>
            </div>
            <div class="stat-box">
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Remaining</span>
                <span id="modalTimeLeft" class="text-lg font-bold text-gray-700">00:00</span>
            </div>
        </div>

        <button type="button" onclick="finalizeRedirect()" class="w-full bg-[#383838] text-white py-4 rounded-xl font-bold hover:bg-black transition-all">
            RETURN TO DASHBOARD
        </button>
    </div>
</div>

<div class="quiz-sticky-header">
    <div class="container mt-6">
        <div class="subject-style-header">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
               <h1 class="text-2xl font-black text-gray-900 mb-2 tracking-tight">
    {{ $quiz->title }} 
    <span class="text-gray-400 font-light mx-2">|</span> 
    <span class="text-[#383838]">
        {{ $quiz->labSession->subject_name ?? 'No Subject' }}
        ({{ $quiz->labSession->program ?? 'N/A' }} - {{ $quiz->labSession->year_level ?? 'N/A' }}{{ $quiz->labSession->section ?? 'N/A' }})
    </span>
</h1>
                    
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                            <i class="ri-question-line mr-2"></i> TOTAL QUESTIONS: {{ $quiz->questions->count() }}
                        </span>
                        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                            <i class="ri-bar-chart-line mr-2"></i> PROGRESS: <span id="progressText">0</span>%
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Time Remaining</span>
                        <h2 id="timer" class="text-3xl font-black text-[#383838] tracking-tighter tabular-nums">--:--</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="quiz-container">
    <div class="container content-limiter px-4">
        <form action="{{ route('student.quizzes.submit', $quiz->id) }}" method="POST" id="quizForm">
            @csrf
            <input type="hidden" name="start_time" value="{{ now() }}">

            @foreach($quiz->questions as $index => $question)
                <div class="card question-card mb-6 shadow-sm border-0" id="q_{{ $question->id }}">
                    <div class="card-body p-8">
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Question {{ $index + 1 }}</span>
                            <span class="text-[11px] px-3 py-1 bg-[#383838] text-white rounded-mdgray-900 text-[10px] font-bold rounded-md border border-gray-100">{{ str_replace('_', ' ', $question->type) }}</span>
                        </div>
                        
                        <h4 class="text-xl font-bold text-gray-900 mb-8 leading-relaxed">{{ $question->question_text }}</h4>

                        <div class="space-y-3 pt-6 border-t border-gray-100">
                            @php $qType = strtoupper(trim($question->type)); @endphp

                            @if($qType == 'IDENTIFICATION')
                                <input type="text" name="answers[{{ $question->id }}]" class="w-full p-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#383838] focus:border-[#383838] transition-all" placeholder="Enter your answer here..." oninput="updateProgress('{{ $question->id }}', 'text')">
                            
                            @elseif($qType == 'SELECT_ALL' || $qType == 'MULTIPLE_CHOICE_MULTIPLE')
                                @foreach($question->options as $option)
                                    <div class="option-wrapper">
                                        <input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option->option_text }}" onchange="updateProgress('{{ $question->id }}', 'check')">
                                        <div class="option-label">{{ $option->option_text }}</div>
                                    </div>
                                @endforeach

                            @else
                                @foreach($question->options as $option)
                                    <div class="option-wrapper">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->option_text }}" onchange="updateProgress('{{ $question->id }}', 'radio')">
                                        <div class="option-label">{{ $option->option_text }}</div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </form>

        <div class="mt-12 p-8 bg-white rounded-2xl shadow-sm border border-gray-200 mb-10">
            <div class="flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="w-full md:w-2/3">
                    <h6 class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-6">Question Review</h6>
                    <div class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 gap-3">
                        @foreach($quiz->questions as $index => $q)
                            <a href="#q_{{ $q->id }}" id="nav_q_{{ $q->id }}" class="nav-dot h-10 w-10 flex items-center justify-center rounded-lg border border-gray-200 font-bold text-gray-500 hover:border-[#383838] transition-all no-underline">
                                {{ $index + 1 }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="w-full md:w-1/3 text-center md:text-right border-t md:border-t-0 md:border-l border-gray-100 pt-8 md:pt-0 md:pl-8">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Submission</p>
                    <button type="button" id="finishBtn" onclick="confirmSubmission()" class="btn-disabled w-full bg-[#383838] hover:bg-black text-white px-8 py-4 rounded-xl font-bold text-sm transition-all shadow-lg active:scale-95">
                        FINISH ATTEMPT
                    </button>
                    <p id="completionWarning" class="mt-3 text-[10px] font-bold text-red-500 uppercase tracking-tighter">Please answer all questions to submit</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let timeLeft = Number("{{ $quiz->time_limit }}") * 60;
    const timerElement = document.getElementById('timer');
    const quizForm = document.getElementById('quizForm');
    const finishBtn = document.getElementById('finishBtn');
    const warningText = document.getElementById('completionWarning');
    const totalQuestions = {{ $quiz->questions->count() }};

    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            quizForm.submit();
        } else {
            let mins = Math.floor(timeLeft / 60);
            let secs = timeLeft % 60;
            timerElement.innerHTML = `${mins}:${secs.toString().padStart(2, '0')}`;
            if (timeLeft < 300) timerElement.classList.add('warning');
            timeLeft--;
        }
    }, 1000);

    function updateProgress(qId, type) {
        let isAnswered = false;
        if (type === 'text') {
            isAnswered = document.querySelector(`input[name="answers[${qId}]"]`).value.trim().length > 0;
        } else if (type === 'check') {
            isAnswered = document.querySelectorAll(`input[name="answers[${qId}][]"]:checked`).length > 0;
        } else {
            isAnswered = document.querySelector(`input[name="answers[${qId}]"]:checked`) !== null;
        }

        const card = document.getElementById('q_' + qId);
        const nav = document.getElementById('nav_q_' + qId);

        if (isAnswered) {
            card.classList.add('answered');
            nav.classList.add('bg-[#383838]', 'text-white', 'border-[#383838]');
        } else {
            card.classList.remove('answered');
            nav.classList.remove('bg-[#383838]', 'text-white', 'border-[#383838]');
        }

        const answeredCount = document.querySelectorAll('.question-card.answered').length;
        document.getElementById('progressText').innerText = Math.round((answeredCount / totalQuestions) * 100);

        // CHECK COMPLETION TO UNLOCK BUTTON
        if (answeredCount === totalQuestions) {
            finishBtn.classList.remove('btn-disabled');
            warningText.classList.add('hidden');
        } else {
            finishBtn.classList.add('btn-disabled');
            warningText.classList.remove('hidden');
        }
    }

    function confirmSubmission() {
        if (confirm("Are you finished with your exam? This action cannot be undone.")) {
            quizForm.submit();
        }
    }
</script>
</x-app-layout>