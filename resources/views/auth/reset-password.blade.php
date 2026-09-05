<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-gray-100">
        <div class="w-full max-w-sm sm:max-w-md bg-white rounded-2xl sm:rounded-xl shadow-lg sm:shadow-md p-6 sm:p-8">
            
            <div class="mb-5 sm:mb-6 text-center sm:text-left">
                <h2 class="text-3xl sm:text-4xl font-black text-gray-900 mb-2">Reset Password</h2>
                <p class="text-gray-600 text-sm sm:text-md">Please enter your new password below[cite: 34].</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">[cite: 34]

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email')" class="font-bold text-[10px] sm:text-xs uppercase tracking-wider text-gray-600 mb-1.5" />[cite: 34]
                    <x-text-input id="email" class="w-full px-4 sm:px-5 py-3 sm:py-3.5 bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all text-sm sm:text-base" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />[cite: 34]
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />[cite: 34]
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Password')" class="font-bold text-[10px] sm:text-xs uppercase tracking-wider text-gray-600 mb-1.5" />[cite: 34]
                    <x-text-input id="password" class="w-full px-4 sm:px-5 py-3 sm:py-3.5 bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all text-sm sm:text-base" type="password" name="password" required autocomplete="new-password" />[cite: 34]
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />[cite: 34]
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="font-bold text-[10px] sm:text-xs uppercase tracking-wider text-gray-600 mb-1.5" />[cite: 34]
                    <x-text-input id="password_confirmation" class="w-full px-4 sm:px-5 py-3 sm:py-3.5 bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all text-sm sm:text-base"
                                        type="password"
                                        name="password_confirmation" required autocomplete="new-password" />[cite: 34]
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />[cite: 34]
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3.5 sm:py-4 bg-[#383838] text-white font-bold rounded-xl hover:bg-black transition-all shadow-md uppercase tracking-wider text-xs sm:text-sm">
                        {{ __('Reset Password') }}[cite: 34]
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>