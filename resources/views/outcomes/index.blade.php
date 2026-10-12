<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Official final outcomes — {{ $class->class_code }}</h2></x-slot>
    <div class="max-w-5xl mx-auto p-6 space-y-6">
        <p>Enter the official professor/institutional result. Outcomes are stored separately from predictors; no risk status is assigned.</p>
        @if(session('status'))<p class="text-green-700">{{ session('status') }}</p>@endif
        @foreach($errors->all() as $error)<p class="text-red-700">{{ $error }}</p>@endforeach
        <form method="POST" enctype="multipart/form-data" action="{{ route('outcomes.preview', $class->id) }}" class="bg-white p-4 space-y-3">
            @csrf
            <h3 class="font-semibold">Import a class CSV</h3>
            <p>Exact header: <code>student_identifier,final_outcome</code>. Use institutional school IDs. Preserve leading zeros. Outcomes must be numeric with up to two decimal places; no passing scale is assumed.</p>
            <label class="block">Official source/reference <input class="block w-full" name="source" required maxlength="255"></label>
            <input type="file" name="csv" accept=".csv,text/csv" required>
            <button class="px-4 py-2 bg-gray-800 text-white">Preview import</button>
        </form>
        <form method="POST" action="{{ route('outcomes.preview', $class->id) }}" class="bg-white p-4 space-y-3">
            @csrf
            <h3 class="font-semibold">Record or correct one outcome</h3>
            <label class="block">School ID <input name="student_identifier" required maxlength="255"></label>
            <label class="block">Official outcome <input name="final_outcome" required maxlength="32"></label>
            <label class="block">Source and correction reason <input class="w-full" name="source" required maxlength="255"></label>
            <button class="px-4 py-2 bg-gray-800 text-white">Preview correction</button>
        </form>
        @if($batch)
            @php($rows = json_decode($batch->preview, true))
            <section class="bg-white p-4 space-y-3">
                <h3 class="font-semibold">Preview — {{ $batch->filename }}</h3><p>Source: {{ $batch->source }}. No outcomes are changed until commit.</p>
                <table class="w-full"><thead><tr><th>CSV row</th><th>School ID</th><th>Previous</th><th>Official outcome</th><th>Validation</th></tr></thead><tbody>
                @foreach($rows as $row)<tr><td>{{ $row['line'] }}</td><td>{{ $row['student_identifier'] }}</td><td>{{ $row['previous_outcome'] ?? 'None' }}</td><td>{{ $row['final_outcome'] }}</td><td>{{ implode(' ', $row['errors']) ?: 'Valid' }}</td></tr>@endforeach
                </tbody></table>
                @if(!$batch->committed_at && !collect($rows)->contains(fn($row) => count($row['errors']) > 0))
                <form method="POST" action="{{ route('outcomes.commit', [$class->id, $batch->id]) }}">@csrf<button class="px-4 py-2 bg-gray-800 text-white">Commit these official outcomes</button></form>
                @else<p>{{ $batch->committed_at ? 'Already committed.' : 'Import blocked. Correct invalid/unmatched/duplicate rows and upload again.' }}</p>@endif
            </section>
        @endif
        <section class="bg-white p-4"><h3 class="font-semibold">Current official outcomes and revision history</h3>
            @forelse($grades as $grade)
            <details class="py-2"><summary>{{ $grade->student?->school_id }} — {{ $grade->grade }} ({{ $grade->recorded_at }}) — {{ $grade->source }}</summary>
                @foreach($history[$grade->id] ?? [] as $revision)<p>{{ $revision->recorded_at }} — {{ $revision->grade }} — {{ $revision->source ?? 'Legacy source not recorded' }}</p>@endforeach
            </details>
            @empty<p>No official final outcomes recorded.</p>@endforelse
        </section>
    </div>
</x-app-layout>
