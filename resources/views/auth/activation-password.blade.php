<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-gray-100">
        <div class="w-full max-w-md bg-white rounded-xl shadow-md p-8">
            
            <div class="mb-6 text-left">
                <h2 class="text-4xl font-black text-gray-900 mb-2">Secure Profile</h2>
                <p class="text-gray-600 text-md">Final Step: Define secure access signatures</p>
            </div>

            <p class="text-sm font-medium text-gray-500 mb-4">
               Your identity has been successfully verified. Please update your temporary default password to a new, secure password to complete your registration and activate your account.

            <div class="mb-5 bg-gray-50 border border-gray-300 rounded-xl p-4 text-xs font-semibold text-gray-600 space-y-2">
                <span class="block text-gray-900 uppercase text-[10px] tracking-wider font-black mb-0.5">Password Complexity Guidelines:</span>
                <div class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-[#383838]"></div> Minimum of 8 total token indices</div>
                <div class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-[#383838]"></div> At least one uppercase character (A-Z)</div>
                <div class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-[#383838]"></div> At least one digital value representation (0-9)</div>
            </div>

            <form method="POST" action="{{ route('activation.update_password') }}" x-data="{ showPass: false, showConfirmPass: false }" class="space-y-4">
                @csrf
                
                <div>
                    <x-input-label for="password" value="New Secure Password" class="font-bold text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password"
                               class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all" />
                        
                        <button type="button" @click="showPass = !showPass" class="absolute right-4 top-4 text-gray-500 hover:text-gray-800 transition">
                            <template x-if="!showPass">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644a11.042 11.042 0 0111.963-7.078a10.054 10.054 0 0111.963 7.078a1.012 1.012 0 010 .644a11.043 11.043 0 01-11.963 7.078a10.054 10.054 0 01-11.963-7.078z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </template>
                            <template x-if="showPass">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </template>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirm Password" class="font-bold text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <div class="relative">
                        <input :type="showConfirmPass ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                               class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all" />
                        
                        <button type="button" @click="showConfirmPass = !showConfirmPass" class="absolute right-4 top-4 text-gray-500 hover:text-gray-800 transition">
                            <template x-if="!showConfirmPass">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644a11.042 11.042 0 0111.963-7.078a10.054 10.054 0 0111.963 7.078a1.012 1.012 0 010 .644a11.043 11.043 0 01-11.963 7.078a10.054 10.054 0 01-11.963-7.078z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </template>
                            <template x-if="showConfirmPass">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </template>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-4 bg-[#383838] text-white font-bold rounded-xl hover:bg-black transition-all shadow-md uppercase tracking-wider text-sm">
                       CONFIRM PASSWORD
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>