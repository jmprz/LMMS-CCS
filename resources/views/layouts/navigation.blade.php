<nav x-data="{ open: false }" class="bg-white border-b border-gray-300 sticky top-0 z-50">
    <div class="max-w-9xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex items-center">
                <div class="shrink-0 flex items-center group">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3">
                        <div class="p-1.5 rounded-lg shadow-sm">
                            <img src="{{ ('/img/ccs_logo.png') }}" class="h-14 w-auto" alt="Logo">
                        </div>
                        <div class="hidden md:flex flex-col">
                            <span class="font-black tracking-tighter text-lg uppercase leading-tight text-gray-900">
                                Learning Management and Monitoring System
                            </span>
                            <span class="font-bold tracking-tighter text-xs uppercase text-gray-500 leading-tight">
                               College of Computing Studies
                            </span>
                        </div>

                    </a>
                </div>
            </div>



            <div class="flex items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center hover:opacity-80 transition">
                            <div class="text-right mr-3 hidden sm:block">
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest leading-none">
                                    Welcome</p>
                                <p class="text-sm font-black text-gray-800 leading-tight">
                                    {{ Auth::user()->name }}
                                    <i class="ri-arrow-down-s-line text-gray-400"></i>
                                </p>
                            </div>

                            <div
                                class="h-10 w-10 rounded-full bg-[#383838] flex items-center justify-center text-white uppercase font-bold shadow-md">
                                {{ substr(Auth::user()->name, 0, 1) }}{{ substr(strrchr(Auth::user()->name, " "), 1, 1) }}
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('My Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" class="text-red-600 font-bold"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Sign Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="p-2 rounded-md text-gray-500 hover:bg-gray-100">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" d="M4 6h16M4 12h16M4 18h16"
                            stroke-linecap="round" stroke-width="2" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" d="M6 18L18 6M6 6l12 12"
                            stroke-linecap="round" stroke-width="2" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-gray-50 border-t">
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-bold text-base">{{ Auth::user()->name }}</div>
                <div class="text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Sign Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>