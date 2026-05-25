<x-app-layout>
    <div class="min-h-screen bg-gray-100 py-8">
        <div class="max-w-4xl mx-auto px-4">
            
            <!-- Back Button -->
            <a href="{{ route('student.dashboard') }}" 
               class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6 font-bold">
                <i class="ri-arrow-left-line"></i>
                Back to Dashboard
            </a>

            <!-- Task Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-6 mb-6 text-white">
                <h1 class="text-3xl font-black mb-2">{{ $task->title }}</h1>
                <p class="text-blue-100">{{ $task->labSession->subject_name }}</p>
                <p class="text-sm text-blue-200 mt-1">Professor: {{ $task->labSession->faculty->name }}</p>
            </div>

            @if($submissionGrade)
                <!-- Grade Summary Card -->
                <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-black text-gray-900 mb-2">📊 Your Grade</h2>
                            <p class="text-sm text-gray-600">
                                Submitted: {{ $submission->submitted_at->format('M d, Y • g:i A') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-5xl font-black {{ $submissionGrade->total_score >= ($submissionGrade->max_score * 0.7) ? 'text-green-600' : 'text-yellow-600' }}">
                                {{ number_format($submissionGrade->total_score, 1) }}
                            </div>
                            <div class="text-2xl text-gray-500">/ {{ $submissionGrade->max_score }}</div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ number_format(($submissionGrade->total_score / $submissionGrade->max_score) * 100, 1) }}%
                            </div>
                        </div>
                    </div>

                    <!-- Auto-Graded Indicator -->
                    @if($submissionGrade->auto_graded)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <i class="ri-robot-line text-2xl text-blue-600"></i>
                            <div>
                                <p class="font-bold text-blue-900">Auto-Graded Submission</p>
                                <p class="text-sm text-blue-700">This submission was automatically graded using the rubric criteria</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Progress Bar -->
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block text-gray-600">
                                    Score Progress
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-4 mb-4 text-xs flex rounded-full bg-gray-200">
                            <div style="width:{{ ($submissionGrade->total_score / $submissionGrade->max_score) * 100 }}%" 
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                        {{ $submissionGrade->total_score >= ($submissionGrade->max_score * 0.7) ? 'bg-green-500' : 'bg-yellow-500' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Feedback by Criterion -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h3 class="text-xl font-black text-gray-900 mb-6">📋 Detailed Feedback</h3>
                    
                    <div class="space-y-4">
                        @foreach($submissionGrade->criterionScores as $index => $score)
                            @php
                                $percentage = $score->max_points > 0 ? ($score->points_earned / $score->max_points) * 100 : 0;
                                $isPerfect = $score->points_earned >= $score->max_points;
                                $isGood = $percentage >= 70;
                                $isFair = $percentage >= 50;
                            @endphp
                            
                            <div class="border-l-4 {{ $isPerfect ? 'border-green-500' : ($isGood ? 'border-blue-500' : ($isFair ? 'border-yellow-500' : 'border-red-500')) }} 
                                        bg-gray-50 rounded-r-lg p-5 hover:shadow-md transition">
                                
                                <!-- Criterion Header -->
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="bg-gray-200 text-gray-700 font-black text-sm rounded-full w-7 h-7 flex items-center justify-center">
                                                {{ $index + 1 }}
                                            </span>
                                            <h4 class="font-black text-gray-900 text-lg">{{ $score->criterion->criterion_name }}</h4>
                                        </div>
                                        
                                        @if($score->criterion->description)
                                        <p class="text-sm text-gray-600 ml-9">{{ $score->criterion->description }}</p>
                                        @endif
                                    </div>
                                    
                                    <div class="text-right ml-4">
                                        <div class="text-2xl font-black {{ $isPerfect ? 'text-green-600' : ($isGood ? 'text-blue-600' : ($isFair ? 'text-yellow-600' : 'text-red-600')) }}">
                                            {{ number_format($score->points_earned, 1) }}
                                        </div>
                                        <div class="text-sm text-gray-500">/ {{ $score->max_points }}</div>
                                    </div>
                                </div>

                                <!-- Checking Type Badge -->
                                <div class="flex items-center gap-2 mb-3 ml-9">
                                    @php
                                        $typeIcons = [
                                            'code' => '💻',
                                            'keyword' => '🔍',
                                            'text' => '📝',
                                            'file' => '📁',
                                            'ai' => '🤖',
                                            'manual' => '✋'
                                        ];
                                        $typeLabels = [
                                            'code' => 'Code Execution',
                                            'keyword' => 'Keyword Detection',
                                            'text' => 'Text Analysis',
                                            'file' => 'File Validation',
                                            'ai' => 'AI Evaluation',
                                            'manual' => 'Manual Grading'
                                        ];
                                    @endphp
                                    
                                    <span class="text-xs bg-gray-200 text-gray-700 px-3 py-1 rounded-full font-bold">
                                        {{ $typeIcons[$score->criterion->checking_type] ?? '📊' }}
                                        {{ $typeLabels[$score->criterion->checking_type] ?? 'Unknown' }}
                                    </span>
                                    
                                    @if($score->auto_checked)
                                        <span class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold">
                                            🤖 Auto-Checked
                                        </span>
                                    @else
                                        <span class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-bold">
                                            👨‍🏫 Manual Review
                                        </span>
                                    @endif
                                </div>

                                <!-- Mini Progress Bar -->
                                <div class="mb-3 ml-9">
                                    <div class="overflow-hidden h-2 text-xs flex rounded-full bg-gray-200">
                                        <div style="width:{{ $percentage }}%" 
                                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                                    {{ $isPerfect ? 'bg-green-500' : ($isGood ? 'bg-blue-500' : ($isFair ? 'bg-yellow-500' : 'bg-red-500')) }}">
                                        </div>
                                    </div>
                                </div>

                                <!-- Feedback Text -->
                                @if($score->feedback)
                                <div class="ml-9 bg-white border border-gray-200 rounded-lg p-4">
                                    <p class="text-sm font-bold text-gray-700 mb-2">📝 Feedback:</p>
                                    <div class="text-sm text-gray-800 whitespace-pre-line">{{ $score->feedback }}</div>
                                </div>
                                @endif

                                <!-- Score Indicator Icon -->
                                <div class="flex items-center gap-2 mt-3 ml-9">
                                    @if($isPerfect)
                                        <i class="ri-checkbox-circle-fill text-green-600 text-xl"></i>
                                        <span class="text-sm font-bold text-green-700">Perfect Score! 🎉</span>
                                    @elseif($isGood)
                                        <i class="ri-check-line text-blue-600 text-xl"></i>
                                        <span class="text-sm font-bold text-blue-700">Good Job!</span>
                                    @elseif($isFair)
                                        <i class="ri-information-line text-yellow-600 text-xl"></i>
                                        <span class="text-sm font-bold text-yellow-700">Could Improve</span>
                                    @else
                                        <i class="ri-close-circle-line text-red-600 text-xl"></i>
                                        <span class="text-sm font-bold text-red-700">Needs Attention</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Submission Info -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mt-6">
                    <h3 class="text-lg font-black text-gray-900 mb-4">📎 Submission Details</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 mb-1">File Name:</p>
                            <p class="font-bold text-gray-900">{{ $submission->original_filename }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 mb-1">Submitted:</p>
                            <p class="font-bold text-gray-900">{{ $submission->submitted_at->format('M d, Y • g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 mb-1">Duration:</p>
                            <p class="font-bold text-gray-900">{{ gmdate('H:i:s', $submission->duration_seconds) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 mb-1">Graded:</p>
                            <p class="font-bold text-gray-900">{{ $submissionGrade->created_at->format('M d, Y • g:i A') }}</p>
                        </div>
                    </div>
                </div>

            @else
                <!-- No Grade Yet -->
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <i class="ri-file-list-line text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-2xl font-black text-gray-900 mb-2">Not Yet Graded</h3>
                    <p class="text-gray-600">Your submission is being reviewed by your professor.</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>