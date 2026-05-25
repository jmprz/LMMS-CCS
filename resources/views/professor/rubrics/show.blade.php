<x-app-layout>
<div class="min-h-screen bg-gray-100 py-8">
<div class="max-w-6xl mx-auto px-4">

    {{-- Back --}}
    <a href="{{ route('professor.classroom.show', $task->labSession->id) }}"
       class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 font-bold">
        <i class="ri-arrow-left-line"></i> Back to Classroom
    </a>

    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black mb-1">📊 Rubric & Grades</h1>
                <p class="text-indigo-200 text-sm">Task: <span class="font-bold text-white">{{ $task->title }}</span></p>
                <p class="text-xs text-indigo-300 mt-1">{{ $task->labSession->subject_name }} — {{ $task->labSession->faculty->name }}</p>
            </div>
            @if($rubric)
            <a href="{{ route('professor.tasks.rubric.create', $task->id) }}"
               class="bg-white/20 hover:bg-white/30 text-white font-black text-sm px-4 py-2 rounded-xl transition flex items-center gap-2">
                <i class="ri-edit-line"></i> Edit Rubric
            </a>
            @else
            <a href="{{ route('professor.tasks.rubric.create', $task->id) }}"
               class="bg-white text-indigo-700 hover:bg-indigo-50 font-black text-sm px-4 py-2 rounded-xl transition flex items-center gap-2">
                <i class="ri-add-line"></i> Create Rubric
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-6 font-bold text-sm flex items-center gap-2">
            <i class="ri-checkbox-circle-fill text-green-600 text-lg"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($rubric)
    {{-- Rubric Summary --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-black text-gray-900">📋 {{ $rubric->name }}</h2>
            <div class="flex items-center gap-3">
                @if($rubric->auto_grade_enabled)
                    <span class="bg-green-100 text-green-700 text-xs font-black px-3 py-1 rounded-full border border-green-200">
                        <i class="ri-robot-line mr-1"></i> Auto-Grade ON
                    </span>
                @else
                    <span class="bg-gray-100 text-gray-500 text-xs font-black px-3 py-1 rounded-full border border-gray-200">
                        Auto-Grade OFF
                    </span>
                @endif
                <span class="bg-indigo-100 text-indigo-700 text-xs font-black px-3 py-1 rounded-full border border-indigo-200">
                    {{ $rubric->total_points }} pts total
                </span>
            </div>
        </div>
        @if($rubric->description)
            <p class="text-sm text-gray-600 mb-4">{{ $rubric->description }}</p>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($rubric->criteria as $i => $criterion)
            @php
                $typeColors = [
                    'ai'      => 'bg-blue-50 border-blue-200 text-blue-700',
                    'text'    => 'bg-purple-50 border-purple-200 text-purple-700',
                    'code'    => 'bg-gray-800 border-gray-700 text-green-400',
                    'keyword' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                    'file'    => 'bg-green-50 border-green-200 text-green-700',
                    'manual'  => 'bg-orange-50 border-orange-200 text-orange-700',
                ];
                $typeLabels = [
                    'ai'      => '🤖 AI Eval',
                    'text'    => '📝 Text AI',
                    'code'    => '💻 Code AI',
                    'keyword' => '🔍 Keyword',
                    'file'    => '📁 File',
                    'manual'  => '✋ Manual',
                ];
                $colorClass = $typeColors[$criterion->checking_type] ?? 'bg-gray-50 border-gray-200 text-gray-700';
            @endphp
            <div class="border {{ $colorClass }} rounded-xl p-4">
                <div class="flex items-start justify-between mb-2">
                    <span class="w-6 h-6 rounded-full bg-black/10 flex items-center justify-center text-xs font-black">{{ $i+1 }}</span>
                    <span class="text-xs font-black px-2 py-0.5 rounded-full bg-black/10">{{ $criterion->max_points }} pts</span>
                </div>
                <p class="font-black text-sm mb-1">{{ $criterion->criterion_name }}</p>
                @if($criterion->description)
                    <p class="text-xs opacity-80 mb-2">{{ $criterion->description }}</p>
                @endif
                <span class="text-xs font-bold">{{ $typeLabels[$criterion->checking_type] ?? 'Unknown' }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Submissions Table --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-black text-gray-900">👥 Student Submissions</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ $submissions->count() }} submission(s) received</p>
        </div>

        @if($submissions->isEmpty())
            <div class="py-16 text-center">
                <i class="ri-inbox-line text-5xl text-gray-200"></i>
                <p class="text-gray-500 font-bold mt-3">No submissions yet</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-black text-gray-500 uppercase tracking-wider">File</th>
                            <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-center text-xs font-black text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($submissions as $submission)
                        @php
                            $grade  = $submission->submissionGrade;
                            $pct    = $grade ? round(($grade->total_score / max(1, $grade->max_score)) * 100) : null;
                            $scoreColor = $pct !== null
                                ? ($pct >= 70 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600'))
                                : 'text-gray-400';
                        @endphp
                        <tr class="hover:bg-gray-50 transition" id="row-{{ $submission->id }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-700 font-black text-xs">
                                        {{ strtoupper(substr($submission->user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $submission->user->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $submission->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="/{{ $submission->file_path }}" target="_blank"
                                   class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 text-xs">
                                    <i class="ri-file-download-line"></i>
                                    {{ Str::limit($submission->original_filename, 25) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($grade)
                                    <span class="text-lg font-black {{ $scoreColor }}">{{ number_format($grade->total_score, 1) }}</span>
                                    <span class="text-gray-400 text-sm"> / {{ $grade->max_score }}</span>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $pct }}%</div>
                                @else
                                    <span class="text-gray-400 font-bold">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($grade && $grade->auto_graded)
                                    <span class="bg-blue-100 text-blue-700 text-xs font-black px-2 py-1 rounded-full">
                                        🤖 Auto-Graded
                                    </span>
                                @elseif($grade)
                                    <span class="bg-purple-100 text-purple-700 text-xs font-black px-2 py-1 rounded-full">
                                        ✋ Manual
                                    </span>
                                @else
                                    <span class="bg-amber-100 text-amber-700 text-xs font-black px-2 py-1 rounded-full">
                                        ⏳ Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-xs text-gray-500">
                                {{ $submission->submitted_at?->format('M d • g:i A') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Re-grade button --}}
                                    @if($rubric->auto_grade_enabled)
                                    <button onclick="regrade({{ $submission->id }})"
                                            id="regrade-btn-{{ $submission->id }}"
                                            class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-black px-3 py-1.5 rounded-lg border border-indigo-200 transition flex items-center gap-1">
                                        <i class="ri-refresh-line"></i> Re-Grade
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- Expandable criterion scores row --}}
                        @if($grade && $grade->criterionScores->count() > 0)
                        <tr class="bg-indigo-50/40">
                            <td colspan="6" class="px-6 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($grade->criterionScores as $score)
                                        @php
                                            $sp = $score->max_points > 0 ? ($score->points_earned / $score->max_points) * 100 : 0;
                                        @endphp
                                        <div class="bg-white border border-gray-200 rounded-lg px-3 py-2 text-xs max-w-xs">
                                            <p class="font-black text-gray-700 truncate">{{ $score->criterion->criterion_name }}</p>
                                            <p class="font-bold {{ $sp >= 70 ? 'text-green-600' : ($sp >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                {{ number_format($score->points_earned, 1) }}/{{ $score->max_points }}
                                            </p>
                                            @if($score->feedback)
                                                <p class="text-gray-500 mt-1 leading-tight">{{ Str::limit($score->feedback, 80) }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif

                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @else
    {{-- No rubric yet --}}
    <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
        <i class="ri-file-list-3-line text-6xl text-gray-200 mb-4"></i>
        <h3 class="text-xl font-black text-gray-900 mb-2">No Rubric Yet</h3>
        <p class="text-gray-500 mb-6">Create a rubric to enable automatic grading with Gemini AI.</p>
        <a href="{{ route('professor.tasks.rubric.create', $task->id) }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-6 py-3 rounded-xl transition inline-flex items-center gap-2">
            <i class="ri-add-line"></i> Create Rubric
        </a>
    </div>
    @endif

</div>
</div>

<script>
async function regrade(submissionId) {
    const btn = document.getElementById(`regrade-btn-${submissionId}`);
    if (!btn) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Grading...';

    try {
        const res = await fetch(`/professor/submissions/${submissionId}/regrade`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            }
        });

        const data = await res.json();

        if (data.success) {
            btn.innerHTML = '<i class="ri-checkbox-circle-fill"></i> Done!';
            btn.classList.replace('text-indigo-600', 'text-green-600');
            btn.classList.replace('bg-indigo-50', 'bg-green-50');
            btn.classList.replace('border-indigo-200', 'border-green-200');
            // Reload to show updated scores
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(data.message);
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-refresh-line"></i> Re-Grade';
        alert('Re-grading failed: ' + (err.message || 'Unknown error'));
    }
}
</script>
</x-app-layout>