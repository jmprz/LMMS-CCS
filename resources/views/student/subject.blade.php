<x-app-layout>
   <div id="lockdown-ui" class="hidden fixed inset-0 z-[9999] bg-white w-full h-screen"
        x-data="{ workspaceOpen: true }">
        <div class="flex w-full h-full">
            <div :class="workspaceOpen ? 'w-1/2' : 'w-full'"
                class="h-full bg-black relative transition-all duration-500 ease-in-out">
                <video id="professor-screen" autoplay playsinline muted class="w-full h-full object-contain"></video>

                <div class="absolute top-4 left-4 flex gap-3">
                    <button x-show="workspaceOpen"
                        class="px-6 py-2 bg-gray-800 text-white rounded-lg font-bold hover:bg-red-600 transition flex items-center gap-2 shadow-2xl"
                        @click="workspaceOpen = false">
                        <i class="ri-layout-right-line"></i> CLOSE WORKSPACE
                    </button>

                    <button x-show="!workspaceOpen" style="display: none;"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2 shadow-2xl animate-bounce-short"
                        @click="workspaceOpen = true">
                        <i class="ri-layout-right-fill"></i> OPEN WORKSPACE
                    </button>
                </div>
            </div>

            <div x-show="workspaceOpen" x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-500" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="w-1/2 h-full bg-gray-50 flex flex-col border-l border-gray-200" x-data="lockdownWorkspace()"
                @task-updated.window="fetchTasks()">

                <div class="flex border-b border-gray-300 bg-white">
                    <button @click="tab = 'activities'"
                        :class="tab === 'activities' ? 'border-b-2 border-black font-black text-black' : 'text-gray-400 font-bold'"
                        class="px-8 py-4 text-[10px] uppercase tracking-widest transition-all">
                        Activities
                    </button>
                    <button @click="tab = 'browser'"
                        :class="tab === 'browser' ? 'border-b-2 border-black font-black text-black' : 'text-gray-400 font-bold'"
                        class="px-8 py-4 text-[10px] uppercase tracking-widest transition-all">
                        Research Browser
                    </button>
                </div>

                <div x-show="tab === 'activities'" class="flex-1 overflow-y-auto p-8">
                    <h2 class="text-2xl font-black text-gray-800 mb-2 tracking-tight">Workspace</h2>
                    <p class="text-sm text-gray-500 uppercase font-bold tracking-widest mb-6 text-[10px]">Active Tasks
                    </p>

                    <div class="space-y-4">
                        <template x-for="task in tasks" :key="task.id">
                            <div class="p-6 bg-white rounded-2xl border border-gray-200 shadow-sm animate-fade-in">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-lg text-gray-900" x-text="task.title"></h3>
                                        <p class="text-gray-600 mt-2 text-sm" x-text="task.description"></p>
                                    </div>
                                    <span
                                        class="text-xs font-black text-purple-600 bg-purple-50 px-3 py-1 rounded-full ml-4"
                                        x-text="task.points + ' PTS'"></span>
                                </div>

                                <form @submit.prevent="submitTask(task.id, $event)" enctype="multipart/form-data">
                                    <div class="mb-4" x-data="{ fileName: '' }">
                                        <label class="cursor-pointer group block">
                                            <div
                                                class="flex items-center justify-center w-full px-4 py-4 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl group-hover:bg-gray-100 group-hover:border-green-500 transition-all">
                                                <div class="flex items-center gap-3 w-full">
                                                    <div
                                                        class="p-2 bg-white rounded-lg shadow-sm border border-gray-200">
                                                        <i class="ri-folder-upload-line text-lg"></i>
                                                    </div>
                                                    <div class="flex-1 overflow-hidden">
                                                        <span class="text-sm font-bold text-gray-700 block truncate"
                                                            x-text="fileName || 'Click to browse files'"></span>
                                                        <span class="text-[10px] text-gray-400 uppercase font-black"
                                                            x-text="fileName ? 'Ready to submit' : 'Select your work'"></span>
                                                    </div>
                                                </div>
                                                <input type="file" name="submission" required class="hidden"
                                                    @change="fileName = $event.target.files[0]?.name || ''">
                                            </div>
                                        </label>
                                    </div>
                                    <button type="submit" :disabled="uploadingTaskId === task.id"
                                        :class="uploadingTaskId === task.id ? 'bg-gray-400' : 'bg-green-600 hover:bg-green-700'"
                                        class="w-full py-3 text-white font-black rounded-xl transition uppercase text-[10px] tracking-widest shadow-lg shadow-green-100">
                                        <span x-show="uploadingTaskId === task.id">Uploading...</span>
                                        <span x-show="uploadingTaskId !== task.id">Submit Task</span>
                                    </button>
                                </form>
                            </div>
                        </template>

                        <div x-show="tasks.length === 0"
                            class="text-center py-20 border-2 border-dashed border-gray-200 rounded-3xl bg-white/50">
                            <i class="ri-checkbox-circle-line text-green-400 text-6xl"></i>
                            <p class="text-gray-700 font-black mt-4 text-xl tracking-tight">All tasks submitted!</p>
                            <p class="text-gray-400 text-xs mt-1 uppercase font-bold tracking-widest">Great job!</p>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'browser'" class="flex-1 p-8 flex flex-col" x-data="browserManager()">
                    <div class="flex gap-2 mb-4 bg-white p-2 rounded-2xl border border-gray-200 shadow-sm">
                        <input type="text" x-model="urlInput" @keyup.enter="navigateTo()"
                            placeholder="Search Google or enter educational URL..."
                            class="flex-1 border-none bg-gray-50 rounded-xl text-xs px-4 py-2 focus:ring-0">
                        <button @click="refresh()" class="p-2 text-gray-400 hover:text-black transition">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                    <iframe id="lockdown-frame" :src="browserUrl"
                        class="w-full flex-1 rounded-2xl border border-gray-200 bg-white shadow-inner"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div id="normal-view" class="flex flex-col min-h-screen bg-gray-50" x-data="{ isSharing: false, activeTab: 'activities' }">
        <main class="flex-1 p-8" @screen-shared.window="isSharing = true"
            @screen-stopped.window="isSharing = false">
            <div class="max-w-7xl mx-auto space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <div class="md:col-span-2 bg-white border border-gray-200 shadow-sm rounded-2xl px-8 py-6">
        <h1 class="text-3xl font-black text-gray-900 mb-3 tracking-tight">
            {{ $class->subject_name }} <span class="text-gray-400 font-light mx-2">|</span>
            <span class="text-[#383838] uppercase">{{ $class->program }}-{{ $class->year_level }}{{ $class->section }}</span>
        </h1>
        <div class="flex gap-2">
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase border border-gray-200">
                <i class="ri-calendar-line mr-2"></i> {{ $class->schedule_day }}
            </span>
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase border border-gray-200">
                <i class="ri-time-line mr-2"></i> {{ $class->schedule_time }}
            </span>
        </div>
    </div>

    <div class="md:col-span-1">
        @if(!$class->is_active)
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 h-full flex flex-col justify-center items-center text-center">
                <i class="ri-error-warning-line text-3xl text-amber-500 mb-2"></i>
                <p class="font-black text-amber-900">Session Offline</p>
            </div>
        @else
            <div x-show="!isSharing" class="">
               
            </div>

            <div x-show="isSharing" x-cloak class="bg-white border-2 border-dashed border-gray-200 rounded-2xl p-6 h-full flex flex-col justify-center items-center text-center animate-fade-in">
                <div class="ri-broadcast-line text-3xl text-green-500 animate-pulse mb-2"></div>
                <h2 class="font-black text-gray-900 tracking-tight">Monitoring Active</h2>
                <p class="text-[10px] text-gray-500 mt-1">The professor is viewing your screen.</p>
            </div>
        @endif
    </div>
</div>

                @if(!$class->is_active)
                    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-12 text-center shadow-inner">
                        <i class="ri-error-warning-line text-5xl text-amber-500 mb-4 block"></i>
                        <p class="font-black text-amber-900 text-xl">Session Offline</p>
                        <p class="text-amber-700 font-medium">The instructor has not initialized the laboratory session yet.
                        </p>
                    </div>
                @else
                                <div x-show="!isSharing"
     class="flex flex-col items-center justify-center bg-white p-12 rounded-[40px] border border-gray-200 shadow-sm text-center">
    
    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-6">
        <i class="ri-macbook-line text-4xl text-[#383838]"></i>
    </div>
    
    <h3 class="text-2xl font-black text-gray-900 mb-2 tracking-tight">Security Check Required</h3>
    
    <p class="text-gray-500 mb-8 max-w-md">
        To maintain lab integrity, share your 
        <span class="font-bold text-gray-800 underline underline-offset-4">Entire Screen</span> 
        to unlock the classroom dashboard.
    </p>
    
    <div class="flex flex-col items-center space-y-4">
        <button onclick="enterClassroom()"
            class="bg-[#383838] text-white px-10 py-4 rounded-2xl shadow-xl hover:bg-[#2c2c2c] font-black transition-all hover:scale-105 duration-150">
            Share Screen & Enter Classroom
        </button>

        <a href="{{ route('dashboard') }}" 
           class="inline-flex items-center text-xs font-black text-gray-400 hover:text-gray-800 uppercase tracking-widest transition duration-150 group">
            <i class="ri-arrow-left-line mr-2 text-sm transition-transform group-hover:-translate-x-1"></i>
            Back to Dashboard
        </a>
    </div>
</div>

                                <div x-show="isSharing" x-cloak class="animate-fade-in">
                                    <div class="flex border-b border-gray-200 mb-8">
                                        <template x-for="t in ['activities', 'quizzes', 'materials']">
                                            <button @click="activeTab = t"
                                                :class="activeTab === t ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 hover:text-gray-600 font-bold'"
                                                class="px-8 py-4 text-[10px] uppercase tracking-widest transition" x-text="t"></button>
                                        </template>
                                    </div>

                                   <div x-show="activeTab === 'activities'" x-data="classroomTasks()" class="space-y-6" @task-updated.window="fetchTasks()">
    
    <div class="flex items-center gap-2 bg-gray-50/50 p-1.5 rounded-[20px] border border-gray-100 w-fit">
        <button @click="filter = 'all'" 
            :class="filter === 'all' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
            class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all">
            All Tasks
        </button>
        <button @click="filter = 'submitted'" 
            :class="filter === 'submitted' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
            class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
            <i class="ri-checkbox-circle-line"></i> Submitted
        </button>
        <button @click="filter = 'missing'" 
            :class="filter === 'missing' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
            class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
            <i class="ri-error-warning-line"></i> Missing
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <template x-for="task in filteredTasks" :key="task.id">
    <div @click="openTaskModal(task)"
        class="bg-white p-5 rounded-[28px] border border-gray-100 flex flex-col justify-between group hover:border-[#383838] cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-gray-100/50 active:scale-[0.98] animate-fade-in">
        
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="mt-1 w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                    <i class="ri-file-text-line text-sm"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-black text-[#383838] text-base tracking-tight leading-tight group-hover:text-black transition-colors" x-text="task.title"></h4>
                    <div class="mt-1 flex items-center gap-1.5">
                        <i class="ri-calendar-todo-line text-gray-400 text-[10px]"></i>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide" 
                                x-text="formatDeadline(task.deadline)"></span>
                    </div>
                </div>
            </div>

            <div class="py-3 border-y border-gray-50">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Score:</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black text-[#383838]" 
                            x-text="task.current_user_submission?.grade ?? '--'"></span>
                    <span class="text-xs font-bold text-gray-300" x-text="'/ ' + task.points"></span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-3">
            <template x-if="task.current_user_submission">
                <span class="flex items-center gap-1 text-[9px] font-black text-black uppercase tracking-tighter">
                    <i class="ri-checkbox-circle-fill text-base text-[#383838]"></i> Submitted
                </span>
            </template>
            <template x-if="!task.current_user_submission">
                <span class="flex items-center gap-1 text-[9px] font-black text-gray-300 uppercase tracking-tighter">
                    <i class="ri-radio-button-line text-base"></i> Missing
                </span>
            </template>
            <i class="ri-arrow-right-line text-gray-300 group-hover:text-[#383838] group-hover:translate-x-1 transition-all"></i>
        </div>
    </div>
</template>
    </div>

    <div x-show="filteredTasks.length === 0" class="py-20 text-center">
        <i class="ri-inbox-line text-4xl text-gray-200"></i>
        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-4">No tasks found in this category</p>
    </div>
</div>

                                   <div x-show="activeTab === 'quizzes'" x-data="classroomQuizzes()" class="space-y-6">
    
    <div class="flex items-center gap-2 bg-gray-50/50 p-1.5 rounded-[20px] border border-gray-100 w-fit">
        <button @click="filter = 'all'" 
            :class="filter === 'all' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
            class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all">
            All Quizzes
        </button>
        <button @click="filter = 'completed'" 
            :class="filter === 'completed' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
            class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all">
          <i class="ri-checkbox-circle-line"></i>
            Completed
        </button>
        <button @click="filter = 'pending'" 
            :class="filter === 'pending' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
            class="px-5 py-2 rounded-[14px] text-[10px] font-black uppercase tracking-widest transition-all">
          <i class="ri-error-warning-line"></i>
            Pending
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
       <template x-for="quiz in filteredQuizzes" :key="quiz.id">
    <div @click="handleQuizClick(quiz)"
        class="bg-white p-5 rounded-[28px] border border-gray-100 flex flex-col justify-between group hover:border-[#383838] cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-gray-100/50 active:scale-[0.98] animate-fade-in min-h-[220px]">
        
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="mt-1 w-8 h-8 shrink-0 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                    <i class="ri-survey-line text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-black text-[#383838] text-base tracking-tight leading-tight group-hover:text-black transition-colors truncate" x-text="quiz.title"></h4>
                    <div class="mt-1 flex items-center gap-1.5">
                        <i class="ri-calendar-todo-line text-gray-400 text-[10px]"></i>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide" 
                              x-text="formatDeadline(quiz.expires_at)"></span>
                    </div>
                </div>
            </div>

            <div class="py-3 border-y border-gray-50">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Score:</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-black text-[#383838]" 
                          x-text="quiz.user_score !== undefined && quiz.user_score !== null ? quiz.user_score : '--'"></span>
                    <span class="text-xs font-bold text-gray-300" x-text="'/ ' + (quiz.total_points || quiz.questions_count)"></span>
                </div>
            </div>
        </div>

      <div class="flex items-center justify-between pt-3">
    <template x-if="quiz.has_attempt">
        <span class="flex items-center gap-1 text-[9px] font-black text-black uppercase tracking-tighter">
            <i class="ri-checkbox-circle-fill text-base text-[#383838]"></i> Completed
        </span>
    </template>

    <template x-if="!quiz.has_attempt">
        <button @click.stop="$dispatch('open-quiz', { id: quiz.id })" 
                class="bg-[#383838] text-white text-[10px] font-black px-4 py-1.5 rounded-lg hover:bg-black transition-all">
            TAKE QUIZ
        </button>
    </template>
    
    <i class="ri-arrow-right-line text-gray-300 group-hover:text-[#383838] group-hover:translate-x-1 transition-all"></i>
</div>
    </div>
</template>
    </div>

    <div x-show="filteredQuizzes.length === 0" class="py-20 text-center">
        <i class="ri-survey-line text-4xl text-gray-200"></i>
        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-4">No quizzes found</p>
    </div>
</div>
                                

  <div x-show="activeTab === 'materials'" x-data="classroomMaterials()" class="space-y-6 animate-fade-in">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <template x-for="material in materials" :key="material.id">
            <template x-if="material.type !== 'pptx'">
                <div @click="$dispatch('open-material', material)" 
                    class="bg-white p-5 rounded-[28px] border border-gray-100 flex flex-col justify-between group hover:border-[#383838] cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-gray-100/50 active:scale-[0.98] animate-fade-in min-h-[180px]">
                    
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-1 w-8 h-8 shrink-0 rounded-lg bg-gray-50 flex items-center justify-center border border-gray-100 group-hover:bg-black group-hover:text-white transition-colors">
                                <i :class="{
                                    'ri-file-pdf-line': material.type === 'pdf',
                                    'ri-video-line': material.type === 'youtube',
                                    'ri-file-line': material.type !== 'pdf' && material.type !== 'youtube'
                                }" class="text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-black text-[#383838] text-base tracking-tight leading-tight group-hover:text-black transition-colors truncate" 
                                    :title="material.title" x-text="material.title">
                                </h4>
                                <div class="mt-1 flex items-center gap-1.5">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide" x-text="material.type + ' Reference'">
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="py-3 border-y border-gray-50">
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest block mb-1">Source Type</span>
                            <span class="text-xs font-black text-[#383838] uppercase" x-text="material.type === 'youtube' ? 'Video Lecture' : 'Reading Material'">
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3">
                        <span class="flex items-center gap-1 text-[9px] font-black text-gray-400 group-hover:text-black uppercase tracking-tighter transition-colors">
                            <i class="ri-external-link-line text-base"></i> View Material
                        </span>
                        <i class="ri-arrow-right-line text-gray-300 group-hover:text-[#383838] group-hover:translate-x-1 transition-all"></i>
                    </div>
                </div>
            </template>
        </template>
    </div>
</div>
                                

                            </div>
                    </div>
                @endif
        </div>
    </main>
    </div>

   <div id="task-modal"
    class="hidden fixed inset-0 z-[10000] bg-black/40 backdrop-blur-md flex items-center justify-center p-4"
    x-data="taskModal()" @click.self="closeModal()">

    <div class="bg-white rounded-[40px] shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate-fade-in border border-gray-100"
        @click.stop>
        
        <div class="border-b border-gray-50 p-8 flex justify-between items-start">
            <div>
                <h2 class="text-3xl font-black text-[#383838] tracking-tight leading-tight" x-text="currentTask?.title"></h2>
                <p class="text-sm text-black mt-2 font-medium max-w-md" x-text="currentTask?.description"></p>
            </div>
            <button @click="closeModal()"
                class="w-12 h-12 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-black transition-all">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <div class="p-8 space-y-8">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50/50 p-5 rounded-[24px] border border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Max Weight</span>
                    <span class="text-xl font-black text-[#383838]" x-text="currentTask?.points + ' PTS'"></span>
                </div>
                <div class="bg-gray-50/50 p-5 rounded-[24px] border border-gray-100">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Due Date</span>
                    <span class="text-sm font-bold text-gray-600" x-text="formatDeadline(currentTask?.deadline)"></span>
                </div>
            </div>

            <div x-show="currentTask?.current_user_submission" class="space-y-4 animate-fade-in">
                <h3 class="font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] ml-1">Current Submission</h3>

                <div class="bg-white p-2 rounded-[32px] border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between p-4 bg-gray-50/50 rounded-[24px]">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-xl border border-gray-100 flex items-center justify-center text-[#383838] shadow-sm">
                                <i class="ri-file-3-line text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-bold text-[#383838] text-sm truncate max-w-[200px]" x-text="currentTask?.current_user_submission?.original_filename"></p>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">
                                    Logged: <span x-text="formatDate(currentTask?.current_user_submission?.submitted_at)"></span>
                                </p>
                            </div>
                        </div>
                        <a :href="'/' + currentTask?.current_user_submission?.file_path" target="_blank"
                            class="w-10 h-10 flex items-center justify-center bg-white border border-gray-100 rounded-xl text-gray-400 hover:text-black hover:border-black transition-all">
                            <i class="ri-download-2-line"></i>
                        </a>
                    </div>

                    <div x-show="currentTask?.current_user_submission?.grade !== null"
                        class="m-2 p-6 bg-[#383838] rounded-[24px] text-white shadow-xl shadow-gray-200">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-60">Resulting Grade</span>
                            <span class="text-3xl font-black" x-text="currentTask?.current_user_submission?.grade + ' / ' + currentTask?.points"></span>
                        </div>
                        <div x-show="currentTask?.current_user_submission?.feedback" class="pt-4 border-t border-white/10">
                            <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-2">Remarks</p>
                            <p class="text-sm font-medium leading-relaxed opacity-90" x-text="currentTask?.current_user_submission?.feedback"></p>
                        </div>
                        <div class="pt-4 border-t border-white/10 mt-2">
                            <a :href="`/student/tasks/${currentTask?.id}`"
                               class="w-full flex items-center justify-center gap-2 py-2.5 bg-white/20 hover:bg-white/30 text-white font-black text-xs uppercase tracking-widest rounded-xl transition">
                                <i class="ri-file-chart-line"></i> View Detailed Feedback
                            </a>
                        </div>
                    </div>

                    <div x-show="currentTask?.current_user_submission?.grade === null" class="p-2">
                        <button @click="showResubmitForm = !showResubmitForm"
                            class="w-full py-4 bg-gray-100 text-[#383838] border border-gray-100 font-black rounded-[20px] hover:bg-[#383838] hover:text-white transition-all text-xs uppercase tracking-widest">
                            <i class="ri-edit-line mr-2"></i> Edit Submission
                        </button>

                        <form x-show="showResubmitForm" @submit.prevent="resubmitTask($event)"
                            class="mt-4 p-6 bg-gray-50 rounded-[24px] border border-gray-200 animate-fade-in"
                            enctype="multipart/form-data">
                            <div class="flex items-center gap-2 text-[10px] text-gray-500 font-bold mb-4 uppercase tracking-wider">
                                <i class="ri-information-line text-sm"></i> Note: Existing file will be deleted
                            </div>
                            <input type="file" name="submission" required
                                class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:bg-[#383838] file:text-white mb-4 cursor-pointer">
                            <button type="submit" :disabled="resubmitting"
                                class="w-full py-3 bg-[#383838] text-white font-black rounded-xl hover:bg-[#2c2c2c] transition-all text-[10px] uppercase tracking-widest">
                                <span x-show="!resubmitting">Update Work</span>
                                <span x-show="resubmitting" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Processing...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div x-show="!currentTask?.current_user_submission" class="space-y-4 animate-fade-in">
                <h3 class="font-black text-[10px] text-gray-400 uppercase tracking-[0.2em] ml-1">Upload Work</h3>

                <form @submit.prevent="resubmitTask($event)" enctype="multipart/form-data" x-data="{ fileName: '' }"
                    class="bg-gray-50/50 border-2 border-dashed border-gray-200 rounded-[32px] p-10 text-center transition-all hover:border-black hover:bg-gray-50 group">

                    <label class="block cursor-pointer">
                        <div class="space-y-4">
                            <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-gray-100 flex items-center justify-center mx-auto text-gray-300 group-hover:text-black group-hover:scale-110 transition-all">
                                <i class="ri-upload-cloud-2-line text-3xl"></i>
                            </div>

                            <div>
                                <p class="text-sm font-bold text-[#383838]" x-text="fileName ? fileName : 'Drag & drop or browse'"></p>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">
                                    <span x-show="!fileName">Select file for evaluation</span>
                                    <span x-show="fileName" class="text-black">File attached and ready</span>
                                </p>
                            </div>
                        </div>
                        <input type="file" name="submission" required class="hidden"
                            @change="fileName = $event.target.files[0].name">
                    </label>

                    <button type="submit" :disabled="resubmitting || !fileName"
                        :class="fileName ? 'bg-[#383838] text-white cursor-pointeropacity-100' : 'bg-gray-300 cursor-not-allowed'"
                        class="mt-8 w-full py-4 text-white font-black rounded-2xl transition-all text-xs uppercase tracking-widest shadow-xl shadow-gray-200 flex items-center justify-center gap-3 hover:bg-[#2c2c2c]">
                        <span x-show="!resubmitting">Upload Work</span>
                        <template x-if="resubmitting">
                            <div class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Sending...
                            </div>
                        </template>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div x-data="{ 
        showQuizModal: false, 
        quizUrl: '', 
        isLocked: false,
        open(id) {
            this.quizUrl = `/student/quizzes/${id}/attempt`;
            this.showQuizModal = true;
            this.isLocked = false; // Reset lock state on open
        }
     }"
     x-show="showQuizModal" 
     x-cloak
     @open-quiz.window="open($event.detail.id)"
     @message.window="
     console.log('Message received:', $event.data);
        if ($event.data === 'lock-modal') isLocked = true;
        if ($event.data === 'unlock-modal') isLocked = false;
        if ($event.data === 'close-modal') {
            showQuizModal = false;
            isLocked = false;
        }
     "
     class="fixed inset-0 z-[9999] bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    
    <div class="bg-white w-full max-w-6xl h-[92vh] rounded-[2rem] overflow-hidden shadow-2xl flex flex-col relative">
        
        <div class="flex justify-between items-center p-6 border-b bg-white">
            <div class="flex items-center gap-3">
                <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse" x-show="isLocked"></div>
                <h3 class="font-black text-gray-900 tracking-tight">QUIZ WORKSPACE</h3>
            </div>

            <button x-show="!isLocked" 
                    @click="showQuizModal = false" 
                    class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition-all uppercase tracking-widest">
                Cancel / Close
            </button>
            
            <span x-show="isLocked" class="text-[10px] font-black text-red-500 uppercase tracking-widest">
                <i class="ri-lock-fill mr-1"></i> Quiz in Progress (Locked)
            </span>
        </div>

        <div class="flex-grow bg-gray-50">
            <iframe :src="quizUrl" class="w-full h-full border-none shadow-inner"></iframe>
        </div>
    </div>
</div>

<div x-data="materialViewer()" 
     @open-material.window="openMaterial($event.detail)" 
     x-show="showViewer" 
     x-cloak 
     @click.self="closeMaterial()"
     class="fixed inset-0 z-[20000] bg-black/40 backdrop-blur-md flex items-center justify-center p-4">
    
    <div class="bg-white rounded-[40px] shadow-2xl max-w-5xl w-full h-[85vh] flex flex-col overflow-hidden animate-fade-in border border-gray-100" @click.stop>
        
        <div class="border-b border-gray-50 p-6 flex justify-between items-center bg-white shrink-0">
            <div>
                <h2 class="text-xl font-black text-[#383838] tracking-tight leading-tight truncate max-w-[300px] md:max-w-xl" x-text="currentMaterial.title"></h2>
                <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest block mt-0.5" x-text="currentMaterial.type + ' Reference Material'"></span>
            </div>

            <div class="flex items-center gap-3">
                <button @click="closeMaterial()" class="px-6 py-2.5 bg-[#383838] text-white font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-[#2c2c2c] transition shadow-md">
                    Finish Reading
                </button>
            </div>
        </div>

        <div class="flex-grow w-full bg-gray-50 relative overflow-hidden">
            <div class="absolute inset-0 flex items-center justify-center -z-10 bg-gray-100">
                <div class="flex flex-col items-center gap-4">
                    <div class="w-8 h-8 border-2 border-[#383838] border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>

            <template x-if="currentMaterial.type === 'pdf'">
                <iframe :src="currentMaterial.url" class="w-full h-full border-none block"></iframe>
            </template>

            <template x-if="currentMaterial.type === 'youtube'">
                <div class="w-full h-full flex items-center justify-center bg-black">
                    <iframe :src="currentMaterial.url" class="w-full h-full border-none" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                </div>
            </template>
        </div>
    </div>
</div>

<div x-data="{ openWorkspaceTools: false, activeToolTab: 'compiler' }">
    <button @click="openWorkspaceTools = true" 
        class="fixed bottom-6 right-6 z-[15000] bg-[#383838] hover:bg-black text-white w-14 h-14 rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200 group border border-gray-700/10">
        <i class="ri-terminal-box-line text-2xl group-hover:rotate-12 transition-transform"></i>
    </button>

    <div x-show="openWorkspaceTools" x-cloak 
         @click.self="openWorkspaceTools = false"
         class="fixed inset-0 z-[25000] bg-black/40 backdrop-blur-md flex items-center justify-center p-4">
        
        <div class="bg-white rounded-[40px] shadow-2xl max-w-5xl w-full h-[85vh] flex flex-col overflow-hidden animate-fade-in border border-gray-100" @click.stop>
            
            <div class="border-b border-gray-100 p-6 flex flex-col sm:flex-row justify-between items-center bg-white gap-4 shrink-0">
                <div class="flex flex-wrap items-center gap-2 bg-gray-50/50 p-1.5 rounded-[18px] border border-gray-100">
                    <button @click="activeToolTab = 'compiler'"
                            :class="activeToolTab === 'compiler' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2 rounded-[12px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="ri-code-s-slash-line text-sm"></i> OneCompiler
                    </button>
                    
                    <button @click="activeToolTab = 'browser'"
                            :class="activeToolTab === 'browser' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2 rounded-[12px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="ri-global-line text-sm"></i> External Browser
                    </button>

                    <button @click="activeToolTab = 'document'"
                            :class="activeToolTab === 'document' ? 'bg-[#383838] text-white shadow-md' : 'text-gray-400 hover:text-gray-600'"
                            class="px-5 py-2 rounded-[12px] text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i class="ri-file-text-line text-sm"></i> Document Editor
                    </button>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <button @click="openWorkspaceTools = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-black transition-all">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="flex-grow w-full bg-gray-50 relative overflow-hidden flex flex-col">
                
                <div x-show="activeToolTab === 'compiler'" class="w-full h-full">
                    <iframe src="https://onecompiler.com" class="w-full h-full border-none bg-white"></iframe>
                </div>

                <div x-show="activeToolTab === 'browser'" class="w-full h-full flex flex-col bg-white" x-data="browserManager('{{ $class->id }}', '{{ csrf_token() }}')">
                    <div class="p-4 border-b border-gray-100 bg-white shrink-0">
                        <div class="flex items-center gap-3">
                            <button @click="browserBack()" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Go Back">
                                <i class="ri-arrow-left-line text-xl"></i>
                            </button>

                            <button @click="browserForward()" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Go Forward">
                                <i class="ri-arrow-right-line text-xl"></i>
                            </button>

                            <button @click="browserRefresh()" :class="refreshing ? 'animate-spin' : ''" class="p-2 hover:bg-gray-100 rounded-lg transition" title="Refresh Page">
                                <i class="ri-refresh-line text-xl"></i>
                            </button>

                            <div class="flex-1 flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2">
                                <i class="ri-global-line text-gray-400"></i>
                                <input type="text" x-model="urlInput" @keyup.enter="navigateTo()" placeholder="Search the web or enter an educational URL..." class="flex-1 bg-transparent border-0 focus:ring-0 text-sm p-0 focus:outline-none">
                            </div>

                            <button @click="navigateTo()" :disabled="loadingUrl" :class="loadingUrl ? 'bg-gray-400' : 'bg-blue-600 hover:bg-blue-700'" class="px-6 py-2 text-white rounded-xl font-bold transition text-xs">
                                <span x-show="!loadingUrl">Go</span>
                                <span x-show="loadingUrl">...</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-grow w-full bg-white relative">
                        <iframe id="dashboard-browser-frame" :src="browserUrl" class="w-full h-full border-none bg-white absolute inset-0"></iframe>
                    </div>
                </div>

                <div x-show="activeToolTab === 'document'" class="w-full h-full flex flex-col bg-white" 
                     x-data="{ 
                        docContent: '', 
                        get wordCount() { 
                            let text = this.docContent.trim();
                            return text ? text.split(/\s+/).length : 0; 
                        } 
                     }">
                    <div class="p-4 border-b border-gray-100 bg-white flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-semibold text-gray-500 flex items-center gap-1.5 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                                <i class="ri-text-spacing text-gray-400 text-sm"></i> Words: <span x-text="wordCount" class="font-bold text-gray-800">0</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="navigator.clipboard.writeText(docContent); alert('Content copied to clipboard!')" class="px-4 py-2 text-xs font-semibold bg-gray-50 text-gray-600 hover:bg-gray-100 hover:text-black rounded-xl border border-gray-100 transition flex items-center gap-1.5">
                                <i class="ri-file-copy-line"></i> Copy Text
                            </button>
                            <button @click="
                                const blob = new Blob([docContent], { type: 'text/plain' });
                                const link = document.createElement('a');
                                link.href = URL.createObjectURL(blob);
                                link.download = 'laboratory-notes.txt';
                                link.click();
                            " class="px-4 py-2 text-xs font-bold bg-[#383838] text-white hover:bg-black rounded-xl transition flex items-center gap-1.5 shadow-sm">
                                <i class="ri-download-cloud-line"></i> Save txt
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex-grow w-full p-6 bg-gray-50/50">
                        <textarea x-model="docContent" 
                                  placeholder="Type or paste your data text structure notes, source snippets, or answers for the assignment here..." 
                                  class="w-full h-full resize-none bg-white border border-gray-200 focus:border-gray-400 focus:ring-4 focus:ring-gray-100 rounded-3xl p-6 text-sm text-gray-700 focus:outline-none transition-all shadow-sm leading-relaxed font-mono"></textarea>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <script>
        const classId = {{ $class->id }};
        const profPeerId = 'PROF_{{ $class->faculty_id }}';
        const csrfToken = '{{ csrf_token() }}';
        let studentPeer = null;

        document.addEventListener('DOMContentLoaded', () => {
            studentPeer = new Peer('STUDENT_{{ auth()->id() }}');

            studentPeer.on('call', (call) => {
                call.answer();
                call.on('stream', (stream) => {
                    document.getElementById('lockdown-ui').classList.remove('hidden');
                    document.getElementById('normal-view').classList.add('hidden');
                    document.getElementById('professor-screen').srcObject = stream;
                    document.getElementById('professor-screen').play();
                });
                call.on('close', () => {
                    document.getElementById('lockdown-ui').classList.add('hidden');
                    document.getElementById('normal-view').classList.remove('hidden');
                });
            });

            setInterval(() => {
                fetch(`/student/heartbeat/${classId}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' } });
            }, 30000);
        });

        async function enterClassroom() {
            try {
                const stream = await navigator.mediaDevices.getDisplayMedia({
                    video: true,
                    audio: false
                });

                window.dispatchEvent(new CustomEvent('screen-shared'));

                // Call professor with metadata
                const call = studentPeer.call(profPeerId, stream, {
                    metadata: {
                        studentId: {{ auth()->id() }},
                        studentName: '{{ auth()->user()->name }}'
                    }
                });

                fetch("{{ route('student.mark-present', $class->id) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });

                stream.getVideoTracks()[0].onended = () => {
                    window.dispatchEvent(new CustomEvent('screen-stopped'));
                    location.reload();
                };
            } catch (err) {
                console.error("Capture Failed", err);
            }
        }

        // Lockdown Workspace logic
        function lockdownWorkspace() {
            return {
                tab: 'activities',
                tasks: [],
                uploadingTaskId: null,
                init() { this.fetchTasks(); setInterval(() => this.fetchTasks(), 3000); },
                fetchTasks() {
                    fetch(`/student/classroom/${classId}/live-tasks`)
                        .then(res => res.json())
                        .then(data => this.tasks = data.filter(t => !t.current_user_submission));
                },
                async submitTask(taskId, event) {
                    this.uploadingTaskId = taskId;
                    const formData = new FormData(event.target);
                    try {
                        const res = await fetch(`/student/tasks/${taskId}/submit`, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } });
                        if (res.ok) { alert('✅ Task submitted!'); this.fetchTasks(); }
                        else { alert('❌ Submission failed.'); }
                    } catch (error) { console.error(error); }
                    finally { this.uploadingTaskId = null; }
                }
            }
        }

        // Normal View Activity Log logic
     function classroomTasks() {
    return {
        tasks: [],
        filter: 'all', // New state for filtering

        init() { 
            this.fetchTasks(); 
            setInterval(() => this.fetchTasks(), 5000); 
        },

        fetchTasks() { 
            fetch(`/student/classroom/${classId}/live-tasks`)
                .then(res => res.json())
                .then(data => this.tasks = data); 
        },

        // This getter automatically updates the UI whenever 'filter' or 'tasks' change
        get filteredTasks() {
            if (this.filter === 'submitted') {
                return this.tasks.filter(t => t.current_user_submission !== null);
            }
            if (this.filter === 'missing') {
                return this.tasks.filter(t => t.current_user_submission === null);
            }
            return this.tasks;
        },

        openTaskModal(task) { 
            Alpine.$data(document.querySelector('[x-data*="taskModal"]')).openModal(task); 
        },

        formatDeadline(deadline) { 
            if (!deadline) return 'No deadline';
            const date = new Date(deadline);
            return isNaN(date.getTime()) 
                ? deadline 
                : date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
}

@php
    $initialQuizzes = $class->quizzes()
        ->where('published_at', '<=', now()) // Filter out future quizzes
        ->get()
        ->map(function($quiz) {
            $attempt = $quiz->attempts()->where('user_id', auth()->id())->first();
            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'expires_at' => $quiz->expires_at,
                'questions_count' => $quiz->questions_count,
                'total_points' => $quiz->total_points ?? $quiz->questions_count,
                'has_attempt' => (bool)$attempt,
                'user_score' => $attempt ? $attempt->score : null
            ];
        });
@endphp

const initialQuizData = @json($initialQuizzes);

function classroomQuizzes() {
    return {
        // Gamitin ang variable na inihanda sa itaas
        quizzes: @json($initialQuizzes),
        filter: 'all',
        init() {
            setInterval(() => this.fetchQuizzes(), 5000);
        },
        fetchQuizzes() {
            fetch(`/student/classroom/${classId}/live-quizzes`)
                .then(res => res.json())
                .then(data => { this.quizzes = data; })
                .catch(err => console.error('Error:', err));
        },
        get filteredQuizzes() {
            if (this.filter === 'completed') return this.quizzes.filter(q => q.has_attempt);
            if (this.filter === 'pending') return this.quizzes.filter(q => !q.has_attempt);
            return this.quizzes;
        },
        formatDeadline(dateString) {
            if (!dateString) return 'No Deadline';
            const date = new Date(dateString);
            return isNaN(date.getTime()) ? 'No Deadline' : date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
        },
        handleQuizClick(quiz) {
            if (quiz.has_attempt) {
                alert('You have already completed this quiz.');
            } else {
                if (confirm('Start this quiz? Timer will begin immediately.')) {
                    window.location.href = `/student/quizzes/${quiz.id}/attempt`;
                }
            }
        }
    }
}

@php
    $initialMaterials = $class->materials->map(function($m) {
        $url = $m->content;
        if ($m->type === 'youtube') {
            $url = \Illuminate\Support\Str::contains($url, 'embed') 
                ? $url 
                : \Illuminate\Support\Str::replace('watch?v=', 'embed/', $url);
        } else {
            $url = url('/' . $url);
        }
        return [
            'id' => $m->id,
            'title' => $m->title,
            'type' => $m->type,
            'url' => $url
        ];
    });
@endphp

function classroomMaterials() {
    return {
        // Feed the pre-processed PHP array into Alpine
        materials: @json($initialMaterials),
        showViewer: false,
        currentMaterial: { title: '', type: '', url: '', id: null },
        startTime: null,

        init() {
            // Live refresh every 5 seconds (Just like Quizzes and Tasks)
            setInterval(() => this.fetchMaterials(), 5000);
        },

        fetchMaterials() {
            fetch(`/student/classroom/${classId}/live-materials`)
                .then(res => res.json())
                .then(data => {
                    // Update the array. x-for will automatically draw new cards if this changes.
                    this.materials = data;
                })
                .catch(err => console.error('Error fetching materials:', err));
        },

        openMaterial(material) {
            this.currentMaterial = material;
            this.showViewer = true;
            this.startTime = new Date();

            // Log Start Activity
            fetch(`/student/materials/${material.id}/log-start`, {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
        },

        closeMaterial() {
            let endTime = new Date();
            let duration = Math.round((endTime - this.startTime) / 1000);

            // Log End Activity
            fetch(`/student/materials/${this.currentMaterial.id}/log-end`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ duration: duration })
            }).then(() => {
                this.showViewer = false;
                this.currentMaterial = { title: '', type: '', url: '', id: null };
            });
        }
    };
}

function quizModal() {
    return {
        showQuizModal: false,
        quizUrl: '',
        open(quizId) {
            this.quizUrl = `/student/quizzes/${quizId}/attempt?embed=true`;
            this.showQuizModal = true;
        }
    }
}

      function taskModal() {
    return {
        currentTask: null,
        showResubmitForm: false,
        resubmitting: false,

        // Safe date formatting for the "Logged/Received" text
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return isNaN(date.getTime()) ? dateString : date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
        },

        // Safe date formatting for the Due Date
        formatDeadline(deadline) {
            if (!deadline) return 'No deadline';
            const date = new Date(deadline);
            // Check if it's a valid date object. 
            // Returns a cleaner format: e.g., "Oct 24, 2023, 11:59 PM"
            return isNaN(date.getTime()) ? deadline : date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
        },

        openModal(task) {
            this.currentTask = task;
            this.showResubmitForm = false;
            document.getElementById('task-modal').classList.remove('hidden');
        },

        closeModal() {
            document.getElementById('task-modal').classList.add('hidden');
            this.currentTask = null;
        },

        async resubmitTask(event) {
            if (event) event.preventDefault();
            if (!confirm('Upload Work?')) return;

            this.resubmitting = true;
            const formData = new FormData(event.target);

            try {
                const res = await fetch(`/student/tasks/${this.currentTask.id}/submit`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    alert('✅ Upload Successful!');
                    this.closeModal();
                    // This tells the rest of your page to refresh the task list quietly
                    window.dispatchEvent(new CustomEvent('task-updated'));
                } else {
                    const err = await res.json();
                    alert('❌ Error: ' + (err.message || 'Upload failed'));
                }
            } catch (error) {
                alert('❌ Network Error');
            } finally {
                this.resubmitting = false;
            }
        }
    }
}

function materialViewer() {
    return {
        showViewer: false,
        currentMaterial: { title: '', type: '', url: '', id: null },
        startTime: null,

        openMaterial(material) {
            this.currentMaterial = material;
            this.showViewer = true;
            this.startTime = new Date();
            // Log logic remains the same
            fetch(`/student/materials/${material.id}/log-start`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
        },

        closeMaterial() {
            let endTime = new Date();
            let duration = Math.round((endTime - this.startTime) / 1000);
            fetch(`/student/materials/${this.currentMaterial.id}/log-end`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ duration: duration })
            }).then(() => {
                this.showViewer = false;
            });
        }
    }
}
    </script>

    <script>
 function browserManager() { 
    return {
        classId: classId,
        csrfToken: csrfToken,

        browserUrl: classId ? `/student/classroom/${classId}/browser-home` : 'about:blank', 
        urlInput: '',
        loadingUrl: false,
        refreshing: false,
        historyStack: classId ? [`/student/classroom/${classId}/browser-home`] : ['about:blank'],
        historyIndex: 0,
        blockedSites: [], 

        init() {
            if (!this.classId || this.classId === 'undefined') {
                console.error("Context Error: classId initialization parameters missing.");
                return;
            }
            this.loadBlockedRules();

            // 🟢 NEW: Listen for click navigation events originating inside the search result iframe
            window.addEventListener('message', (event) => {
                if (event.data && event.data.type === 'iframe-navigate') {
                    this.urlInput = event.data.url;
                    this.navigateTo();
                }
            });

            if (window.ipcRenderer) {
                window.ipcRenderer.on('site-blocked-by-electron', (event, url) => {
                    this.showBlockedPage(`"${this.cleanDomain(url)}" is restricted by the Instructor.`);
                });
            }
        },

        async loadBlockedRules() {
            try {
                const res = await fetch(`/student/classroom/${this.classId}/allowed-sites`);
                if (res.ok) {
                    const data = await res.json();
                    this.blockedSites = [
                        ...(data.pre_approved || []), 
                        ...(data.session_sites || []), 
                        ...(data.task_sites || [])
                    ];

                    const structuralDomains = this.blockedSites.map(site => {
                        return site.domain.replace(/^www\./, '').toLowerCase().trim();
                    });

                    if (window.ipcRenderer) {
                        window.ipcRenderer.send('update-blocklist', structuralDomains);
                    }
                }
            } catch (err) {
                console.error("Failed to load blocklist configurations:", err);
            }
        },

        cleanDomain(url) {
            try {
                let domain = url.includes('://') ? url.split('://')[1] : url;
                return domain.split('/')[0].split('?')[0].replace(/^www\./, '').toLowerCase().trim();
            } catch (e) {
                return url;
            }
        },

        isSiteBlocked(url) {
            const currentDomain = this.cleanDomain(url);
            return this.blockedSites.some(site => {
                const blockedDomain = site.domain.replace(/^www\./, '').toLowerCase().trim();
                return currentDomain === blockedDomain || currentDomain.endsWith('.' + blockedDomain);
            });
        },

        navigateTo(preserveHistory = true) {
            let input = this.urlInput.trim();
            if (!input) return;

            const isUrl = (input.includes('.') && !input.includes(' ')) || input.startsWith('http');

            // 🔍 SEARCH HANDLING BLOCK
            if (!isUrl) {
                this.loadingUrl = true;
                const lowerInput = input.toLowerCase();

                // 🟢 NEW: Dynamic Keyword Verification against restricted rules
                const containsBlockedKeyword = this.blockedSites.some(site => {
                    // Strips extensions (extracts "youtube" from "youtube.com" or "www.youtube.co.uk")
                    const domainClean = site.domain.replace(/^www\./, '').toLowerCase().trim();
                    const keyword = domainClean.split('.')[0]; 
                    
                    return keyword.length > 2 && lowerInput.includes(keyword);
                });

                if (containsBlockedKeyword) {
                    this.loadingUrl = false;
                    this.showBlockedPage(`Search query contains restricted keyword terms.`);
                    this.urlInput = '';
                    return;
                }

                // Point iframe src to isolated search route
                this.browserUrl = `/student/classroom/${this.classId}/search?q=${encodeURIComponent(input)}`;
                
                if (preserveHistory) {
                    this.historyStack = this.historyStack.slice(0, this.historyIndex + 1);
                    this.historyStack.push(this.browserUrl);
                    this.historyIndex = this.historyStack.length - 1;
                }

                const frame = document.getElementById('dashboard-browser-frame');
                if (frame) frame.src = this.browserUrl;
                
                this.urlInput = '';
                this.loadingUrl = false;
                return;
            }

            let url = input.toLowerCase();
            if (!url.startsWith('http')) {
                url = 'https://' + url;
            }

            this.loadingUrl = true;

            if (this.isSiteBlocked(url)) {
                this.loadingUrl = false;
                this.showBlockedPage(`"${this.cleanDomain(url)}" is blocked by your instructor.`);
                this.logViolationAttempt(url);
                this.urlInput = '';
                return;
            }

            if (preserveHistory) {
                this.historyStack = this.historyStack.slice(0, this.historyIndex + 1);
                this.historyStack.push(url);
                this.historyIndex = this.historyStack.length - 1;
            }

            this.browserUrl = url;
            const frame = document.getElementById('dashboard-browser-frame');
            if (frame) frame.src = url;

            this.logSiteVisit(url);
            this.urlInput = '';
            this.loadingUrl = false;
        },

        browserBack() {
            if (this.historyIndex > 0) {
                this.historyIndex--;
                this.loadUrlFromHistory(this.historyStack[this.historyIndex]);
            }
        },

        browserForward() {
            if (this.historyIndex < this.historyStack.length - 1) {
                this.historyIndex++;
                this.loadUrlFromHistory(this.historyStack[this.historyIndex]);
            }
        },

        loadUrlFromHistory(url) {
            this.loadingUrl = true;
            if (url.includes('/browser-home') || url.includes('/search?q=')) {
                this.browserUrl = url;
                const frame = document.getElementById('dashboard-browser-frame');
                if (frame) frame.src = url;
                this.loadingUrl = false;
                return;
            }
            if (this.isSiteBlocked(url)) {
                this.loadingUrl = false;
                this.showBlockedPage(`"${this.cleanDomain(url)}" is restricted.`);
                return;
            }
            this.browserUrl = url;
            const frame = document.getElementById('dashboard-browser-frame');
            if (frame) frame.src = url;
            this.loadingUrl = false;
        },

        browserRefresh() {
            this.refreshing = true;
            const frame = document.getElementById('dashboard-browser-frame');
            if (frame) {
                const currentUrl = frame.src;
                frame.src = 'about:blank';
                setTimeout(() => {
                    frame.src = currentUrl;
                    this.refreshing = false;
                }, 100);
            }
        },

        quickNav(url) {
            this.urlInput = url;
            this.navigateTo();
        },

        showBlockedPage(reason) {
            const blockedHtml = `
                <!DOCTYPE html>
                <html>
                <body style="font-family:sans-serif; background:#f8f9fa; display:flex; align-items:center; justify-content:center; height:100vh; margin:0;">
                    <div style="background:white; padding:40px; border-radius:12px; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.1); max-width:400px; border:1px solid #fee2e2;">
                        <div style="font-size:50px; margin-bottom:10px;">🚫</div>
                        <div style="color:#991b1b; background:#fef2f2; padding:15px; border-radius:8px; font-weight:bold;">${reason}</div>
                        <p style="color:#6b7280; font-size:12px; margin-top:15px;">This website or search keyword is restricted during this laboratory session.</p>
                    </div>
                </body>
                </html>`;
            const blob = new Blob([blockedHtml], { type: 'text/html' });
            this.browserUrl = URL.createObjectURL(blob);
            const frame = document.getElementById('dashboard-browser-frame');
            if (frame) frame.src = this.browserUrl;
        },

        logViolationAttempt(targetUrl) {
            fetch('/student/log-behavior', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ type: 'violation', detail: `Attempted to access blocked site: ${this.cleanDomain(targetUrl)}`, lab_session_id: this.classId })
            }).catch(err => console.error(err));
        },

        logSiteVisit(targetUrl) {
            if (targetUrl.startsWith('blob:') || targetUrl.includes('/search?q=')) return;
            fetch('/student/log-behavior', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ type: 'navigation', detail: targetUrl, lab_session_id: this.classId })
            }).catch(err => console.error(err));
        }
    };
}
    </script>

    

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out forwards;
        }

        .animate-bounce-short {
            animation: bounce 1s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</x-app-layout>