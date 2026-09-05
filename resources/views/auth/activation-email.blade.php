<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-gray-100">
        <div class="w-full max-w-sm sm:max-w-md bg-white rounded-2xl sm:rounded-xl shadow-lg sm:shadow-md p-6 sm:p-8">
            
            <div class="mb-5 sm:mb-6 text-center sm:text-left">
                <h2 class="text-3xl sm:text-4xl font-black text-gray-900 mb-2">Account Activation</h2>
                <p class="text-gray-600 text-sm sm:text-md">Step 1: Initialize your institutional profile gateway</p>
            </div>

            <p class="text-xs sm:text-sm font-medium text-gray-500 mb-6 text-center sm:text-left">
                Welcome, <span class="font-bold text-gray-800">{{ $user->first_name }}</span>! To secure your account, please confirm or update your active email address where your validation token will be sent.
            </p>

            <form method="POST" action="{{ route('activation.send_otp') }}" class="space-y-5">
                @csrf
                
                <div>
                    <x-input-label for="email" value="Active Email Address" class="font-bold text-[10px] sm:text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autofocus
                           class="w-full px-4 sm:px-5 py-3 sm:py-3.5 bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all text-sm sm:text-base" 
                           placeholder="your-email@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 sm:py-4 bg-[#383838] text-white font-bold rounded-xl hover:bg-black transition-all shadow-md uppercase tracking-wider text-xs sm:text-sm">
                        Send Activation Code
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>