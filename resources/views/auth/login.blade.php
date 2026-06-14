<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    
    <div class="min-h-screen flex flex-col md:flex-row bg-white">
        <div class="hidden md:flex md:w-2/5 bg-[#383838] items-center justify-center p-12 relative">
            <div class="text-white text-center">
               <img src="{{ asset('img/ccs_logo.png') }}" class="h-[350px] mx-auto mb-8 filter drop-shadow-lg">
                <h1 class="text-3xl font-bold uppercase tracking-tight mb-4">Learning and Monitoring Management System</h1>
                <div class="mt-8 w-56 h-1 bg-gray-100 mx-auto rounded-full"></div>
                <p class="font-medium tracking-widest uppercase text-lg mt-8">College of Computing Studies</p>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center p-8 bg-gray-100">
            <div class="w-full max-w-md"> 
                <div class="mb-4 text-left">
                    <h2 class="text-4xl font-black text-gray-900 mb-2">Welcome Back</h2>
                    <p class="text-gray-600 text-md">Log in your account to continue</p>
                </div>

                <form action="{{ route('login') }}" method="POST" class="space-y-4" x-data="{ showPass: false }">
                    @csrf
                    
                    <div>
                        <input type="text" name="school_id" value="{{ old('school_id') }}"
                               class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all" 
                               placeholder="School ID" required autofocus>
                        <x-input-error :messages="$errors->get('school_id')" class="mt-2" />
                    </div>

                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" name="password" 
                               class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all"
                               placeholder="Password" required>
                        
                        <button type="button" @click="showPass = !showPass" class="absolute right-4 top-4 text-gray-500">
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
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="pt-2 text-center">
                        <button type="submit" class="w-full py-4 bg-[#383838] text-white font-bold rounded-xl hover:bg-black transition-all shadow-md uppercase tracking-wider text-sm">
                            Sign In
                        </button>
                        <a href="{{ route('password.request') }}" class="inline-block mt-4 text-xs font-bold text-gray-500 hover:text-black uppercase tracking-widest transition-all outline-none">
                            Forgot your password?
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>