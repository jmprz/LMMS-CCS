<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style> body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-900 text-white min-h-full flex items-center justify-center p-6 antialiased select-none">
    <div class="max-w-md w-full bg-slate-800/80 backdrop-blur-xl rounded-3xl border border-slate-700/60 p-8 shadow-2xl text-center relative overflow-hidden">
        
        <!-- Red Accent Ambient Light -->
        <div class="absolute -top-12 -left-12 w-32 h-32 bg-red-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Warning Icon Badge -->
        <div class="w-16 h-16 bg-red-500/10 border border-red-500/20 rounded-2xl flex items-center justify-center mx-auto mb-5 text-red-400 shadow-inner">
            <i class="ri-shield-keyhole-line text-3xl"></i>
        </div>

        <span class="inline-block px-3 py-1 bg-red-500/10 text-red-400 border border-red-500/20 rounded-full text-[10px] font-bold uppercase tracking-widest mb-3">
            Lab Security Firewall
        </span>

        <h1 class="text-2xl font-black text-white tracking-tight mb-2">Access Restricted</h1>
        
        <p class="text-xs text-slate-400 leading-relaxed mb-6">
            {{ $message ?? 'This domain or URL pattern is restricted during active laboratory sessions.' }}
        </p>

        <!-- Warning Box -->
        <div class="bg-slate-900/80 rounded-2xl p-4 border border-slate-700/50 mb-6 text-left">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-400 mb-1">
                <i class="ri-error-warning-line"></i>
                Session Audit Notice
            </div>
            <p class="text-[11px] text-slate-400 leading-normal">
                All external request attempts outside approved educational resources are logged to the instructor timeline.
            </p>
        </div>

        <!-- Whitelisted Suggestions -->
        <div class="pt-4 border-t border-slate-700/50">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-3">Approved Educational Domains</p>
            <div class="flex flex-wrap gap-2 justify-center">
                <span class="px-3 py-1 bg-slate-700/50 border border-slate-600/50 rounded-lg text-xs font-medium text-slate-300">Stack Overflow</span>
                <span class="px-3 py-1 bg-slate-700/50 border border-slate-600/50 rounded-lg text-xs font-medium text-slate-300">W3Schools</span>
                <span class="px-3 py-1 bg-slate-700/50 border border-slate-600/50 rounded-lg text-xs font-medium text-slate-300">PHP Docs</span>
                <span class="px-3 py-1 bg-slate-700/50 border border-slate-600/50 rounded-lg text-xs font-medium text-slate-300">TailwindCSS</span>
            </div>
        </div>

    </div>
</body>
</html>