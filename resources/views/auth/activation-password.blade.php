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
                
                <div>
                    <x-input-label for="password" value="New Secure Password" class="font-bold text-[10px] sm:text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password"
                               class="w-full px-4 sm:px-5 py-3 sm:py-3.5 bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all text-sm sm:text-base" />
                        
                        <button type="button" @click="showPass = !showPass" class="absolute right-3 sm:right-4 top-3 sm:top-4 text-gray-500 hover:text-gray-800 transition">
                            <template x-if="!showPass">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 Here is the complete file. It includes the responsive wrapper, the header you requested, and a standard form structure with inputs styled to match the mobile/desktop responsive design. You can easily adjust the `action` route, `name` attributes, and text to match either your Reset or Activation flow.

```html
<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-gray-100">
        <!-- Added max-w-sm for mobile, scales to max-w-md on tablet/desktop. Reduced padding on mobile. -->
        <div class="w-full max-w-sm sm:max-w-md bg-white rounded-2xl sm:rounded-xl shadow-lg sm:shadow-md p-6 sm:p-8">
            
            <div class="mb-5 sm:mb-6 text-center sm:text-left">
                <!-- Scaled down font for mobile -->
                <h2 class="text-3xl sm:text-4xl font-black text-gray-900 mb-2">Reset Password</h2>
                <p class="text-gray-600 text-sm sm:text-md">Enter your new password below to secure your account.</p>
            </div>
            
            <!-- Adjust the form action route based on whether this is for Reset or Activation -->
            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <!-- Optional Token Field (Often needed for resets) -->
                <input type="hidden" name="token" value="{{ $request->route('token') ?? '' }}">

                <!-- Email Field -->
                <div class="mb-4">
                    <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
                    <input id="email" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm sm:text-base px-3 py-2 border" 
                           type="email" 
                           name="email" 
                           value="{{ old('email', $request->email ?? '') }}" 
                           required 
                           autofocus />
                    <!-- Error Message Placeholder -->
                    @error('email')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                    <label for="password" class="block font-medium text-sm text-gray-700">New Password</label>
                    <input id="password" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm sm:text-base px-3 py-2 border" 
                           type="password" 
                           name="password" 
                           required />
                    @error('password')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block font-medium text-sm text-gray-700">Confirm Password</label>
                    <input id="password_confirmation" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm sm:text-base px-3 py-2 border" 
                           type="password" 
                           name="password_confirmation" 
                           required />
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end">
                    <button type="submit" 
                            class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-gray-900 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>