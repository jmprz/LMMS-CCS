<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - {{ $query }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 min-h-full p-4 md:p-8 select-none antialiased">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <!-- Header & Query Details -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                        <i class="ri-shield-check-line text-emerald-500"></i>
                        Safe Search Active
                    </span>
                    <span class="text-xs text-slate-400">&bull;</span>
                    <span class="text-xs text-slate-400 font-medium">Lab Index v2.4</span>
                </div>
                <h2 class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <span>Results for</span>
                    <span class="text-[#383838] bg-slate-100 px-3 py-1 rounded-xl border border-slate-200 font-mono text-base">"{{ $query }}"</span>
                </h2>
            </div>
        </div>

        <!-- Results List Container -->
        <div class="space-y-3">
            @php $hasResults = false; @endphp

            @foreach($results as $result)
                @if(isset($result['FirstURL']))
                    @php 
                        $hasResults = true; 
                        $host = parse_url($result['FirstURL'], PHP_URL_HOST) ?? 'External Reference';
                        $cleanHost = str_replace('www.', '', $host);
                    @endphp
                    
                    <article class="group relative p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-slate-300 transition-all duration-200 hover:-translate-y-[1px]">
                        <div class="flex items-start gap-4">
                            
                            <!-- Host Badge Icon -->
                            <div class="shrink-0 w-11 h-11 bg-slate-50 group-hover:bg-[#383838] border border-slate-200/80 group-hover:border-[#383838] rounded-xl flex items-center justify-center transition-all duration-200 shadow-2xs">
                                <span class="text-xs font-black text-slate-600 group-hover:text-white uppercase transition-colors">
                                    {{ substr($cleanHost, 0, 2) }}
                                </span>
                            </div>

                            <!-- Content Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5 text-xs font-semibold">
                                    <span class="text-slate-700 group-hover:text-blue-600 transition-colors flex items-center gap-1">
                                        <i class="ri-global-line text-slate-400"></i>
                                        {{ $cleanHost }}
                                    </span>
                                    <span class="text-slate-300">&bull;</span>
                                </div>

                                <a href="#" 
                                   onclick="window.parent.postMessage({ type: 'iframe-navigate', url: '{{ $result['FirstURL'] }}' }, '*'); return false;" 
                                   class="text-base font-bold text-slate-900 group-hover:text-blue-600 tracking-tight block mb-1.5 transition-colors leading-snug">
                                    {{ $result['Text'] }}
                                </a>
                                
                                <p class="text-xs text-slate-400 font-mono truncate tracking-tight">
                                    {{ $result['FirstURL'] }}
                                </p>
                            </div>

                            <!-- Action Icon -->
                            <div class="shrink-0 self-center pl-2">
                                <button onclick="window.parent.postMessage({ type: 'iframe-navigate', url: '{{ $result['FirstURL'] }}' }, '*'); return false;" 
                                        class="w-9 h-9 rounded-xl bg-slate-50 group-hover:bg-blue-50 text-slate-400 group-hover:text-blue-600 border border-slate-100 group-hover:border-blue-100 flex items-center justify-center transition-all">
                                    <i class="ri-arrow-right-up-line text-lg group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                </button>
                            </div>

                        </div>
                    </article>
                @endif
            @endforeach

            <!-- Empty / No Results State -->
            @if(!$hasResults)
                <div class="bg-white text-center py-16 px-6 rounded-2xl border border-slate-200/80 shadow-xs max-w-lg mx-auto my-8">
                    <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-400 shadow-2xs">
                        <i class="ri-search-2-line text-2xl"></i>
                    </div>
                    <h3 class="text-slate-900 font-bold text-base mb-1">No matching resources found</h3>
                    <p class="text-slate-500 text-xs leading-relaxed max-w-xs mx-auto mb-6">
                        We couldn't index reference pages for <span class="font-semibold text-slate-700">"{{ $query }}"</span>. Try breaking down your query into core terminology.
                    </p>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                        <i class="ri-lightbulb-line text-amber-500"></i>
                        Tip: Search for specific function names or library frameworks.
                    </div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>