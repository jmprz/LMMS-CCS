<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50/50 p-6 min-h-screen select-none">
    <div class="max-w-3xl mx-auto">
        
        <div class="mb-8 border-b border-slate-100 pb-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Laboratory Engine Search</p>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">
                Showing results for <span class="text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg font-medium text-lg ml-1">"{{ $query }}"</span>
            </h2>
        </div>

        <div class="space-y-3">
            @php $hasResults = false; @endphp

            @foreach($results as $result)
                @if(isset($result['FirstURL']))
                    @php 
                        $hasResults = true; 
                        // Automatically extract cleanly formatted hostnames (e.g., "stackoverflow.com")
                        $host = parse_url($result['FirstURL'], PHP_URL_HOST) ?? 'External Link';
                        $cleanHost = str_replace('www.', '', $host);
                    @endphp
                    
                    <div class="group relative p-5 bg-white rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-slate-300 hover:-translate-y-[1px] transition-all duration-200">
                        <div class="flex items-start gap-4">
                            
                            <div class="flex-shrink-0 w-10 h-10 bg-slate-50 group-hover:bg-indigo-50 border border-slate-100 group-hover:border-indigo-100 rounded-xl flex items-center justify-center transition-colors">
                                <span class="text-xs font-bold text-slate-500 group-hover:text-indigo-600 uppercase">
                                    {{ substr($cleanHost, 0, 2) }}
                                </span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-1 text-xs font-medium text-slate-400">
                                    <span class="text-slate-600 group-hover:text-indigo-600 font-semibold transition-colors">{{ $cleanHost }}</span>
                                    <span>&bull;</span>
                                    <span class="truncate max-w-[250px]">Reference Manual</span>
                                </div>

                                <a href="#" 
                                   onclick="window.parent.postMessage({ type: 'iframe-navigate', url: '{{ $result['FirstURL'] }}' }, '*'); return false;" 
                                   class="text-[17px] font-semibold text-slate-900 group-hover:text-indigo-600 tracking-tight leading-snug block mb-1.5 transition-colors">
                                    {{ $result['Text'] }}
                                </a>
                                
                                <span class="text-slate-400 text-xs block truncate tracking-wide group-hover:text-slate-500 transition-colors">
                                    {{ $result['FirstURL'] }}
                                </span>
                            </div>

                            <div class="text-slate-300 group-hover:text-indigo-500 self-center transition-colors pl-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75" />
                                </svg>
                            </div>

                        </div>
                    </div>
                @endif
            @endforeach

            @if(!$hasResults)
                <div class="bg-white text-center py-16 px-6 rounded-2xl border border-slate-200/80 shadow-sm max-w-xl mx-auto mt-6">
                    <div class="w-14 h-14 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75l-2.489-2.489m0 0a3.375 3.375 0 10-4.773-4.773 3.375 3.375 0 004.774 4.774zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-slate-800 font-semibold text-base mb-1">No local or live indexes found</h3>
                    <p class="text-slate-400 text-sm max-w-xs mx-auto leading-relaxed">
                        We couldn't locate reference pages for that query. Try breaking down your coding assignment into modular keywords.
                    </p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>