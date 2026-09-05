<x-guest-layout>
    <div class="min-h-screen flex flex-col lg:flex-row bg-white text-gray-900">
        <!-- Sidebar Image/Branding -->
        <div class="hidden lg:flex lg:w-2/5 bg-[#383838] items-center justify-center p-12 relative">
            <div class="text-white text-center fixed w-[40%] px-12">
                <img src="{{ asset('img/ccs_logo.png') }}" class="h-[250px] xl:h-[350px] mx-auto mb-8 filter drop-shadow-lg">
                <h1 class="text-2xl xl:text-3xl font-bold uppercase tracking-tight mb-4">Learning and Monitoring Management System</h1>
                <div class="mt-8 w-48 xl:w-56 h-1 bg-gray-100 mx-auto rounded-full"></div>
                <p class="font-medium tracking-widest uppercase text-base xl:text-lg mt-8">College of Computing Studies</p>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="flex-1 flex items-center justify-center p-4 sm:p-8 bg-gray-100 overflow-y-auto">
            <div class="w-full max-w-2xl bg-white sm:bg-transparent p-6 sm:p-0 rounded-2xl sm:rounded-none shadow-sm sm:shadow-none">
                <div class="mb-6 sm:mb-8 text-center sm:text-left">
                    <img src="{{ asset('img/ccs_logo.png') }}" class="h-20 mx-auto mb-4 lg:hidden block">
                    <h2 class="text-3xl sm:text-4xl font-black text-gray-900 mb-2">Create Account</h2>
                    <p class="text-gray-600 text-sm sm:text-md">Enter your full details to register</p>
                </div>

                <form method="POST" action="{{ route('register') }}" x-data="{ role: 'student', showPass: false, showConfirm: false }" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <input type="text" name="first_name" placeholder="First Name" required
                            class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 outline-none transition-all uppercase text-sm sm:text-base">
                        <input type="text" name="middle_name" placeholder="Middle Name"
                            class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 outline-none transition-all uppercase text-sm sm:text-base">
                        <input type="text" name="last_name" placeholder="Surname" required
                            class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 outline-none transition-all uppercase text-sm sm:text-base">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <input type="text" name="school_id" placeholder="Student / Faculty ID" required
                            class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 outline-none text-sm sm:text-base">
                        <select name="role" x-model="role"
                            class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl outline-none cursor-pointer text-sm sm:text-base">
                            <option value="student">Student</option>
                            <option value="professor">Faculty</option>
                        </select>
                    </div>

                    <template x-if="role === 'student'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <select name="program" required
                                class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl outline-none cursor-pointer text-sm sm:text-base">
                                <option value="" disabled selected>Select Program</option>
                                <option value="BSCS">BSCS (Computer Science)</option>
                                <option value="BSIT">BSIT (Information Technology)</option>
                            </select>

                            <select name="year_level" required
                                class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl outline-none cursor-pointer text-sm sm:text-base">
                                <option value="" disabled selected>Select Year Level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                    </template>
                    
                    <input type="email" name="email" placeholder="Email Address" required
                        class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 outline-none text-sm sm:text-base">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" name="password" required
                                class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 outline-none text-sm sm:text-base"
                                placeholder="Password">
                            <button type="button" @click="showPass = !showPass" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <!-- Icons omitted for brevity, keep your original SVG paths here -->
                            </button>
                        </div>

                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required
                                class="w-full px-4 sm:px-5 py-3.5 bg-gray-50 sm:bg-white border border-gray-300 sm:border-gray-400 rounded-xl focus:ring-2 focus:ring-gray-400 outline-none text-sm sm:text-base"
                                placeholder="Confirm Password">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <!-- Icons omitted for brevity, keep your original SVG paths here -->
                            </button>
                        </div>
                    </div>

                    <div class="pt-4 space-y-4">
                        <button type="submit"
                            class="w-full bg-[#383838] text-white font-bold py-3.5 sm:py-4 rounded-xl hover:bg-black transition-all shadow-md active:scale-[0.99] uppercase text-sm">
                            Register Account
                        </button>
                        <p class="text-center text-gray-600 text-sm">
                            Already registered? <a href="{{ route('login') }}" class="font-bold text-[#383838] hover:underline">Sign in instead</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>