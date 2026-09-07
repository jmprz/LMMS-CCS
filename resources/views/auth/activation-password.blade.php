<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-gray-100">
        <div class="w-full max-w-sm sm:max-w-md bg-white rounded-2xl sm:rounded-xl shadow-lg sm:shadow-md p-6 sm:p-8">
            
            <div class="mb-5 sm:mb-6 text-center sm:text-left">
                <h2 class="text-3xl sm:text-4xl font-black text-gray-900 mb-2">Secure Profile</h2>
                <p class="text-gray-600 text-sm sm:text-md">Final Step: Define secure access signatures</p>
            </div>

            <p class="text-xs sm:text-sm font-medium text-gray-500 mb-4 text-center sm:text-left">
               Your identity has been successfully verified. Please update your temporary default password to a new, secure password to complete your registration and activate your account.
            </p>

            <div class="mb-5 bg-gray-50 border border-gray-200 sm:border-gray-300 rounded-xl p-3 sm:p-4 text-[10px] sm:text-xs font-semibold text-gray-600 space-y-2">
                <span class="block text-gray-900 uppercase text-[9px] sm:text-[10px] tracking-wider font-black mb-1 sm:mb-0.5">Password Complexity Guidelines:</span>
                <div class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-[#383838]"></div> Minimum of 8 total token indices</div>
                <div class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-[#383838]"></div> At least one uppercase character (A-Z)</div>
                <div class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-[#383838]"></div> At least one digital value representation (0-9)</div>
            </div>

            <form method="POST" action="{{ route('activation.update_password') }}" x-data="{ showPass: false, showConfirmPass: false }" class="space-y-4">
                @csrf
                
                <!-- Password Field -->
                <div>
                    <x-input-label for="password" value="New Secure Password" class="font-bold text-[10px] sm:text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password"
                               class="w-full px-4 sm:px-5 py-3 sm:py-3.5 bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all text-sm sm:text-base" />
                        
                        <button type="button" @click="showPass = !showPass" class="absolute right-3 sm:right-4 top-3 sm:top-4 text-gray-500 hover:text-gray-800 transition">
                            <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="showPass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <x-input-label for="password_confirmation" value="Confirm Secure Password" class="font-bold text-[10px] sm:text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <div class="relative">
                        <input :type="showConfirmPass ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                               class="w-full px-4 sm:px-5 py-3 sm:py-3.5 bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all text-sm sm:text-base" />
                        
                        <button type="button" @click="showConfirmPass = !showConfirmPass" class="absolute right-3 sm:right-4 top-3 sm:top-4 text-gray-500 hover:text-gray-800 transition">
                            <svg x-show="!showConfirmPass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="showConfirmPass" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full inline-flex justify-center items-center px-6 py-3.5 bg-gray-900 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-gray-800 focus:bg-gray-800 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-400 transition ease-in-out duration-150">
                        Activate Account & Set Password
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-guest-layout>