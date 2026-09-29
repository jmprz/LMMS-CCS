<x-app-layout>
    <x-slot name="header"></x-slot>
    <div class="fixed inset-0 flex bg-gray-100 overflow-hidden" x-data="{ sidebarOpen: false }">

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
            class="fixed inset-0 bg-black/50 z-40 md:hidden" style="display: none;">
        </div>

        <!-- Fixed Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
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
            <!-- Mobile Header Toggle Bar -->
            <div
                class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 mt-[80px] flex-shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="ri-menu-2-line text-xl"></i>
                </button>
                <span class="text-xs font-black uppercase text-gray-700 tracking-wider">Navigation</span>
            </div>

            <div class="p-4 pb-16 sm:p-8 md:mt-[80px]" x-data="{
                modalOpen: @js($errors->has('name') || $errors->has('description')),
                modalMode: @js(old('_topic_id') ? 'edit' : 'create'),
                topicId: @js(old('_topic_id')),
                topicName: @js(old('name', '')),
                topicDescription: @js(old('description', '')),
                deleteOpen: false,
                deleteId: null,
                deleteName: '',
                openCreate() {
                    this.modalMode = 'create'; this.topicId = null;
                    this.topicName = ''; this.topicDescription = ''; this.modalOpen = true;
                },
                openEdit(topic) {
                    this.modalMode = 'edit'; this.topicId = topic.id;
                    this.topicName = topic.name; this.topicDescription = topic.description || '';
                    this.modalOpen = true;
                },
                openDelete(topic) {
                    this.deleteId = topic.id; this.deleteName = topic.name; this.deleteOpen = true;
                },
                topicAction() {
                    const base = @js(route('admin.learning-topics.store'));
                    return this.modalMode === 'edit'
                        ? @js(route('admin.learning-topics.update', ['learning_topic' => '__ID__'])).replace('__ID__', this.topicId)
                        : base;
                },
                deleteAction() {
                    return @js(route('admin.learning-topics.destroy', ['learning_topic' => '__ID__'])).replace('__ID__', this.deleteId);
                }
            }" @keydown.escape.window="modalOpen = false; deleteOpen = false">
                <div class="mx-auto max-w-7xl space-y-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[.22em] text-gray-400">LMMS / Learning Management</p>
                            <h1 class="mt-2 text-2xl font-black uppercase tracking-tight text-[#383838] sm:text-3xl">Learning Topics</h1>
                            <p class="mt-2 max-w-2xl text-sm text-gray-500">A standardized topic catalog for professor materials, quizzes and personalized learning resources.</p>
                        </div>
                        <button type="button" @click="openCreate()"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#383838] px-6 py-3.5 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-gray-200 transition hover:bg-black">
                            <i class="ri-add-line text-lg"></i> Add Learning Topic
                        </button>
                    </div>

                    @if(session('success'))
                        <div role="status" class="flex items-center gap-3 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-800">
                            <i class="ri-checkbox-circle-line text-xl"></i>{{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                            <p class="font-black">Please review the following:</p>
                            <ul class="mt-2 list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between"><span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Topics</span><i class="ri-price-tag-3-line rounded-xl bg-gray-100 p-2.5 text-xl text-gray-700"></i></div>
                            <p class="mt-3 text-3xl font-black tabular-nums text-[#383838]">{{ number_format($totalTopics) }}</p>
                            <p class="mt-1 text-xs text-gray-400">Standardized learning topics</p>
                        </div>
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between"><span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Topics In Use</span><i class="ri-links-line rounded-xl bg-green-50 p-2.5 text-xl text-green-700"></i></div>
                            <p class="mt-3 text-3xl font-black tabular-nums text-[#383838]">{{ number_format($usedTopics) }}</p>
                            <p class="mt-1 text-xs text-gray-400">Linked to learning content or quizzes</p>
                        </div>
                        <div class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex items-center justify-between"><span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Resource Tags</span><i class="ri-book-open-line rounded-xl bg-blue-50 p-2.5 text-xl text-blue-700"></i></div>
                            <p class="mt-3 text-3xl font-black tabular-nums text-[#383838]">{{ number_format($resourceLinks) }}</p>
                            <p class="mt-1 text-xs text-gray-400">Topic-to-resource assignments</p>
                        </div>
                    </div>

                    <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 p-5 sm:p-6">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                                <div><h2 class="text-lg font-black uppercase text-[#383838]">Topic Catalog</h2><p class="mt-1 text-xs text-gray-400">Find, edit and manage available topics.</p></div>
                                <span class="rounded-full bg-gray-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-gray-600">{{ $topics->total() }} matching</span>
                            </div>
                            <form method="GET" action="{{ route('admin.learning-topics.index') }}" class="grid gap-3 md:grid-cols-12">
                                <div class="relative md:col-span-5">
                                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400"></i>
                                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, slug or description..." aria-label="Search learning topics"
                                        class="w-full rounded-xl border-gray-200 bg-gray-50 py-3 pl-11 pr-4 text-sm focus:border-gray-500 focus:ring-gray-400">
                                </div>
                                <select name="usage" aria-label="Filter topics by usage" class="rounded-xl border-gray-200 bg-gray-50 px-3 py-3 text-sm font-semibold text-gray-700 md:col-span-3">
                                    <option value="all" @selected(request('usage', 'all') === 'all')>All topics</option>
                                    <option value="used" @selected(request('usage') === 'used')>In use</option>
                                    <option value="unused" @selected(request('usage') === 'unused')>Unused</option>
                                </select>
                                <select name="sort" aria-label="Sort topics" class="rounded-xl border-gray-200 bg-gray-50 px-3 py-3 text-sm font-semibold text-gray-700 md:col-span-2">
                                    <option value="name" @selected(request('sort', 'name') === 'name')>Name A–Z</option>
                                    <option value="newest" @selected(request('sort') === 'newest')>Newest first</option>
                                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
                                    <option value="resources" @selected(request('sort') === 'resources')>Most resources</option>
                                </select>
                                <button type="submit" class="rounded-xl bg-[#383838] px-4 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-black md:col-span-2">Apply</button>
                                @if(request()->filled('search') || request()->filled('usage') || request()->filled('sort'))
                                    <a href="{{ route('admin.learning-topics.index') }}" class="text-xs font-bold text-gray-500 hover:text-black md:col-span-12">Clear all filters <i class="ri-close-line"></i></a>
                                @endif
                            </form>
                        </div>

                        <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                            @forelse($topics as $topic)
                                @php
                                    $linkedResources = (int) $topic->learning_resources_count;
                                    $linkedMaterials = (int) $topic->materials_count;
                                    $linkedQuizzes = (int) $topic->quizzes_count;
                                    $linkedRecommendations = (int) $topic->recommendations_count;
                                    $isUsed = $linkedResources + $linkedMaterials + $linkedQuizzes + $linkedRecommendations > 0;
                                @endphp
                                <article class="group flex min-h-64 flex-col justify-between rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-gray-400 hover:shadow-lg hover:shadow-gray-100">
                                    <div>
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-xl text-[#383838] group-hover:bg-[#383838] group-hover:text-white transition"><i class="ri-price-tag-3-line"></i></span>
                                            @if($isUsed)
                                                <span class="rounded-full bg-green-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-green-700">In use</span>
                                            @else
                                                <span class="rounded-full bg-gray-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-gray-500">Unused</span>
                                            @endif
                                        </div>
                                        <h3 class="mt-4 break-words text-lg font-black leading-tight text-[#383838]">{{ $topic->name }}</h3>
                                        <p class="mt-1 break-all font-mono text-[11px] text-gray-400">{{ $topic->slug }}</p>
                                        @if($topic->description)
                                            <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-gray-500" title="{{ $topic->description }}">{{ $topic->description }}</p>
                                        @else
                                            <p class="mt-3 text-xs italic text-gray-400">No description added.</p>
                                        @endif
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700">{{ $linkedResources }} {{ \Illuminate\Support\Str::plural('resource', $linkedResources) }}</span>
                                            @if($linkedMaterials > 0)<span class="rounded-lg bg-purple-50 px-2.5 py-1 text-[10px] font-bold text-purple-700">{{ $linkedMaterials }} {{ \Illuminate\Support\Str::plural('material', $linkedMaterials) }}</span>@endif
                                            @if($linkedQuizzes > 0)<span class="rounded-lg bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700">{{ $linkedQuizzes }} {{ \Illuminate\Support\Str::plural('quiz', $linkedQuizzes) }}</span>@endif
                                            @if($linkedRecommendations > 0)<span class="rounded-lg bg-gray-100 px-2.5 py-1 text-[10px] font-bold text-gray-600">{{ $linkedRecommendations }} recommendations</span>@endif
                                        </div>
                                    </div>
                                    <div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
                                        <span class="text-[10px] font-semibold text-gray-400">Added {{ $topic->created_at?->format('M j, Y') ?? '—' }}</span>
                                        <div class="flex gap-2">
                                            <button type="button" @click="openEdit(@js(['id' => $topic->id, 'name' => $topic->name, 'description' => $topic->description]))"
                                                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-2 text-xs font-black text-gray-700 hover:bg-gray-100"><i class="ri-edit-line"></i> Edit</button>
                                            <button type="button" @click="openDelete(@js(['id' => $topic->id, 'name' => $topic->name]))"
                                                @disabled($isUsed)
                                                title="{{ $isUsed ? 'This topic has linked records and cannot be deleted.' : 'Delete this unused topic' }}"
                                                class="inline-flex items-center gap-1 rounded-lg border border-red-100 px-3 py-2 text-xs font-black text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-35"><i class="ri-delete-bin-line"></i> Delete</button>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="col-span-full flex flex-col items-center rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 py-16 text-center">
                                    <i class="ri-search-eye-line text-5xl text-gray-300"></i>
                                    <h3 class="mt-4 text-base font-black text-gray-700">No topics found</h3>
                                    <p class="mt-1 text-sm text-gray-400">Try different filters or add a new learning topic.</p>
                                    <div class="mt-5 flex gap-2"><a href="{{ route('admin.learning-topics.index') }}" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-black">Clear filters</a><button @click="openCreate()" class="rounded-xl bg-[#383838] px-5 py-2.5 text-xs font-black text-white">Add Topic</button></div>
                                </div>
                            @endforelse
                        </div>
                        @if($topics->hasPages())
                            <div class="border-t border-gray-100 px-5 py-5 sm:px-6">{{ $topics->links() }}</div>
                        @endif
                    </section>
                </div>

                {{-- Add/edit modal: uses existing POST/PUT endpoints; leave selected filters alone until submit. --}}
                <div x-show="modalOpen" x-cloak x-transition.opacity
                    class="fixed inset-0 z-[9999] flex items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
                    role="dialog" aria-modal="true" aria-label="Learning topic editor" @click.self="modalOpen = false">
                    <div class="my-8 w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
                        <div class="flex items-start justify-between border-b border-gray-100 px-7 py-6">
                            <div><p class="text-[10px] font-black uppercase tracking-widest text-gray-400" x-text="modalMode === 'edit' ? 'Update catalog' : 'Expand catalog'"></p><h2 class="mt-1 text-xl font-black uppercase text-[#383838]" x-text="modalMode === 'edit' ? 'Edit Learning Topic' : 'Add Learning Topic'"></h2></div>
                            <button type="button" @click="modalOpen = false" class="rounded-full bg-gray-100 p-2.5 text-gray-500 hover:bg-gray-200" aria-label="Close modal"><i class="ri-close-line text-lg"></i></button>
                        </div>
                        <form method="POST" :action="topicAction()" class="space-y-5 p-7">
                            @csrf
                            <input type="hidden" name="_method" value="PUT" :disabled="modalMode !== 'edit'">
                            <input type="hidden" name="_topic_id" :value="topicId || ''">
                            <div><label for="topic-name" class="mb-2 block text-xs font-black uppercase text-gray-600">Topic Name *</label><input id="topic-name" x-model="topicName" name="name" type="text" maxlength="191" required placeholder="e.g. Finite Automata" class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm focus:border-gray-500 focus:ring-gray-400"></div>
                            <div><label for="topic-description" class="mb-2 block text-xs font-black uppercase text-gray-600">Description</label><textarea id="topic-description" x-model="topicDescription" name="description" maxlength="3000" rows="4" placeholder="Describe the learning objectives or concepts covered..." class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-sm focus:border-gray-500 focus:ring-gray-400"></textarea><p class="mt-1 text-[11px] text-gray-400">A useful description helps professors select the correct standardized topic.</p></div>
                            <div class="flex gap-3 border-t border-gray-100 pt-5"><button type="button" @click="modalOpen = false" class="flex-1 rounded-xl border border-gray-200 py-3 text-xs font-black uppercase text-gray-600 hover:bg-gray-50">Cancel</button><button type="submit" class="flex-1 rounded-xl bg-[#383838] py-3 text-xs font-black uppercase text-white hover:bg-black" x-text="modalMode === 'edit' ? 'Save Changes' : 'Create Topic'"></button></div>
                        </form>
                    </div>
                </div>

                {{-- Delete confirmation is available only for topics without references. --}}
                <div x-show="deleteOpen" x-cloak x-transition.opacity
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                    role="alertdialog" aria-modal="true" aria-label="Confirm topic deletion" @click.self="deleteOpen = false">
                    <div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-2xl text-red-600"><i class="ri-delete-bin-line"></i></div>
                        <h2 class="mt-5 text-xl font-black text-[#383838]">Delete Learning Topic?</h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">Permanently delete <strong class="break-words text-gray-800" x-text="deleteName"></strong>? This action cannot be undone.</p>
                        <p class="mt-3 rounded-xl bg-amber-50 p-3 text-xs font-bold text-amber-800">Topics referenced by quizzes, classroom materials, learning resources or recommendations are protected from deletion.</p>
                        <form method="POST" :action="deleteAction()" class="mt-6 flex gap-3">@csrf @method('DELETE')<button type="button" @click="deleteOpen = false" class="flex-1 rounded-xl border border-gray-200 py-3 text-xs font-black uppercase">Cancel</button><button type="submit" class="flex-1 rounded-xl bg-red-600 py-3 text-xs font-black uppercase text-white hover:bg-red-700">Delete Topic</button></form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-app-layout>
