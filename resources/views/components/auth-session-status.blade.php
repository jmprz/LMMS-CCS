@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 p-3 rounded-lg']) }}>
        {{ $status }}
    </div>
@endif