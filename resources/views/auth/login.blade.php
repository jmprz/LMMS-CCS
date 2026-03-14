<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <div class="min-h-screen flex flex-col md:flex-row bg-white">
        <div class="hidden md:flex md:w-2/5 bg-[#383838] items-center justify-center p-12 relative">
            <div class="text-white text-center">
                <img src="{{ ('img/ccs_logo.png') }}" class="h-[350px] mx-auto mb-8 filter drop-shadow-lg">
                <h1 class="text-3xl font-bold uppercase tracking-tight mb-4">Learning Management and Monitoring System</h1>
                <div class="mt-8 w-56 h-1 bg-gray-100 mx-auto rounded-full"></div>
                <p class="font-medium tracking-widest uppercase text-lg mt-8">College of Computing Studies </p>
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center p-8 bg-gray-100">
            <div class="w-full max-w-md"> <div class="mb-4 text-left">
                    <h2 class="text-4xl font-black text-gray-900 mb-2">Welcome Back</h2>
                    <p class="text-gray-600 text-md">Log in your account to continue</p>
                </div>

                <form action="{{ route('login') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <input type="email" name="email" 
                               class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all" 
                               placeholder="Email" required>
                    </div>
                    <div>
                        <input type="password" name="password" 
                               class="w-full px-5 py-3.5 bg-white border border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 focus:border-gray-400 outline-none transition-all" 
                               placeholder="Password" required>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-[#383838] text-white font-bold py-3.5 rounded-xl hover:bg-black transition-all duration-300 shadow-lg shadow-gray-200 active:scale-[0.99]">
                       LOGIN
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>