<x-guest-layout>
    <div class="bg-white rounded-3xl shadow-2xl shadow-gray-900/10 overflow-hidden flex flex-col md:flex-row w-full border border-gray-100">

        <!-- Left Branding Panel -->
        <div class="w-full md:w-[45%] relative overflow-hidden bg-gradient-to-br from-[#2d2d2d] to-[#1a1a1a] text-white">
            <!-- Decorative glows -->
            <div class="pointer-events-none absolute -top-20 -right-16 w-56 h-56 rounded-full bg-white/5 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-12 w-48 h-48 rounded-full bg-white/5 blur-3xl"></div>

            <!-- Compact header for small screens -->
            <div class="relative flex md:hidden items-center gap-4 p-5 sm:p-6">
                <div class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 flex items-center justify-center">
                    <x-application-logo class="w-full h-auto object-contain" />
                </div>
                <div class="min-w-0 text-left">
                    <p class="font-bold text-xs sm:text-sm tracking-wide uppercase text-white leading-snug">
                        Learning and Monitoring Management System
                    </p>
                    <p class="text-[10px] font-semibold tracking-widest text-white/50 uppercase mt-1">
                        College of Computing Studies
                    </p>
                </div>
            </div>

            <!-- Full panel for tablet/desktop -->
            <div class="relative hidden md:flex flex-col justify-between items-center text-center p-10 min-h-[520px] h-full">
                <div class="flex flex-col items-center justify-center flex-1 space-y-6">
                    <div class="w-40 lg:w-44 h-auto flex items-center justify-center">
                        <x-application-logo class="w-full h-auto max-h-48 object-contain" />
                    </div>
                    <p class="font-bold text-sm lg:text-base tracking-[0.2em] uppercase text-white/90 leading-relaxed max-w-xs">
                        Learning and Monitoring Management System
                    </p>
                </div>

                <div class="w-full pt-6 border-t border-white/10">
                    <p class="text-xs font-bold tracking-widest text-white/60 uppercase">
                        College of Computing Studies
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="w-full md:w-[55%] p-6 sm:p-10 md:p-12 flex flex-col justify-center bg-white">
            <div class="mb-6 sm:mb-8">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1a202c] tracking-tight leading-tight">
                    Welcome back
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-2 font-medium">
                    Log in to your account to continue
                </p>
            </div>

            <!-- Global Error Display (Prints any hidden backend validation errors) -->
            @if ($errors->any())
                <div class="mb-5 flex items-start gap-3 p-4 bg-red-50 border border-red-100 text-red-700 text-xs rounded-2xl">
                    <svg class="h-4 w-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <ul class="space-y-1 font-medium">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-4 sm:space-y-5">
                @csrf

                <!-- School ID Field -->
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z" />
                        </svg>
                    </div>
                    <input id="school_id" 
                        class="block w-full pl-11 pr-4 py-3 sm:py-3.5 border border-gray-200 bg-gray-50/60 rounded-2xl text-gray-900 text-sm focus:ring-2 focus:ring-gray-800/10 focus:border-gray-800 focus:bg-white outline-none transition placeholder-gray-400" 
                        type="text" 
                        name="school_id" 
                        value="{{ old('school_id') }}" 
                        required 
                        autofocus 
                        autocomplete="username" 
                        placeholder="School ID" />
                </div>

                <!-- Password Field -->
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V8.25a4.5 4.5 0 10-9 0v2.25m-1.5 0h12a1.5 1.5 0 011.5 1.5v6.75a1.5 1.5 0 01-1.5 1.5h-12a1.5 1.5 0 01-1.5-1.5V12a1.5 1.5 0 011.5-1.5z" />
                        </svg>
                    </div>
                    <input id="password" 
                        class="block w-full pl-11 pr-10 py-3 sm:py-3.5 border border-gray-200 bg-gray-50/60 rounded-2xl text-gray-900 text-sm focus:ring-2 focus:ring-gray-800/10 focus:border-gray-800 focus:bg-white outline-none transition placeholder-gray-400" 
                        type="password" 
                        name="password" 
                        required 
                        autocomplete="current-password" 
                        placeholder="Password" />
                    
                    <button type="button" 
                        onclick="togglePasswordVisibility()" 
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" class="w-full bg-[#2d2d2d] hover:bg-black text-white font-bold py-3.5 px-4 rounded-2xl text-xs tracking-widest uppercase transition-all duration-150 ease-in-out shadow-lg shadow-gray-900/10 hover:scale-[1.01] active:scale-[0.99]">
                        Sign In
                    </button>
                </div>

                <!-- Forgot Password -->
                @if (Route::has('password.request'))
                    <div class="text-center pt-2">
                        <a class="text-[11px] font-bold text-gray-400 hover:text-gray-700 uppercase tracking-wider transition" href="{{ route('password.request') }}">
                            Forgot your password?
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</x-guest-layout>