<x-app-layout>
<div id="lockdown-ui" class="hidden fixed inset-0 z-[9999] bg-white w-full h-screen">
    <div class="flex w-full h-full">
        
        <div class="w-1/2 h-full bg-black relative">
            <video id="professor-screen" autoplay playsinline muted class="w-full h-full object-contain" style="background: black; min-height: 400px;"></video>
        </div>

        <!-- UPDATED: Real-time task workspace with file submission -->
        <div class="w-1/2 h-full p-8 overflow-y-auto bg-gray-50 border-l border-gray-200"
             x-data="{ 
                 tasks: [],
                 pollInterval: null,
                 uploadingTaskId: null,
                 
                 init() {
                     this.fetchTasks();
                     this.pollInterval = setInterval(() => {
                         this.fetchTasks();
                     }, 3000);
                 },
                 
                 fetchTasks() {
                     fetch('/student/classroom/{{ $class->id }}/live-tasks')
                         .then(res => {
                             if (!res.ok) throw new Error('Server error');
                             return res.json();
                         })
                         .then(data => { 
                             // Only show unsubmitted tasks
                             this.tasks = data.filter(task => !task.current_user_submission);
                         })
                         .catch(err => console.warn('Fetch error:', err));
                 },
                 
                 async submitTask(taskId, event) {
                     const form = event.target;
                     const fileInput = form.querySelector('input[type=file]');
                     
                     if (!fileInput.files.length) {
                         alert('Please select a file to submit!');
                         return;
                     }
                     
                     this.uploadingTaskId = taskId;
                     
                     const formData = new FormData(form);
                     
                     try {
                         const response = await fetch(`/student/tasks/${taskId}/submit`, {
                             method: 'POST',
                             headers: {
                                 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                             },
                             body: formData
                         });
                         
                         if (response.ok) {
                             // Remove task from UI immediately
                             this.tasks = this.tasks.filter(t => t.id !== taskId);
                             alert('✅ Task submitted! It will appear on your dashboard once graded.');
                         } else {
                             alert('❌ Submission failed. Please try again.');
                         }
                     } catch (error) {
                         console.error('Submission error:', error);
                         alert('❌ Network error. Please check your connection.');
                     } finally {
                         this.uploadingTaskId = null;
                     }
                 },
                 
                 destroy() {
                     if (this.pollInterval) {
                         clearInterval(this.pollInterval);
                     }
                 }
             }">
             
            <h2 class="text-2xl font-black text-gray-800 mb-4">Your Workspace</h2>
            <p class="text-gray-500 mb-6">Complete the active tasks assigned by your professor below.</p>

            <div class="space-y-4">
                <template x-for="task in tasks" :key="task.id">
                    <div class="p-5 bg-white rounded-xl shadow-sm border border-gray-200 transition-all animate-fade-in">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-900" x-text="task.title"></h3>
                                <p class="text-gray-600 mt-2 text-sm" x-text="task.description"></p>
                            </div>
                            <span class="text-xs font-black text-purple-600 bg-purple-50 px-3 py-1 rounded-full ml-4" 
                                  x-text="task.points + ' PTS'"></span>
                        </div>
                        
                        <!-- File Upload Form -->
                        <form @submit.prevent="submitTask(task.id, $event)" 
                              class="mt-4 space-y-3"
                              enctype="multipart/form-data">
                            
                            <div class="flex items-center gap-3 mb-4" x-data="{ fileName: '' }">
                            <label class="flex-1 cursor-pointer group">
                                <div class="flex items-center justify-center w-full px-4 py-4 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl group-hover:bg-gray-100 group-hover:border-green-500 transition-all">
                                    <div class="flex items-center gap-3 w-full">
                                        <div class="p-2 bg-white rounded-lg shadow-sm border border-gray-200 group-hover:border-green-200 group-hover:text-green-600 transition-all">
                                            <i class="ri-folder-upload-line text-lg"></i>
                                        </div>
                                        
                                        <div class="flex flex-col text-left flex-1 overflow-hidden">
                                            <span class="text-sm font-bold text-gray-700 truncate" 
                                                x-text="fileName ? fileName : 'Click to browse files'"></span>
                                            
                                            <span class="text-[10px] font-medium text-gray-400 uppercase tracking-widest" 
                                                x-show="!fileName">Select your work</span>
                                                
                                            <span class="text-[10px] font-bold text-green-600 uppercase tracking-widest" 
                                                x-show="fileName">Ready to submit</span>
                                        </div>
                                    </div>

                                    <input type="file" 
                                        name="submission" 
                                        required 
                                        class="hidden" 
                                        @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                </div>
                            </label>
                        </div>

                            <button type="submit" 
                                    :disabled="uploadingTaskId === task.id"
                                    :class="uploadingTaskId === task.id ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                                    class="w-full px-4 py-3 text-white text-sm font-bold rounded-lg transition flex items-center justify-center gap-2">
                                <template x-if="uploadingTaskId === task.id">
                                    <div class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Uploading...</span>
                                    </div>
                                </template>
                                <template x-if="uploadingTaskId !== task.id">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-upload-cloud-line"></i>
                                        <span>Submit Task</span>
                                    </div>
                                </template>
                            </button>
                        </form>
                    </div>
                </template>
                
                <div x-show="tasks.length === 0" class="text-center py-10 border-2 border-dashed border-gray-300 rounded-xl">
                    <div class="mb-3">
                        <i class="ri-checkbox-circle-line text-green-400 text-5xl"></i>
                    </div>
                    <p class="text-gray-700 font-bold text-lg">All caught up!</p>
                    <p class="text-gray-500 text-sm mt-1">No pending tasks. Check your dashboard for graded work.</p>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="flex h-screen bg-gray-50" x-data="{ activeTask: null }">

        <main class="flex-1 p-8 overflow-y-auto" 
      x-data="{ 
          tab: 'activities', 
          activeTask: null,
          tasks: [],
          fetchTasks() {
              fetch(`/student/classroom/{{ $class->id }}/live-tasks`)
                  .then(res => res.json())
                  .then(data => { this.tasks = data; })
                  .catch(err => console.error('Failed to fetch tasks:', err));
          },
          // NEW: Background Form Submission
          submitTask(event) {
              let formData = new FormData(event.target);
              
              fetch(`/student/tasks/${this.activeTask.id}/submit`, {
                  method: 'POST',
                  body: formData,
                  headers: {
                      'X-CSRF-TOKEN': '{{ csrf_token() }}'
                  }
              })
              .then(res => {
                  if(res.ok) {
                      this.activeTask = null; // Closes the modal
                      this.fetchTasks();      // Refreshes the task list quietly
                  } else {
                      alert('Something went wrong submitting the task.');
                  }
              });
          }
      }"
      x-init="fetchTasks(); setInterval(() => fetchTasks(), 3000)">
            <div class="max-w-5xl mx-auto space-y-6">
            
                <div class="bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6">
                    <h1 class="text-3xl font-black text-gray-900 mb-3 tracking-tight">
                        {{ $class->subject_name }} <span class="text-gray-400 font-light mx-2">|</span> <span class="text-[#383838]">{{ $class->program }}-{{ $class->year_level }}{{ $class->section }}</span>
                    </h1>
                    
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                            <i class="ri-calendar-line mr-2"></i> {{ $class->schedule_day }}
                        </span>
                        <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase tracking-widest border border-gray-200">
                            <i class="ri-time-line mr-2"></i> {{ $class->schedule_time }}
                        </span>
                    </div>
                </div>

                <div id="classroom-root" 
                     x-data="{ 
                        labMode: {{ $class->is_active ? 'true' : 'false' }}, 
                        sessionActive: {{ $class->is_active ? 'true' : 'false' }},
                        isPresent: {{ $isPresent ? 'true' : 'false' }},
                        showBroadcast: {{ $class->is_broadcasting ? 'true' : 'false' }},
                        loading: false
                     }">

                    <div id="monitoring-area" class="mt-6">
                        <div x-show="!sessionActive" x-transition>
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 flex items-center gap-4">
                                <div class="bg-amber-100 p-3 rounded-full text-amber-600 animate-pulse">
                                    <i class="ri-error-warning-line text-2xl"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-amber-900">Class Not Started</p>
                                    <p class="text-sm text-amber-700">The instructor has not started the session. Please wait...</p>
                                </div>
                            </div>
                        </div>

                        <div x-show="sessionActive" x-transition>
                            <div class="flex justify-center bg-white p-10 rounded-2xl border border-gray-200 shadow-sm">
                                
                                <template x-if="!isPresent">
                                    <button @click="markAttendance()" 
                                        :disabled="loading"
                                        class="bg-green-600 text-white px-10 py-4 rounded-xl shadow-lg hover:bg-green-700 font-black flex items-center gap-3 transition-all transform hover:scale-105">
                                        <i x-show="!loading" class="ri-user-check-line text-xl"></i>
                                        <i x-show="loading" class="ri-loader-4-line animate-spin text-xl"></i>
                                        <span x-text="loading ? 'Marking Presence...' : 'Join Classroom & Mark Present'"></span>
                                    </button>
                                </template>

                                <template x-if="isPresent">
                                    <button onclick="startFullMonitoring()" 
                                        class="bg-black text-white px-10 py-4 rounded-xl shadow-xl hover:bg-gray-800 font-black flex items-center gap-3 animate-pulse-slow">
                                        <i class="ri-screenshot-2-line"></i> Start Screen Sharing
                                    </button>
                                </template>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex border-b border-gray-200">
                    <template x-for="t in ['activities', 'quizzes', 'materials', 'classmates']">
                        <button @click="tab = t" 
                            :class="tab === t ? 'border-[#383838] text-[#383838]' : 'border-transparent text-gray-400 hover:text-gray-600'" 
                            class="px-6 py-4 border-b-2 font-bold text-xs uppercase tracking-widest transition" 
                            x-text="t">
                        </button>
                    </template>
                </div>

                <div class="mt-6">
                    <div x-show="tab === 'activities'" x-transition>
                        <div class="grid gap-4">
                            <template x-for="task in tasks" :key="task.id">
                        <button @click="activeTask = task" 
                                class="w-full group text-left p-6 bg-white border border-gray-200 rounded-xl shadow-sm hover:border-[#3B3B3B] transition-all relative mb-4">
                            
                            <div class="flex justify-between items-start mb-3">
                                <div class="space-y-1">
                                    <h4 class="font-bold text-gray-900 text-lg group-hover:text-[#3B3B3B] transition-all" x-text="task.title"></h4>
                                    
                                    <div class="flex items-center gap-3">
                                        <template x-if="task.current_user_submission">
                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-bold bg-green-50 text-green-700 uppercase border border-green-200">
                                                SUBMITTED
                                            </span>
                                        </template>
                                        <template x-if="!task.current_user_submission">
                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 uppercase border border-amber-200">
                                                PENDING
                                            </span>
                                        </template>
                                        
                                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-lighter">
                                            ID: #TASK-<span x-text="task.id"></span>
                                        </span>
                                    </div>
                                </div>

                                <span class="text-xs font-black text-[#3B3B3B] bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200 whitespace-nowrap">
                                    <span x-text="task.points"></span> PTS
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 line-clamp-2 mb-4" x-text="task.description"></p>
                            
                            <div class="flex items-center text-xs font-bold text-[#3B3B3B] group-hover:underline">
                                View Task Details <i class="ri-arrow-right-line ml-1"></i>
                            </div>
                        </button>
                    </template>

                    <template x-if="tasks.length === 0">
                        <div class="text-center py-16 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                            <i class="ri-stack-line text-gray-300 text-4xl mb-3 block"></i>
                            <p class="text-gray-500 font-medium italic">No activities assigned yet.</p>
                        </div>
                    </template>
                        </div>
                    </div>

                    <div x-show="tab === 'quizzes'" x-transition class="space-y-4">
                        @forelse($quizzes as $quiz)
                            @php
                                $attempt = $quiz->attempts()->where('user_id', auth()->id())->first();
                                $isPastDeadline = $quiz->deadline && now()->gt($quiz->deadline);
                                $hasAttempted = $attempt !== null;
                            @endphp

                            <div class="group bg-white border border-gray-200 rounded-2xl p-5 hover:border-[#383838] transition-all shadow-sm">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    
                                    <div class="flex items-start gap-4">
                                        <div class="bg-gray-100 p-3 rounded-xl text-[#383838] group-hover:bg-[#383838] group-hover:text-white transition-colors">
                                            <i class="ri-timer-flash-line text-2xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900 text-lg group-hover:text-[#383838] transition">
                                                {{ $quiz->title }}
                                            </h4>
                                            <div class="flex flex-wrap items-center gap-3 mt-1">
                                                <span class="flex items-center text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                                    <i class="ri-time-line mr-1 text-xs"></i> {{ $quiz->time_limit }} Mins
                                                </span>
                                                @if($quiz->deadline)
                                                    <span class="text-gray-300">•</span>
                                                    <span class="flex items-center text-[10px] font-bold {{ $isPastDeadline ? 'text-red-500' : 'text-gray-500' }} uppercase tracking-widest">
                                                        <i class="ri-calendar-event-line mr-1 text-xs"></i> 
                                                        Due: {{ \Carbon\Carbon::parse($quiz->deadline)->format('M d, h:i A') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        @if($hasAttempted)
                                            <div class="text-right px-4">
                                                <span class="block text-[10px] font-black text-[#383838] uppercase tracking-tighter opacity-60">Result</span>
                                                <span class="text-xl font-black text-green-600">{{ $attempt->score }} / {{ $quiz->total_points }}</span>
                                            </div>
                                            <button disabled class="bg-gray-100 text-gray-400 px-6 py-2.5 rounded-xl text-xs font-bold cursor-not-allowed border border-gray-200">
                                                Completed
                                            </button>
                                        @elseif($isPastDeadline)
                                            <div class="text-right px-4">
                                                <span class="block text-[10px] font-black text-red-600 uppercase tracking-tighter">Closed</span>
                                                <span class="text-xs font-bold text-gray-400">Past Deadline</span>
                                            </div>
                                            <button disabled class="bg-gray-100 text-gray-400 px-6 py-2.5 rounded-xl text-xs font-bold cursor-not-allowed">
                                                Unavailable
                                            </button>
                                        @else
                                            <a href="{{ route('student.quizzes.attempt', $quiz->id) }}" 
                                               class="bg-[#383838] text-white px-8 py-3 rounded-xl text-xs font-bold hover:bg-black transition-all shadow-lg shadow-gray-200 flex items-center gap-2">
                                                Take Quiz <i class="ri-edit-box-line"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-16 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                                <i class="ri-survey-line text-gray-300 text-4xl mb-3 block"></i>
                                <p class="text-gray-500 font-medium">No quizzes available for this subject.</p>
                            </div>
                        @endforelse
                    </div>

                    <div x-show="tab === 'materials'" x-transition>
                       <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-8">
                        @foreach($class->materials as $material) 
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-gray-100 rounded-xl">
                                    @if($material->type == 'pdf') 
                                        <i class="ri-file-pdf-fill text-red-500 text-xl"></i>
                                    @elseif($material->type == 'youtube') 
                                        <i class="ri-youtube-fill text-red-600 text-xl"></i>
                                    @else 
                                        <i class="ri-file-ppt-2-fill text-orange-500 text-xl"></i>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900">{{ $material->title }}</h4>
                                    <p class="text-xs text-gray-400 uppercase font-semibold">{{ $material->type }}</p>
                                </div>
                            </div>
                            
                           @php
                        $url = ($material->type === 'youtube') 
                                ? $material->content 
                                : url($material->content);
                        @endphp

                        <a href="{{ $url }}" 
                           target="_blank" 
                           class="inline-flex items-center text-[10px] font-black text-[#383838] bg-gray-100 px-3 py-2 rounded-lg hover:bg-black hover:text-white transition-all uppercase tracking-widest">
                            <i class="fas fa-eye me-2"></i> 
                            {{ $material->type === 'youtube' ? 'Watch Video' : 'View File' }}
                        </a>
                        </div>
                        @endforeach
                       </div>
                    </div>

                    <div x-show="tab === 'classmates'" x-transition>
                        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th class="px-6 py-4 font-black text-[#383838] uppercase tracking-widest text-[10px]">Student Name</th>
                                        <th class="px-6 py-4 font-black text-[#383838] uppercase tracking-widest text-[10px]">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($class->students as $student)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-6 py-4 font-medium text-gray-700 flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-[10px] font-bold text-gray-500">
                                                {{ strtoupper(substr($student->name, 0, 2)) }}
                                            </div>
                                            {{ $student->name }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                                                <span class="w-1 h-1 rounded-full bg-green-500 mr-1.5"></span> ENROLLED
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Modal -->
            <div 
                x-show="activeTask !== null"
                @open-task-modal.window="activeTask = $event.detail"
                class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-cloak
            >
                <div 
                    @click.away="activeTask = null" 
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-200"
                >
                    <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-start bg-gray-50/50">
                        <div>
                            <h3 class="text-xl font-black text-gray-900 leading-tight" x-text="activeTask?.title"></h3>
                            <div class="flex gap-3 mt-2">
                                <span class="text-[10px] font-bold text-[#383838] bg-gray-200 px-2 py-0.5 rounded uppercase tracking-wider">
                                    Points: <span x-text="activeTask?.points"></span>
                                </span>
                            </div>
                        </div>
                        <button @click="activeTask = null" class="text-gray-400 hover:text-[#383838] transition">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="px-8 py-6 space-y-6">
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl border border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="ri-calendar-todo-line text-gray-400"></i>
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Deadline</span>
                            </div>
                            <span :class="activeTask?.deadline && new Date() > new Date(activeTask.deadline) ? 'text-red-600' : 'text-[#383838]'" 
                                  class="text-xs font-black"
                                  x-text="activeTask?.deadline ? new Date(activeTask.deadline).toLocaleString() : 'No Deadline Set'">
                            </span>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Instructions</h4>
                            <p class="text-gray-700 text-sm leading-relaxed" x-text="activeTask?.description"></p>
                        </div>

                        <template x-if="activeTask?.current_user_submission">
                            <div class="p-4 rounded-xl border-2 border-gray-100 bg-gray-50/30">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Your Submission</h4>
                                    <span class="text-lg font-black text-[#383838]" 
                                          x-text="(activeTask.current_user_submission.grade || '0') + ' / ' + activeTask.points">
                                    </span>
                                </div>
                                
                                <div class="bg-white p-3 rounded-lg border border-gray-100 mb-4 shadow-sm">
                                    <p class="text-[9px] uppercase font-bold text-gray-400 mb-1">Feedback</p>
                                    <p class="text-sm text-gray-600 italic" 
                                       x-text="activeTask.current_user_submission.feedback || 'No feedback yet.'"></p>
                                </div>

                                <div class="flex justify-between items-center">
                                    <a :href="'/submissions/' + activeTask.current_user_submission.file_path.replace('submissions/', '')" 
                                       target="_blank" 
                                       class="flex items-center gap-2 text-sm font-bold text-[#383838] hover:underline transition">
                                        <i class="ri-file-list-3-line"></i>
                                        <span x-text="activeTask.current_user_submission.original_filename" class="truncate max-w-[150px]"></span>
                                    </a>

                                    <template x-if="activeTask && (!activeTask.deadline || new Date() < new Date(activeTask.deadline))">
                                        <form :action="`/student/tasks/${activeTask.id}/delete`" method="POST" 
                                              onsubmit="return confirm('Delete this submission?')">
                                            @csrf
                                            <button type="submit" class="text-red-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50 transition">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <template x-if="activeTask && (!activeTask.deadline || new Date() < new Date(activeTask.deadline))">
                            <form 
                                @submit.prevent="uploading = true; submitTask($event)"
                                enctype="multipart/form-data"
                                x-data="{ uploading: false, fileName: '' }"
                            >
                                @csrf
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                                        Upload Work
                                    </label>
                                    
                                    <label class="block cursor-pointer group">
                                        <div class="flex items-center justify-center w-full px-4 py-4 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl group-hover:bg-gray-100 group-hover:border-green-500 transition-all">
                                            <div class="flex items-center gap-3 w-full">
                                                <div class="p-2 bg-white rounded-lg shadow-sm border border-gray-200 group-hover:border-green-200 group-hover:text-green-600 transition-all">
                                                    <i class="ri-folder-upload-line text-lg"></i>
                                                </div>
                                                <div class="flex flex-col text-left flex-1 overflow-hidden">
                                                    <span class="text-sm font-bold text-gray-700 truncate" x-text="fileName ? fileName : 'Click to browse files'"></span>
                                                    <span class="text-[10px] font-medium text-gray-400 uppercase tracking-widest" x-show="!fileName">Select your work</span>
                                                    <span class="text-[10px] font-bold text-green-600 uppercase tracking-widest" x-show="fileName">Ready to submit</span>
                                                </div>
                                            </div>
                                            <input type="file" name="submission" required class="hidden" @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                        </div>
                                    </label>

                                    <div class="flex items-center justify-end gap-3 pt-2">
                                        <button 
                                            type="submit" 
                                            class="w-full py-3 bg-[#383838] text-white text-sm font-bold rounded-xl hover:bg-black shadow-lg shadow-gray-200 transition flex items-center justify-center gap-2"
                                            :disabled="uploading"
                                        >
                                            <span x-show="!uploading" x-text="activeTask?.current_user_submission ? 'Update Submission' : 'Submit Now'"></span>
                                            <span x-show="uploading" class="flex items-center gap-2">
                                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                Uploading...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Browser Tabs -->
            <div x-data="browserTabs()" class="bg-gray-100 rounded-3xl overflow-hidden border border-gray-200 shadow-xl">
                <div class="bg-white p-3 flex items-center gap-2 border-b border-gray-200">
                    <template x-for="(tab, index) in tabs" :key="index">
                        <div @click="activeTab = index" 
                             :class="activeTab === index ? 'bg-gray-100 border-gray-300' : 'bg-white border-transparent'"
                             class="px-4 py-2 rounded-xl border text-sm font-bold flex items-center gap-3 cursor-pointer transition-all">
                            <i class="ri-global-line"></i>
                            <span x-text="tab.title" class="truncate max-w-[100px]"></span>
                            <i @click.stop="removeTab(index)" class="ri-close-line hover:text-red-500" x-show="tabs.length > 1"></i>
                        </div>
                    </template>
                    <button @click="addTab()" class="p-2 hover:bg-gray-100 rounded-lg text-blue-600">
                        <i class="ri-add-circle-fill text-xl"></i>
                    </button>
                </div>

                <div class="bg-white px-4 py-2 flex items-center gap-4 border-b border-gray-200">
                    <div class="flex gap-2 text-gray-600">
                        <button @click="refresh()" class="p-2 hover:bg-gray-100 rounded-lg">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                    
                    <div class="flex-1 bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 text-xs text-gray-400 flex items-center gap-2 italic">
                        <i class="ri-lock-2-line"></i>
                        <span>Secure Research Mode: Use the Google search bar below to find resources. Direct URL entry is disabled.</span>
                    </div>
                    
                    <div class="text-[10px] font-mono text-gray-300">
                        SESSION: {{ $class->id }}
                    </div>
                </div>

                <div class="relative h-[700px] bg-white">
                    <template x-for="(tab, index) in tabs" :key="index">
                        <iframe :src="tab.url" 
                                x-show="activeTab === index"
                                :id="'browser-frame-' + index"
                                class="absolute inset-0 w-full h-full border-none"></iframe>
                    </template>
                </div>
            </div>
        </main>
    </div>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
        .animate-bounce-short { animation: bounce 1s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        
        input[type="file"]::file-selector-button {
            background-color: #383838 !important;
            border-radius: 8px !important;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #000 !important;
        }
    </style>

    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
   
    <script>
    let heartbeatInterval;
    let studentPeer;
    let localStream;
    let isAttemptingToShare = false;
    const classId = {{ $class->id }};
    const csrfToken = '{{ csrf_token() }}';
    const allowedDomains = @json($class->whitelisted_urls ? explode(',', $class->whitelisted_urls) : ['google.com']);

    document.addEventListener('DOMContentLoaded', () => {
        studentPeer = new Peer('STUDENT_{{ auth()->id() }}');
        
        startHeartbeat();

        studentPeer.on('open', (id) => {
            console.log('✅ Peer ready: ' + id);
            const wasSharing = localStorage.getItem('is_sharing');
            if (wasSharing === 'true') {
                console.log("Session was active. Please click 'Start Screen Sharing' to resume.");
            }
        });

        studentPeer.on('call', (call) => {
            console.log("📞 Incoming call detected from Professor!");
            
            if (typeof localStream !== 'undefined' && localStream && localStream.active) {
                call.answer(localStream); 
            } else {
                call.answer(); 
            }

            call.on('stream', (incomingStream) => {
                console.log("🎥 Professor's screen received! Locking down...");
                
                const lockdownUi = document.getElementById('lockdown-ui');
                if (lockdownUi) {
                    lockdownUi.classList.remove('hidden');
                    lockdownUi.classList.add('flex'); 
                }
                
                const profVideo = document.getElementById('professor-screen');
                if (profVideo) {
                    profVideo.srcObject = incomingStream;
                }
                
                if (window.electronAPI) {
                    window.electronAPI.lockScreen();
                }
            });

            // 🟢 NEW: Listen for the professor ending the screen share
            call.on('close', () => {
                console.log("Stream closed by Professor.");
                // Redirect to dashboard
                window.location.href = "{{ route('student.dashboard') }}";
            });
        });
    });

    async function startFullMonitoring() {
        if (isAttemptingToShare) return; 
        isAttemptingToShare = true;

        try {
            let streamConstraints;

            if (window.electronAPI) {
                const sourceId = await window.electronAPI.getScreenId();
                streamConstraints = {
                    audio: false,
                    video: {
                        mandatory: {
                            chromeMediaSource: 'desktop',
                            chromeMediaSourceId: sourceId,
                            minWidth: 1280, maxWidth: 1280,
                            minHeight: 720, maxHeight: 720
                        }
                    }
                };
                localStream = await navigator.mediaDevices.getUserMedia(streamConstraints);
            } else {
                localStream = await navigator.mediaDevices.getDisplayMedia({ 
                    video: { displaySurface: "monitor", width: { max: 1280 } },
                    audio: false
                });
            }

            const videoTrack = localStream.getVideoTracks()[0];
            
            if (!window.electronAPI) {
                const settings = videoTrack.getSettings();
                if (settings.displaySurface && settings.displaySurface !== 'monitor') {
                    videoTrack.stop();
                    alert("❌ You must select 'ENTIRE SCREEN'.");
                    isAttemptingToShare = false;
                    return;
                }
            }

            localStorage.setItem('is_sharing', 'true');

            videoTrack.onended = () => {
                localStorage.setItem('is_sharing', 'false');
                fetch('{{ route("student.stop-presenting", $class->id) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
                }).then(() => location.reload());
            };

            const adminPeerId = 'PROF_{{ $class->faculty_id }}';
            
            const callProfessor = () => {
                console.log("📞 Attempting to connect to Professor:", adminPeerId);
                const call = studentPeer.call(adminPeerId, localStream);

                if (!call) {
                    console.error("❌ PeerJS call failed to initialize.");
                    return;
                }

                studentPeer.on('error', (err) => {
                    if (err.type === 'peer-unavailable') {
                        console.warn("⚠️ Professor page is still loading... retrying in 3s");
                        setTimeout(callProfessor, 3000);
                    }
                });

                call.on('stream', (remoteStream) => {
                    console.log("✅ Connection established with Professor!");
                });
            };

            callProfessor();

        } catch (err) {
            console.error("❌ Capture failed:", err);
            localStorage.setItem('is_sharing', 'false');
            isAttemptingToShare = false;
        }
    }

    function startHeartbeat() {
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        heartbeatInterval = setInterval(() => {
            fetch(`/student/heartbeat/${classId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            });
        }, 30000);
    }

    // 🟢 NEW: Track the previous broadcasting state
    let wasBroadcasting = {{ $class->is_broadcasting ? 'true' : 'false' }};

    setInterval(() => {
        fetch("{{ route('student.check-session-status', $class->id) }}")
            .then(res => res.json())
            .then(data => {
                const root = document.getElementById('classroom-root');
                if (!root || !window.Alpine) return;
                
                const alpine = Alpine.$data(root);
                
                alpine.sessionActive = data.is_active;

                if (data.is_active && !alpine.labMode) {
                    alpine.labMode = true; 
                    console.log("🔒 Session is now active. Locking UI...");
                    
                    if (alpine.isPresent && !localStorage.getItem('is_sharing')) {
                        startFullMonitoring();
                    }
                }

                if (!data.is_active && alpine.labMode) {
                    alpine.labMode = false;
                    alert("The instructor has ended the session.");
                    window.location.href = "{{ route('student.dashboard') }}"; 
                }
                
                // 🟢 NEW: Detect if broadcasting just stopped
                if (wasBroadcasting && !data.is_broadcasting) {
                    console.log("Professor ended screen share. Refreshing to show workspace.");
                    window.location.reload();
                }
                wasBroadcasting = data.is_broadcasting;

                alpine.showBroadcast = data.is_broadcasting;
            })
            .catch(err => console.error("Poll error:", err));
    }, 5000);

    setInterval(() => {
        fetch("{{ route('student.check-session-status', $class->id) }}")
            .then(res => res.json())
            .then(data => {
                const root = document.getElementById('classroom-root');
                if (!root || !window.Alpine) return;
                
                const alpine = Alpine.$data(root);
                
                alpine.sessionActive = data.is_active;

                if (data.is_active && !alpine.labMode) {
                    alpine.labMode = true; 
                    console.log("🔒 Session is now active. Locking UI...");
                    
                    if (alpine.isPresent && !localStorage.getItem('is_sharing')) {
                        startFullMonitoring();
                    }
                }

                if (!data.is_active && alpine.labMode) {
                    alpine.labMode = false;
                    alert("The instructor has ended the session.");
                    location.reload(); 
                }
                
                alpine.showBroadcast = data.is_broadcasting;
            })
            .catch(err => console.error("Poll error:", err));
    }, 5000);

    function navigateBrowser() {
        const input = document.getElementById('browserUrl').value;
        const targetUrl = input.includes('.') ? (input.startsWith('http') ? input : 'https://' + input) : 'https://www.google.com/search?q=' + encodeURIComponent(input) + '&igu=1';
        if (allowedDomains.some(d => targetUrl.includes(d.trim().toLowerCase()))) {
            document.getElementById('mainFrame').src = targetUrl;
        } else {
            alert("🚫 Access Denied.");
        }
    }
    </script>

    <script>
    if (window.electronAPI) {
        window.electronAPI.setCurrentSession("{{ $class->id }}");
    }

    function browserTabs() {
        return {
            activeTab: 0,
            tabs: [
                { title: 'Google Search', url: 'https://www.google.com/search?igu=1' }
            ],
            
            init() {
                this.logActivity('environment_start', 'Browser UI initialized in locked mode');
                
                setInterval(() => {
                    const frame = document.getElementById('browser-frame-' + this.activeTab);
                    if (frame && frame.contentWindow) {
                        try {
                            const currentUrl = frame.contentWindow.location.href;
                            if (currentUrl !== this.tabs[this.activeTab].url && currentUrl !== 'about:blank') {
                                this.tabs[this.activeTab].url = currentUrl;
                            }
                        } catch (e) { /* CORS expected */ }
                    }
                }, 2000);

                window.addEventListener('beforeunload', () => {
                    if (window.electronAPI) {
                        window.electronAPI.triggerFinalLog("{{ $class->id }}");
                    }
                });
            },

            addTab() {
                this.tabs.push({ 
                    title: 'New Tab', 
                    url: 'https://www.google.com/search?igu=1' 
                });
                this.activeTab = this.tabs.length - 1;
                this.logActivity('new_tab', 'Opened new research tab');
            },

            removeTab(index) {
                this.tabs.splice(index, 1);
                if (this.activeTab >= this.tabs.length) this.activeTab = this.tabs.length - 1;
            },

            refresh() {
                const frame = document.getElementById('browser-frame-' + this.activeTab);
                if (frame) {
                    frame.src = frame.src;
                    this.logActivity('refresh', 'Manual refresh triggered');
                }
            },

            logActivity(type, detail) {
                fetch('http://127.0.0.1:8000/student/log-behavior', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify({ 
                        type: type, 
                        detail: detail,
                        lab_session_id: "{{ $class->id }}" 
                    })
                }).catch(err => console.error('Log Error:', err));
            }
        }
    }
    </script>

    <script>
    function markAttendance() {
        const root = Alpine.$data(document.getElementById('classroom-root'));
        root.loading = true;

        fetch("{{ route('student.mark-present', $class->id) }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                root.isPresent = true;
            }
        })
        .catch(err => console.error(err))
        .finally(() => {
            root.loading = false;
        });
    }
    </script>
</x-app-layout>