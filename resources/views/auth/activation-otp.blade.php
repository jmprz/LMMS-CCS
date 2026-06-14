<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-gray-100">
        <div class="w-full max-w-md bg-white rounded-xl shadow-md p-8">
            
            <div class="mb-6 text-left">
                <h2 class="text-4xl font-black text-gray-900 mb-2">Verify Identity</h2>
                <p class="text-gray-600 text-md">Step 2: Authenticate mailbox parameters</p>
            </div>

            <p class="text-sm font-medium text-gray-500 mb-6">
                Enter the 6-digit security code sent to <strong class="text-gray-800">{{ $user->temp_email }}</strong> below to verify secure ownership records.
            </p>

            @if (session('status'))
                <div class="mb-4 text-xs font-bold text-green-700 bg-green-50 border border-green-300 p-3.5 rounded-xl">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('activation.verify_otp') }}" class="space-y-5">
                @csrf
                
                <div>
                    <x-input-label for="code" value="6-Digit Verification Code" class="font-bold text-xs uppercase tracking-wider text-gray-600 mb-1.5" />
                    <input type="text" id="code" name="code" required autofocus maxlength="6" autocomplete="off"
                           class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl text-center font-black text-2xl tracking-[0.5em] focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all" 
                           placeholder="000000" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-4 bg-[#383838] text-white font-bold rounded-xl hover:bg-black transition-all shadow-md uppercase tracking-wider text-sm">
                        SUBMIT
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>