<div class="fixed inset-x-0 bottom-0 top-20 flex bg-gray-100 overflow-hidden">
    
    <aside class="w-64 border-r border-gray-300 bg-white flex-shrink-0 flex flex-col justify-between h-full">
        <nav class="mt-8 px-4 space-y-2">
            <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">System Admin</div>

            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.dashboard') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-dashboard-line mr-3 text-lg"></i> Dashboard
            </a>

            <a href="{{ route('admin.classroom') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.classroom*') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-folder-5-line mr-3 text-lg"></i> Classroom
            </a>

            <a href="{{ route('admin.users.index') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.users*') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-user-line mr-3 text-lg"></i> Users
            </a>

            <a href="{{ route('profile.edit') }}"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('profile.edit') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-settings-5-line mr-3 text-lg"></i> Settings
            </a>
            <div class="px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Platform Support</div>

            <a href="#"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.about') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-information-line mr-3 text-lg"></i> About System
            </a>

            <a href="#"
                class="flex items-center py-2.5 px-4 rounded-xl text-xs {{ request()->routeIs('admin.faqs') ? 'bg-[#383838] text-white font-black' : 'text-gray-600 font-bold hover:bg-gray-100' }} transition">
                <i class="ri-questionnaire-line mr-3 text-lg"></i> FAQs Hub
            </a>
        </nav>

    <div class="p-4 border-t border-gray-200 bg-gray-50/50 relative" x-data="{ open: false }" @click.away="open = false">
        
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-100"
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

        <button @click="open = !open" class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-gray-200/60 transition duration-150 text-left">
            <div class="flex items-center min-w-0">
                <div class="h-9 w-9 rounded-xl bg-[#383838] flex items-center justify-center text-white uppercase font-black shadow-sm text-xs flex-shrink-0">
                    {{ substr(Auth::user()->name, 0, 1) }}{{ substr(strrchr(Auth::user()->name, " "), 1, 1) }}
                </div>
                
                <div class="ml-3 truncate">
                    <p class="text-xs font-black text-gray-800 truncate leading-snug">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider leading-none mt-0.5">
                        Administrator
                    </p>
                </div>
            </div>
           <i class="ri-arrow-up-s-line text-gray-400 text-base transition group-hover:text-gray-700 mr-1"
               :class="open ? 'transform rotate-180 text-gray-700' : ''"></i>
        </button>
    </div>
    </aside>

    <main class="flex-1 overflow-y-auto h-full p-8">
        <div class="max-w-4xl mx-auto space-y-6 pb-16">
            
            <div class="mb-2">
                <h1 class="text-2xl font-black text-gray-800 tracking-tight uppercase text-sm">Account Settings</h1>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Update system administrator access credentials.</p>
            </div>

            <section class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                <header class="border-b border-gray-100 pb-5 mb-6">
                    <h2 class="text-xl font-black text-gray-800 tracking-tight uppercase text-sm">
                        {{ __('Profile Information') }}
                    </h2>
                    <p class="mt-1 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        {{ __("Update your account's profile credentials and system email address.") }}
                    </p>
                </header>

                <form id="send-verification" method="post" action="{{ route('verification.send') }}">
                    @csrf
                </form>

                <form method="post" action="{{ route('profile.update') }}" class="space-y-6 max-w-xl">
                    @csrf
                    @method('patch')

                    <div>
                        <label for="name" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">
                            {{ __('Full Name') }}
                        </label>
                        <input id="name" name="name" type="text" 
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl text-sm font-bold text-gray-800 focus:bg-white focus:border-black focus:ring-1 focus:ring-black transition duration-150" 
                            value="{{ old('name', $user->name) }}" 
                            required autofocus autocomplete="name" />
                        <x-input-error class="mt-2 text-xs font-bold text-red-500" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <label for="email" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">
                            {{ __('Email Address') }}
                        </label>
                        <input id="email" name="email" type="email" 
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl text-sm font-bold text-gray-800 focus:bg-white focus:border-black focus:ring-1 focus:ring-black transition duration-150" 
                            value="{{ old('email', $user->email) }}" 
                            required autocomplete="username" />
                        <x-input-error class="mt-2 text-xs font-bold text-red-500" :messages="$errors->get('email')" />

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-xl flex items-center justify-between">
                                <p class="text-xs font-bold text-yellow-700">
                                    {{ __('Your email address is currently unverified.') }}
                                </p>
                                <button form="send-verification" class="text-xs font-black text-yellow-800 uppercase tracking-wider hover:underline focus:outline-none">
                                    {{ __('Resend Link') }}
                                </button>
                            </div>

                            @if (session('status') === 'verification-link-sent')
                                <p class="mt-2 text-xs font-bold text-green-600 uppercase tracking-wide">
                                    {{ __('A new verification link has been dispatched to your box.') }}
                                </p>
                            @endif
                        @endif
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <button type="submit" class="bg-black text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm hover:bg-gray-800 transition active:scale-95 duration-150">
                            {{ __('Save Changes') }}
                        </button>

                        @if (session('status') === 'profile-updated')
                            <div x-data="{ show: true }"
                                 x-show="show"
                                 x-transition:enter="transition ease-out duration-300"
                                 x-transition:enter-start="opacity-0 translate-x-2"
                                 x-transition:enter-end="opacity-100 translate-x-0"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 x-init="setTimeout(() => show = false, 2500)"
                                 class="flex items-center text-xs font-bold text-green-600 uppercase tracking-wider">
                                 <i class="ri-checkbox-circle-fill mr-1.5 text-base"></i>
                                 {{ __('Saved successfully.') }}
                            </div>
                        @endif
                    </div>
                </form>
            </section>

            <section class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                <header class="border-b border-gray-100 pb-5 mb-6">
                    <h2 class="text-xl font-black text-gray-800 tracking-tight uppercase text-sm">
                        {{ __('Update Password') }}
                    </h2>
                    <p class="mt-1 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        {{ __('Ensure your account is utilizing a long, random authentication sequence to maintain security.') }}
                    </p>
                </header>
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </section>

            <section class="bg-white p-8 rounded-xl shadow-sm border border-red-100">
                <header class="border-b border-red-50 pb-5 mb-6">
                    <h2 class="text-xl font-black text-red-600 tracking-tight uppercase text-sm">
                        {{ __('Danger Zone') }}
                    </h2>
                    <p class="mt-1 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        {{ __('Once your admin account is purged, all system dependencies and logging footprints will be unlinked.') }}
                    </p>
                </header>
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </section>
            
        </div>
    </main>
</div>