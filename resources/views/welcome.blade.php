<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LMMS-CCS | Laboratory Monitoring System</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
</head>
<body class="bg-slate-900 text-slate-100 font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-indigo-500 selection:text-white">

    <!-- Navigation Header -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-lg text-white shadow-lg shadow-indigo-500/30">
                    CCS
                </div>
                <div>
                    <span class="text-lg font-bold tracking-tight text-white block leading-none">LMMS</span>
                    <span class="text-xs text-slate-400 block font-medium">EARIST College of Computing Studies</span>
                </div>
            </div>

            <nav class="flex items-center gap-3 sm:gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-semibold px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 transition shadow-md shadow-indigo-600/20">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition px-3 py-2">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="text-sm font-semibold px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 transition shadow-md shadow-indigo-600/20">
                                Register
                            </a>
                        @endif
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    <!-- Main Hero & Content -->
    <main class="flex-1 flex flex-col justify-center">
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold mb-6">
                <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                System Operational
            </div>
            
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-tight max-w-4xl mx-auto">
                Enhanced Computer Laboratory <span class="text-indigo-400">Monitoring System</span>
            </h1>
            
            <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto font-normal">
                Streamlining computer terminal tracking, laboratory scheduling, and issue reporting for students and faculty.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-white transition shadow-lg shadow-indigo-600/30 text-center">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-white transition shadow-lg shadow-indigo-600/30 text-center">
                            Access Portal
                        </a>
                    @endauth
                @endif
            </div>
        </section>

        <!-- Feature Overview -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 w-full">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700/50 hover:border-slate-600 transition">
                    <div class="w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold mb-4">
                        01
                    </div>
                    <h2 class="text-lg font-semibold text-white mb-2">Station Tracking</h2>
                    <p class="text-sm text-slate-400">Monitor active terminal availability and user session allocations across all computer laboratories in real time.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700/50 hover:border-slate-600 transition">
                    <div class="w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold mb-4">
                        02
                    </div>
                    <h2 class="text-lg font-semibold text-white mb-2">Schedule Management</h2>
                    <p class="text-sm text-slate-400">View upcoming class reservations, open-lab hours, and instructor assignments with ease.</p>
                </div>

                <div class="p-6 rounded-2xl bg-slate-800/50 border border-slate-700/50 hover:border-slate-600 transition">
                    <div class="w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold mb-4">
                        03
                    </div>
                    <h2 class="text-lg font-semibold text-white mb-2">Incident Reporting</h2>
                    <p class="text-sm text-slate-400">Log hardware glitches or software errors directly to laboratory administrators for quick maintenance response.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-900/50 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500">
            <p>&copy; {{ date('Y') }} LMMS-CCS. College of Computing Studies — EARIST. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>