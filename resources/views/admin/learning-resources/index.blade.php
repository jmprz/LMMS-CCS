<x-app-layout>
      <x-slot name="header"></x-slot>
   <div class="fixed inset-0 flex bg-gray-100 overflow-hidden" x-data="{ sidebarOpen: false, createOpen: @js(old('title') !== null), createType: @js(old('type', 'pdf')) }">

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40 md:hidden" style="display: none;">
        </div>

        <!-- Fixed Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed md:static inset-y-0 left-0 z-50 w-64 border-r border-gray-300 bg-white mt-[80px] flex-shrink-0 flex flex-col justify-between h-[calc(100vh-80px)] transform transition-transform duration-300 ease-in-out md:translate-x-0">
            <nav class="mt-8 px-4 space-y-2 overflow-y-auto flex-1">
                <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">System Admin</div>

                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.dashboard') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
                </a>

                <a href="{{ route('admin.classroom') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.classroom*') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-folder-5-line mr-3 text-lg"></i> Classroom
                </a>

                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.users*') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-user-line mr-3 text-lg"></i> Users
                </a>

                <a href="{{ route('profile.edit') }}"
                    class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('profile.edit') ? 'bg-black text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                    <i class="ri-settings-5-line mr-3 text-lg"></i> Settings
                </a>

                <div class="mx-4 my-4 border-t border-gray-100"></div>

                <div class="px-4 text-[10px] font-black text-gray-400
            uppercase tracking-widest mb-2">
                    Learning Management
                </div>

                <a href="{{ route('admin.learning-topics.index') }}" class="flex items-center py-2.5 px-4 rounded-xl text-xs transition
                {{ request()->routeIs('admin.learning-topics.*')
                    ? 'bg-[#383838] text-white font-black'
                    : 'text-gray-600 font-bold hover:bg-gray-100' }}">
                                    <i class="ri-price-tag-3-line mr-3 text-lg"></i>
                    Learning Topics
                </a>

                <a href="{{ route('admin.learning-resources.index') }}" class="flex items-center py-2.5 px-4 rounded-xl text-xs transition
                {{ request()->routeIs('admin.learning-resources.*')
                    ? 'bg-[#383838] text-white font-black'
                    : 'text-gray-600 font-bold hover:bg-gray-100' }}">
                                    <i class="ri-book-open-line mr-3 text-lg"></i>
                    Resource Library
                </a>
                <a href="{{ route('admin.research-experiments.index') }}" class="flex items-center py-2.5 px-4 rounded-xl text-xs transition
                {{ request()->routeIs('admin.research-experiments.*')
                    ? 'bg-[#383838] text-white font-black'
                    : 'text-gray-600 font-bold hover:bg-gray-100' }}">
                                <i class="ri-flask-line mr-3 text-lg"></i>
                    Research Experiment
                </a>
                 @if(app()->environment(['local', 'testing']))
                    <a href="{{ route('admin.local-quiz-simulator.index') }}"
                        class="flex items-center rounded-xl px-4 py-2.5 text-xs font-bold {{ request()->routeIs('admin.local-quiz-simulator.*') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="ri-test-tube-line mr-3 text-lg"></i>
                        Local Quiz Simulator
                    </a>
                @endif
            </nav>

            <div class="p-4 border-t border-gray-200 bg-gray-50/50 relative flex-shrink-0" x-data="{ open: false }"
                @click.away="open = false">
                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute bottom-full left-4 right-4 mb-2 w-56 rounded-xl md:w-auto bg-white border border-gray-200 shadow-xl z-50 divide-y divide-gray-100"
                    style="display: none;">
                    <div class="py-1">
                        <form id="admin-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                            @csrf
                        </form>
                        <a href="{{ route('logout') }}"
                            class="flex items-center px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 transition"
                            onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                            <i class="ri-logout-box-r-line mr-2.5 text-red-500 text-sm"></i> Sign Out
                        </a>
                    </div>
                </div>

                <button @click="open = !open"
                    class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-gray-200/60 transition duration-150 text-left">
                    <div class="flex items-center min-w-0">
                        <div
                            class="h-9 w-9 rounded-xl bg-[#383838] flex items-center justify-center text-white uppercase font-black shadow-sm text-xs flex-shrink-0">
                            {{ substr(Auth::user()->name, 0, 1) }}{{ substr(strrchr(Auth::user()->name, " "), 1, 1) }}
                        </div>
                        <div class="ml-3 truncate">
                            <p class="text-xs font-black text-gray-800 truncate leading-snug">{{ Auth::user()->name }}
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider leading-none mt-0.5">
                                Administrator</p>
                        </div>
                    </div>
                    <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
                        :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
                </button>
            </div>
        </aside>

        <main class="flex-1 overflow-y-auto h-full flex flex-col min-w-0">
            <div class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 mt-[80px] flex-shrink-0">
                <button type="button" @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg" aria-label="Open navigation">
                    <i class="ri-menu-2-line text-xl"></i>
                </button>
                <span class="text-xs font-black uppercase text-gray-700 tracking-wider">LMMS Administration</span>
            </div>

            <div class="p-4 pb-16 sm:p-8 md:mt-[80px] space-y-7">
                {{-- Page heading / action --}}
                <div class="flex flex-col gap-5 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Learning Management / Resource Library</p>
                        <h1 class="mt-2 text-2xl font-black uppercase tracking-tight text-[#383838] sm:text-3xl">Supplementary Resources</h1>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-gray-500">Curate approved PDFs, YouTube tutorials and educational websites for topic-based student recommendations.</p>
                    </div>
                    @if($topics->isNotEmpty())
                        <button type="button" @click="createOpen = true"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-[#383838] px-6 py-3.5 text-xs font-black uppercase tracking-wider text-white shadow-sm transition hover:bg-black">
                            <i class="ri-add-line text-lg"></i> Add Resource
                        </button>
                    @else
                        <a href="{{ route('admin.learning-topics.index') }}"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-[#383838] px-6 py-3.5 text-xs font-black uppercase text-white">Create a Topic First</a>
                    @endif
                </div>

                @if(session('success'))
                    <div role="status" class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-800">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                        <p class="font-black">Please review your submission:</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Summary metrics --}}
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Total resources</p>
                        <p class="mt-2 text-3xl font-black text-gray-900">{{ $totalResources }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Approved</p>
                        <p class="mt-2 text-3xl font-black text-green-700">{{ $approvedCount }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Drafts</p>
                        <p class="mt-2 text-3xl font-black text-amber-700">{{ $draftCount }}</p>
                    </div>
                    <a href="{{ route('admin.learning-topics.index') }}" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-gray-400">
                        <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Learning topics <i class="ri-arrow-right-up-line ml-1"></i></p>
                        <p class="mt-2 text-3xl font-black text-gray-900">{{ $topics->count() }}</p>
                    </a>
                </div>

                {{-- Search / filters: submit GET so pagination and counts match ALL records, not just one page. --}}
                <form action="{{ route('admin.learning-resources.index') }}" method="GET" class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                        <div class="min-w-0 flex-1">
                            <label for="resource-search" class="mb-2 block text-[10px] font-black uppercase tracking-wider text-gray-500">Search resources</label>
                            <div class="relative">
                                <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400"></i>
                                <input id="resource-search" name="q" type="search" value="{{ request('q') }}" maxlength="100" placeholder="Search title, description or class code..."
                                    class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 pl-11 pr-4 text-sm focus:border-gray-400 focus:ring-gray-400">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:flex lg:items-end">
                            <div>
                                <label for="resource-type-filter" class="mb-2 block text-[10px] font-black uppercase tracking-wider text-gray-500">Type</label>
                                <select id="resource-type-filter" name="type" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 text-sm">
                                    <option value="">All types</option>
                                    @foreach(['pdf' => 'PDF', 'youtube' => 'YouTube', 'url' => 'Website'] as $value => $label)
                                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="resource-status-filter" class="mb-2 block text-[10px] font-black uppercase tracking-wider text-gray-500">Status</label>
                                <select id="resource-status-filter" name="status" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 text-sm">
                                    <option value="">All statuses</option>
                                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                                </select>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="resource-topic-filter" class="mb-2 block text-[10px] font-black uppercase tracking-wider text-gray-500">Topic</label>
                                <select id="resource-topic-filter" name="topic" class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 text-sm">
                                    <option value="">All topics</option>
                                    @foreach($topics as $topic)
                                        <option value="{{ $topic->id }}" @selected((string) request('topic') === (string) $topic->id)>{{ $topic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="flex-1 rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-black lg:flex-none">Apply</button>
                            <a href="{{ route('admin.learning-resources.index') }}" class="rounded-xl border border-gray-200 px-5 py-3 text-xs font-black uppercase tracking-wider text-gray-600 hover:bg-gray-50">Reset</a>
                        </div>
                    </div>
                    @if($topics->isNotEmpty())
                        <div class="mt-5 border-t border-gray-100 pt-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <p class="text-[10px] font-black uppercase tracking-wider text-gray-500">Browse by learning topic</p>
                                <a href="{{ route('admin.learning-topics.index') }}" class="text-[11px] font-bold text-gray-600 hover:underline">Manage topics <i class="ri-arrow-right-line"></i></a>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.learning-resources.index', request()->except('topic', 'page')) }}"
                                    class="rounded-full px-3.5 py-2 text-xs font-bold transition {{ !request('topic') ? 'bg-[#383838] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">All topics</a>
                                @foreach($topics as $topic)
                                    <a href="{{ route('admin.learning-resources.index', array_merge(request()->except('topic', 'page'), ['topic' => $topic->id])) }}"
                                        class="rounded-full px-3.5 py-2 text-xs font-bold transition {{ (string) request('topic') === (string) $topic->id ? 'bg-[#383838] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ $topic->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </form>

            <section class="space-y-4">
                <div class="mb-4 flex items-end justify-between">
                    <h2 class="text-xl font-black uppercase text-[#383838]">Library Resources</h2>
                    <span class="text-xs font-semibold text-gray-500">Showing {{ $resources->count() }} of {{ $resources->total() }} matching resources.</span>
                </div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($resources as $resource)
                        <article class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                                 x-data="{ editing: false, deleteOpen: false, editType: @js($resource->type) }">
                            <div class="flex items-center justify-between gap-3">
                                <span class="rounded-lg bg-gray-100 px-3 py-1 text-[10px] font-black uppercase text-gray-700">
                                    {{ ['pdf' => 'PDF', 'youtube' => 'YouTube', 'url' => 'Website'][$resource->type] ?? 'Legacy file' }}
                                </span>
                                <span class="rounded-lg px-3 py-1 text-[10px] font-black uppercase
                                             {{ $resource->status === 'approved' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $resource->status }}
                                </span>
                            </div>
                            <h3 class="mt-4 text-lg font-black leading-snug text-[#383838]">{{ $resource->title }}</h3>
                            @if($resource->description)
                                <p class="mt-2 text-sm text-gray-500">{{ $resource->description }}</p>
                            @endif
                            <p class="mt-3 text-xs font-semibold text-gray-500">
                                {{ $resource->subject_key }}{{ $resource->program ? ' · ' . $resource->program : '' }}
                            </p>
                            <div class="mt-4 flex flex-wrap gap-1.5">
                                @forelse($resource->learningTopics as $topic)
                                    <a href="{{ route('admin.learning-resources.index', array_merge(request()->except('topic', 'page'), ['topic' => $topic->id])) }}" class="rounded-lg bg-green-50 px-2.5 py-1 text-[10px] font-bold text-green-700 hover:bg-green-100" title="Filter by this topic">{{ $topic->name }}</a>
                                @empty
                                    <span class="text-xs text-amber-600">No topics assigned</span>
                                @endforelse
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
                                @if($resource->type === 'pdf')
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($resource->content) }}"
                                       target="_blank" rel="noopener noreferrer" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-200">Open PDF</a>
                                @elseif(in_array($resource->type, ['youtube', 'url']))
                                    <a href="{{ $resource->content }}" target="_blank" rel="noopener noreferrer"
                                       class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-200">Open Link</a>
                                @endif
                                <button type="button" @click="editing = !editing" class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">
                                    <span x-text="editing ? 'Close Editor' : 'Edit'"></span>
                                </button>
                                <button type="button" @click="deleteOpen = true"
                                        class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-100"
                                        aria-label="Delete {{ $resource->title }}">
                                    <i class="ri-delete-bin-line mr-1" aria-hidden="true"></i> Delete
                                </button>
                                @if($resource->status !== 'approved')
                                    <form action="{{ route('admin.learning-resources.status', $resource) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="rounded-xl bg-green-600 px-3 py-2 text-xs font-bold text-white hover:bg-green-700">Approve</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.learning-resources.status', $resource) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="draft">
                                        <button type="submit" class="rounded-xl bg-amber-100 px-3 py-2 text-xs font-bold text-amber-900 hover:bg-amber-200">Return to Draft</button>
                                    </form>
                                @endif
                            </div>

                            <div x-show="editing" x-cloak class="mt-5 border-t border-gray-100 pt-5">
                                <p class="mb-3 text-xs font-bold text-amber-700">Editing returns an approved resource to draft.</p>
                                <form action="{{ route('admin.learning-resources.update', $resource) }}" method="POST"
                                      enctype="multipart/form-data" class="space-y-3">
                                    @csrf @method('PUT')
                                    <label class="block text-xs font-bold text-gray-600">Title
                                        <input name="title" value="{{ $resource->title }}" maxlength="255" required class="mt-1 w-full rounded-xl border-gray-200 text-sm">
                                    </label>
                                    <label class="block text-xs font-bold text-gray-600">Description
                                        <textarea name="description" maxlength="3000" rows="2" class="mt-1 w-full rounded-xl border-gray-200 text-sm">{{ $resource->description }}</textarea>
                                    </label>
                                    <label class="block text-xs font-bold text-gray-600">Type
                                        <select name="type" x-model="editType" required class="mt-1 w-full rounded-xl border-gray-200 text-sm">
                                            <option value="pdf">PDF Document</option>
                                            <option value="youtube">YouTube Link</option>
                                            <option value="url">Website Link</option>
                                        </select>
                                    </label>
                                    <div x-show="editType === 'pdf'">
                                        <label class="block text-xs font-bold text-gray-600">{{ $resource->type === 'pdf' ? 'Replace PDF (optional)' : 'Upload PDF *' }}
                                            <input type="file" name="content_file" accept=".pdf,application/pdf"
                                                   :disabled="editType !== 'pdf'" :required="editType === 'pdf' && @js($resource->type !== 'pdf')"
                                                   class="mt-1 w-full rounded-xl border border-gray-200 p-2 text-sm">
                                        </label>
                                    </div>
                                    <div x-show="editType !== 'pdf'" x-cloak>
                                        <label class="block text-xs font-bold text-gray-600">URL
                                            <input type="url" name="content_url" maxlength="2048"
                                                   value="{{ in_array($resource->type, ['youtube', 'url']) ? $resource->content : '' }}"
                                                   :disabled="editType === 'pdf'" :required="editType !== 'pdf'"
                                                   class="mt-1 w-full rounded-xl border-gray-200 text-sm">
                                        </label>
                                    </div>
                                    <label class="block text-xs font-bold text-gray-600">Subject Key
                                        <input name="subject_key" value="{{ $resource->subject_key }}" required maxlength="191"
                                               pattern="[A-Za-z0-9_-]+" class="mt-1 w-full rounded-xl border-gray-200 text-sm">
                                    </label>
                                    <label class="block text-xs font-bold text-gray-600">Program (optional)
                                        <input name="program" value="{{ $resource->program }}" maxlength="32"
                                               class="mt-1 w-full rounded-xl border-gray-200 text-sm">
                                    </label>
                                    <fieldset>
                                        <legend class="mb-2 text-xs font-bold text-gray-600">Topics *</legend>
                                        <div class="max-h-40 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-3">
                                            @foreach($topics as $topic)
                                                <label class="flex items-center gap-2 text-xs">
                                                    <input type="checkbox" name="learning_topic_ids[]" value="{{ $topic->id }}"
                                                           @checked($resource->learningTopics->contains('id', $topic->id))
                                                           class="rounded border-gray-300">
                                                    {{ $topic->name }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                    <button type="submit" class="w-full rounded-xl bg-[#383838] px-4 py-3 text-xs font-black uppercase text-white hover:bg-black">Save Changes</button>
                                </form>
                            </div>
                            {{-- Delete confirmation is available directly on each resource card. --}}
                            <template x-teleport="body">
                                <div x-show="deleteOpen" x-cloak @keydown.escape.window="deleteOpen = false"
                                     class="fixed inset-0 z-[10000] flex items-center justify-center p-4"
                                     role="dialog" aria-modal="true" aria-labelledby="delete-resource-title-{{ $resource->id }}">
                                    <div class="absolute inset-0 bg-gray-950/65" @click="deleteOpen = false"></div>
                                    <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl sm:p-8"
                                         @click.stop>
                                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                                            <i class="ri-delete-bin-line text-2xl" aria-hidden="true"></i>
                                        </div>
                                        <h3 id="delete-resource-title-{{ $resource->id }}"
                                            class="text-xl font-black text-gray-900">Delete this resource?</h3>
                                        <p class="mt-3 break-words text-sm font-semibold text-gray-800">{{ $resource->title }}</p>
                                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                                            This permanently removes the resource and its topic links. If this PDF was uploaded
                                            to the library, its stored file will also be deleted. This cannot be undone.
                                        </p>
                                        <p class="mt-3 rounded-xl bg-amber-50 p-3 text-xs font-semibold text-amber-800">
                                            Resources with existing recommendation history cannot be deleted. Return those to Draft instead.
                                        </p>
                                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                            <button type="button" @click="deleteOpen = false"
                                                    class="rounded-xl border border-gray-200 px-5 py-3 text-xs font-black text-gray-700 hover:bg-gray-50">
                                                Cancel
                                            </button>
                                            <form action="{{ route('admin.learning-resources.destroy', $resource) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-full rounded-xl bg-red-600 px-5 py-3 text-xs font-black text-white hover:bg-red-700">
                                                    Yes, delete resource
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </article>
                    @empty
                        <div class="col-span-full rounded-3xl border-2 border-dashed border-gray-200 bg-white px-6 py-20 text-center">
                            <h3 class="font-black text-gray-700">No supplementary resources yet</h3>
                            <p class="mt-2 text-sm text-gray-400">Try adjusting your filters, or add a new PDF, YouTube tutorial or website.</p>
                        </div>
                    @endforelse
                </div>
                <div class="mt-8">{{ $resources->onEachSide(1)->links() }}</div>
            </section>
                {{-- Modal: create a resource without pushing the library out of view. --}}
                <div x-show="createOpen" x-cloak @keydown.escape.window="createOpen = false" class="fixed inset-0 z-[9999] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="new-resource-heading">
                    <div class="absolute inset-0 bg-gray-950/70 backdrop-blur-sm" @click="createOpen = false"></div>
                    <div class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
                        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-7">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">New supplementary resource</p>
                                <h2 id="new-resource-heading" class="mt-1 text-xl font-black uppercase text-[#383838]">Add Resource</h2>
                            </div>
                            <button type="button" @click="createOpen = false" aria-label="Close resource form" class="rounded-xl bg-gray-100 p-2.5 text-gray-600 hover:bg-gray-200"><i class="ri-close-line text-xl"></i></button>
                        </div>
                        <div class="overflow-y-auto px-5 py-6 sm:px-7">
                            <p class="mb-5 rounded-xl bg-blue-50 px-4 py-3 text-xs font-semibold text-blue-800">New resources are saved as drafts. Approve them after checking the content and assigned topics.</p>
                            @if($topics->isEmpty())
                                <a class="font-semibold text-gray-900 underline" href="{{ route('admin.learning-topics.index') }}">Create your first learning topic</a>
                            @else
                    <form action="{{ route('admin.learning-resources.store') }}" method="POST"
                          enctype="multipart/form-data" class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label for="new-title" class="mb-1 block text-xs font-black uppercase text-gray-600">Title *</label>
                                <input id="new-title" name="title" value="{{ old('title') }}" required maxlength="255"
                                       class="w-full rounded-xl border-gray-200 text-sm" placeholder="e.g. Introduction to Finite Automata">
                            </div>
                            <div>
                                <label for="new-description" class="mb-1 block text-xs font-black uppercase text-gray-600">Description</label>
                                <textarea id="new-description" name="description" rows="3" maxlength="3000"
                                          class="w-full rounded-xl border-gray-200 text-sm" placeholder="What will students learn?">{{ old('description') }}</textarea>
                            </div>
                            <div>
                                <label for="new-type" class="mb-1 block text-xs font-black uppercase text-gray-600">Resource Type *</label>
                                <select id="new-type" name="type" x-model="createType" required class="w-full rounded-xl border-gray-200 text-sm">
                                    <option value="pdf">PDF Document</option>
                                    <option value="youtube">YouTube Link</option>
                                    <option value="url">Website Link</option>
                                </select>
                            </div>
                            <div x-show="createType === 'pdf'">
                                <label for="new-pdf" class="mb-1 block text-xs font-black uppercase text-gray-600">PDF file * (max 20 MB)</label>
                                <input id="new-pdf" name="content_file" type="file" accept=".pdf,application/pdf"
                                       :disabled="createType !== 'pdf'" :required="createType === 'pdf'"
                                       class="w-full rounded-xl border border-gray-200 p-3 text-sm">
                            </div>
                            <div x-show="createType !== 'pdf'" x-cloak>
                                <label for="new-link" class="mb-1 block text-xs font-black uppercase text-gray-600"
                                       x-text="createType === 'youtube' ? 'YouTube URL *' : 'Website URL *'"></label>
                                <input id="new-link" type="url" name="content_url" maxlength="2048"
                                       :disabled="createType === 'pdf'" :required="createType !== 'pdf'"
                                       placeholder="https://..." class="w-full rounded-xl border-gray-200 text-sm">
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="new-subject" class="mb-1 block text-xs font-black uppercase text-gray-600">Subject Key *</label>
                                    <input id="new-subject" name="subject_key" value="{{ old('subject_key') }}" required maxlength="191"
                                           pattern="[A-Za-z0-9_-]+" placeholder="e.g. CS301"
                                           class="w-full rounded-xl border-gray-200 text-sm">
                                    <p class="mt-1 text-[11px] text-gray-400">For now, enter the matching LMS class code exactly.</p>
                                </div>
                                <div>
                                    <label for="new-program" class="mb-1 block text-xs font-black uppercase text-gray-600">Program (optional)</label>
                                    <input id="new-program" name="program" value="{{ old('program') }}" maxlength="32"
                                           placeholder="BSCS" class="w-full rounded-xl border-gray-200 text-sm">
                                    <p class="mt-1 text-[11px] text-gray-400">Leave blank for resources shared across programs.</p>
                                </div>
                            </div>
                            <fieldset>
                                <legend class="mb-2 text-xs font-black uppercase text-gray-600">Learning Topics * (select one or more)</legend>
                                <div class="max-h-52 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-4">
                                    @foreach($topics as $topic)
                                        <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-700">
                                            <input type="checkbox" name="learning_topic_ids[]" value="{{ $topic->id }}"
                                                   @checked(in_array($topic->id, old('learning_topic_ids', [])))
                                                   class="rounded border-gray-300 text-[#383838] focus:ring-gray-600">
                                            <span>{{ $topic->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <button type="submit" class="w-full rounded-xl bg-[#383838] px-5 py-3 text-xs font-black uppercase tracking-widest text-white hover:bg-black">
                                Save as Draft
                            </button>
                        </div>
                    </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
