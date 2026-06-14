<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-gray-100">
        <div class="w-full max-w-md bg-white rounded-xl shadow-md p-8">
            
            <div class="mb-6 text-left">
                <h2 class="text-4xl font-black text-gray-900 mb-2">Reset Password</h2>
                <p class="text-gray-600 text-md">Step 1: Request a security code</p>
            </div>

            <p class="text-sm font-medium text-gray-500 mb-6">
                Forgot your password? No problem. Enter your email address below and we will send you a 6-digit verification code to reset it.
            </p>

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                
                <div>
                    <x-input-label Gaza for="email" value="Email Address" class="font-bold text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all" 
                           placeholder="username@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-4 bg-[#383838] text-white font-bold rounded-xl hover:bg-black transition-all shadow-md uppercase tracking-wider text-sm">
                        Send Verification Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>