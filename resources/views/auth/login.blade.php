<x-guest-layout>
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row w-full border border-gray-100">
        <!-- Left Branding Panel -->
        <div class="w-full md:w-1/2 bg-[#2d2d2d] p-6 sm:p-8 md:p-10 flex flex-col justify-between items-center text-center text-white min-h-[380px] sm:min-h-[460px]">
            <div class="w-full flex flex-col items-center justify-center flex-1 space-y-6">
                <div class="w-36 sm:w-44 md:w-52 h-auto flex items-center justify-center p-2">
                    <x-application-logo class="w-full h-auto max-h-48 object-contain" />
                </div>

                <div class="w-full py-4">
                    <h1 class="font-extrabold text-2xl sm:text-3xl md:text-lg tracking-wider leading-relaxed uppercase text-white px-2 break-words">
                        Learning and Monitoring Management System
                    </h1>
                </div>
            </div>

            <div class="w-full pt-4 mt-4 border-t border-gray-500/40">
                <p class="text-xs sm:text-sm font-extrabold tracking-widest text-white uppercase break-words">
                    College of Computing Studies
                </p>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="w-full md:w-1/2 p-6 sm:p-10 md:p-12 flex flex-col justify-center bg-white">
            <div class="mb-6 sm:mb-8">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1a202c] tracking-tight leading-tight">
                    Welcome<br />Back
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-2 font-medium">
                    Log in to your account to continue
                </p>
            </div>

            <!-- Global Error Display (Prints any hidden backend validation errors) -->
            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 text-xs rounded-xl">
                    <ul class="list-disc list-inside">
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
                <div>
                    <input id="school_id" 
                        class="block w-full px-4 py-3 sm:py-3.5 border border-gray-300 rounded-xl text-gray-900 text-sm focus:ring-2 focus:ring-gray-800 focus:border-transparent outline-none transition placeholder-gray-400" 
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
                    <input id="password" 
                        class="block w-full px-4 py-3 sm:py-3.5 border border-gray-300 rounded-xl text-gray-900 text-sm focus:ring-2 focus:ring-gray-800 focus:border-transparent outline-none transition placeholder-gray-400 pr-10" 
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
                    <button type="submit" class="w-full bg-[#333333] hover:bg-black text-white font-bold py-3.5 px-4 rounded-xl text-xs tracking-widest uppercase transition duration-150 ease-in-out shadow-sm">
                        SIGN IN
                    </button>
                </div>

                <!-- Forgot Password -->
                @if (Route::has('password.request'))
                    <div class="text-center pt-2">
                        <a class="text-[11px] font-bold text-gray-400 hover:text-gray-700 uppercase tracking-wider transition" href="{{ route('password.request') }}">
                            FORGOT YOUR PASSWORD?
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