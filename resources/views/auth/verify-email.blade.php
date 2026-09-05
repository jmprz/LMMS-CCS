<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-gray-100">
        <div class="w-full max-w-sm sm:max-w-md bg-white rounded-2xl sm:rounded-xl shadow-lg sm:shadow-md p-6 sm:p-8">
            
            <div class="mb-5 sm:mb-6 text-center sm:text-left">
                <h2 class="text-3xl sm:text-4xl font-black text-gray-900 mb-2">Verify Email</h2>
                <p class="text-gray-600 text-sm sm:text-md">Confirm your account email address.</p>
            </div>

            <div class="mb-5 text-xs sm:text-sm font-medium text-gray-500 text-center sm:text-left">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}[cite: 35]
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-4 text-xs font-bold text-green-700 bg-green-50 border border-green-300 p-3 sm:p-3.5 rounded-xl text-center sm:text-left">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}[cite: 35]
                </div>
            @endif

            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto py-3.5 px-4 bg-[#383838] text-white font-bold rounded-xl hover:bg-black transition-all shadow-md uppercase tracking-wider text-xs sm:text-sm">
                        {{ __('Resend Verification Email') }}[cite: 35]
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto text-center">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-gray-500 hover:text-black uppercase tracking-widest transition-all outline-none">
                        {{ __('Log Out') }}[cite: 35]
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>