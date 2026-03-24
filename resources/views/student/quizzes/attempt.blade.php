<x-app-layout>
<style>
    .sticky-timer { position: sticky; top: 1rem; z-index: 1020; }
    .question-card { transition: all 0.2s ease; border-left: 5px solid transparent; border-radius: 12px; }
    .question-card.answered { border-left: 5px solid #198754; background-color: #fdfdfd; }
    .nav-btn.active { background: #0d6efd !important; color: white !important; border-color: #0d6efd !important; }
    
    .custom-option label { cursor: pointer; transition: 0.2s; border-radius: 8px; margin-bottom: 2px; }
    .form-check-input:checked + label { 
        background-color: #0d6efd !important; 
        color: white !important; 
        border-color: #0d6efd !important; 
        box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
    }
    .identification-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-md-4 order-md-2 mb-4">
            <div class="sticky-timer">
                <div class="card shadow border-0 mb-3">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-stopwatch me-2"></i>Status</h5>
                    </div>
                    <div class="card-body text-center py-4">
                        <h2 id="timer" class="display-5 fw-bold mb-3">--:--</h2>
                        <div class="progress mb-2" style="height: 10px; border-radius: 5px;">
                            <div id="quizProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%"></div>
                        </div>
                        <span id="progressText" class="small fw-bold text-muted text-uppercase">0 / {{ $quiz->questions->count() }} Answered</span>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3">
                        <button type="button" onclick="confirmSubmission()" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                            FINISH ATTEMPT
                        </button>
                    </div>
                </div>

                <div class="card shadow border-0">
                    <div class="card-header bg-light small fw-bold text-uppercase">Navigation</div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        @foreach($quiz->questions as $index => $q)
                            <a href="#q_{{ $q->id }}" id="nav_q_{{ $q->id }}" class="nav-btn btn btn-outline-secondary btn-sm fw-bold" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                {{ $index + 1 }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 order-md-1">
            <div class="mb-4">
                <h1 class="fw-extrabold text-dark mb-1">{{ $quiz->title }}</h1>
                <p class="text-muted"><i class="fas fa-info-circle me-1"></i> Answer all questions carefully. Time will auto-submit when finished.</p>
            </div>

            <form action="{{ route('student.quizzes.submit', $quiz->id) }}" method="POST" id="quizForm">
                @csrf
                <input type="hidden" name="start_time" value="{{ now() }}">

                @foreach($quiz->questions as $index => $question)
                    <div class="card shadow-sm mb-4 question-card border-0" id="q_{{ $question->id }}">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge rounded-pill bg-light text-primary px-3 py-2 fw-bold">Question {{ $index + 1 }}</span>
                                <span class="text-uppercase small fw-bold text-muted">{{ str_replace('_', ' ', $question->type) }}</span>
                            </div>
                            
                            <h5 class="mb-4 fw-bold text-dark" style="line-height: 1.5;">{{ $question->question_text }}</h5>

                            <div class="options-container pt-3 border-top">
                                
                             {{-- DEBUG: Remove this line once it works --}}
    @php
        $qType = strtoupper(trim($question->type));
    @endphp

    @if($qType == 'IDENTIFICATION')
        <input type="text" name="answers[{{ $question->id }}]" class="form-control" oninput="updateProgress('{{ $question->id }}', 'text')">

    @elseif($qType == 'SELECT_ALL' || $qType == 'MULTIPLE_CHOICE_MULTIPLE')
        @foreach($question->options as $option)
            <div class="form-check custom-option mb-2">
                <input class="form-check-input d-none" type="checkbox" name="answers[{{ $question->id }}][]" id="opt_{{ $option->id }}" value="{{ $option->option_text }}" onchange="updateProgress('{{ $question->id }}', 'check')">
                <label class="btn btn-outline-secondary w-100 text-start" for="opt_{{ $option->id }}">{{ $option->option_text }}</label>
            </div>
        @endforeach

    @else {{-- Default to Radio (True/False or Multiple Choice) --}}
        @foreach($question->options as $option)
            <div class="form-check custom-option mb-2">
                <input class="form-check-input d-none" type="radio" name="answers[{{ $question->id }}]" id="opt_{{ $option->id }}" value="{{ $option->option_text }}" onchange="updateProgress('{{ $question->id }}', 'radio')">
                <label class="btn btn-outline-secondary w-100 text-start" for="opt_{{ $option->id }}">{{ $option->option_text }}</label>
            </div>
        @endforeach
    @endif

                            </div>
                        </div>
                    </div>
                @endforeach
            </form>
        </div>
    </div>
</div>

<script>
    // Logic remains mostly same but uses better selectors
    let timeLeft = Number("{{ $quiz->time_limit }}") * 60;
    const timerElement = document.getElementById('timer');
    const quizForm = document.getElementById('quizForm');

    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            window.onbeforeunload = null;
            quizForm.submit();
        } else {
            let mins = Math.floor(timeLeft / 60);
            let secs = timeLeft % 60;
            timerElement.innerHTML = `${mins}:${secs.toString().padStart(2, '0')}`;
            if (timeLeft < 60) timerElement.classList.add('text-danger', 'animate-pulse');
            timeLeft--;
        }
    }, 1000);

    function updateProgress(qId, type) {
        let isAnswered = false;
        
        if (type === 'text') {
            const val = document.querySelector(`input[name="answers[${qId}]"]`).value;
            isAnswered = val.trim().length > 0;
        } else if (type === 'check') {
            isAnswered = document.querySelectorAll(`input[name="answers[${qId}][]"]:checked`).length > 0;
        } else {
            isAnswered = document.querySelector(`input[name="answers[${qId}]"]:checked`) !== null;
        }

        const card = document.getElementById('q_' + qId);
        const nav = document.getElementById('nav_q_' + qId);

        if (isAnswered) {
            card.classList.add('answered');
            nav.classList.add('active');
        } else {
            card.classList.remove('answered');
            nav.classList.remove('active');
        }

        const total = {{ $quiz->questions->count() }};
        const answeredCount = document.querySelectorAll('.question-card.answered').length;
        document.getElementById('quizProgress').style.width = (answeredCount / total * 100) + '%';
        document.getElementById('progressText').innerText = `${answeredCount} / ${total} Answered`;
    }

    function confirmSubmission() {
        if (confirm("Submit your quiz now? You cannot undo this action.")) {
            window.onbeforeunload = null;
            quizForm.submit();
        }
    }
</script>
</x-app-layout>