<?php

namespace App\Http\Controllers;

use App\Models\LabSession;
use App\Models\SubjectGrade;
use App\Services\OfficialOutcomeService;
use Illuminate\Http\Request;

class OfficialOutcomeController extends Controller
{
    public function index(Request $request, int $classId, OfficialOutcomeService $service)
    {
        $class = LabSession::findOrFail($classId);
        $service->authorize($request->user(), $class);
        $batch = $request->query('preview') ? $service->batch($request->user(), $class, $request->query('preview')) : null;
        $grades = SubjectGrade::with('student')->where('lab_session_id', $classId)->whereRaw("LOWER(TRIM(grading_period)) = 'final'")->get();
        $history = \Illuminate\Support\Facades\DB::table('subject_grade_revisions')->whereIn('subject_grade_id', $grades->pluck('id'))->orderByDesc('id')->get()->groupBy('subject_grade_id');

        return view('outcomes.index', compact('class', 'batch', 'grades', 'history'));
    }

    public function preview(Request $request, int $classId, OfficialOutcomeService $service)
    {
        $class = LabSession::findOrFail($classId);
        $service->authorize($request->user(), $class);
        $data = $request->validate(['source' => 'required|string|max:255', 'csv' => 'nullable|file|max:1024',
            'student_identifier' => 'required_without:csv|string|max:255', 'final_outcome' => 'required_without:csv|string|max:32']);
        if ($request->hasFile('csv')) {
            $csv = file_get_contents($request->file('csv')->getRealPath());
            $filename = $request->file('csv')->getClientOriginalName();
        } else {
            $stream = fopen('php://temp', 'w+');
            fputcsv($stream, ['student_identifier', 'final_outcome'], escape: '');
            fputcsv($stream, [$data['student_identifier'], $data['final_outcome']], escape: '');
            rewind($stream);
            $csv = stream_get_contents($stream);
            fclose($stream);
            $filename = 'manual-correction.csv';
        }
        $batch = $service->preview($request->user(), $class, $csv, trim($data['source']), $filename);

        return redirect()->route('outcomes.index', ['classId' => $classId, 'preview' => $batch->id]);
    }

    public function commit(Request $request, int $classId, string $importId, OfficialOutcomeService $service)
    {
        $count = $service->commit($request->user(), LabSession::findOrFail($classId), $importId);

        return redirect()->route('outcomes.index', ['classId' => $classId])->with('status', $count.' official outcomes recorded.');
    }
}
