<x-app-layout>
    <div class="flex h-[calc(100vh-80px)] overflow-hidden bg-[#f4f7f9]">
        
        <main class="flex-1 overflow-y-auto h-full">
            <div class="p-8 max-w-5xl space-y-6">
                
                <div class="mb-8">
                    <h1 class="text-2xl font-black text-gray-800 tracking-tight uppercase text-sm">Account Settings</h1>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">
                        Manage your college administrative credentials, core security protocols, and authentication settings.
                    </p>
                </div>

                <div class="transition duration-150">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <header class="border-b border-gray-100 pb-5 mb-6">
                        <h2 class="text-xl font-black text-gray-800 tracking-tight uppercase text-sm">
                            {{ __('Update Password') }}
                        </h2>
                        <p class="mt-1 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            {{ __('Ensure your account is using a long, random password to stay secure.') }}
                        </p>
                    </header>
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <header class="border-b border-gray-100 pb-5 mb-6">
                        <h2 class="text-xl font-black text-red-600 tracking-tight uppercase text-sm">
                            {{ __('Danger Zone') }}
                        </h2>
                        <p class="mt-1 text-xs font-bold text-gray-400 uppercase tracking-wider">
                            {{ __('Once your account is deleted, all of its resources and data will be permanently purged.') }}
                        </p>
                    </header>
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>

            </div>
        </main>
    </div>
</x-app-layout>