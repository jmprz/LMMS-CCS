<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function store(Request $request, $labSessionId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:pdf,pptx,youtube',
            'content_file' => 'required_if:type,pdf,pptx|file|mimes:pdf,ppt,pptx|max:20480',
            'content_url' => 'required_if:type,youtube|nullable|url',
        ]);

        $contentPath = '';

        if ($request->type === 'youtube') {
            $contentPath = $request->content_url;
        } else {
            $file = $request->file('content_file');
            // Create a unique filename
            $filename = time() . '_' . $file->getClientOriginalName();

            // Move it directly to public/materials
            $file->move(public_path('materials'), $filename);

            // Save only the relative path for the database
            $contentPath = 'materials/' . $filename;
        }

        $material = \App\Models\Material::create([
            'lab_session_id' => $labSessionId,
            'title' => $request->title,
            'type' => $request->type,
            'content' => $contentPath,
        ]);

        $this->logProfessorActivity(
            $labSessionId,
            'Posted a learning material: "' . $material->title . '"'
        );

        // =========================================================================
        // LIVE PRODUCTION EMAIL ALERTS FOR MATERIALS (SYNCHRONOUS TRANSMISSION)
        // =========================================================================
        try {
            // Find the lab session info to grab the actual class name safely
            $labSession = \App\Models\LabSession::find($labSessionId);

            // Query student users who have joined this active LabSession ID via pivot setup
            $students = \App\Models\User::whereHas('joinedClasses', function ($query) use ($labSessionId) {
                $query->where('lab_sessions.id', $labSessionId);
            })->get();

            // Fire the direct inline mailer distribution loop
            foreach ($students as $student) {
                \Illuminate\Support\Facades\Mail::send('emails.new_material_notification', [
                    'student' => $student,
                    'material' => $material,
                    'labSession' => $labSession
                ], function ($message) use ($student, $material) {
                    $message->to($student->email)
                        ->subject('LMMS - New Learning Material Posted: ' . $material->title);
                });
            }
        } catch (\Exception $e) {
            // Log any mail server connection anomalies securely without halting database records
            \Log::error('Material Email Distribution Encountered an Error: ' . $e->getMessage());
        }
        // =========================================================================

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Material posted successfully!'
            ]);
        }

        return back()->with('success', 'Material posted successfully!');
    }

    public function logStart(Request $request, Material $material, \App\Services\MaterialEngagementService $engagement)
    {
        $data = $request->validate(['client_session_key' => 'required|uuid', 'external' => 'sometimes|boolean']);
        $log = $engagement->start($request->user(), $material, $data['client_session_key'], (bool) ($data['external'] ?? false));
        return response()->json(['session_id' => $log->id, 'measurement_mode' => $log->measurement_mode,
            'collection_version' => $log->collection_version, 'closed' => $log->closed_at !== null]);
    }

    public function logHeartbeat(Request $request, Material $material, \App\Services\MaterialEngagementService $engagement)
    {
        return $this->recordEngagement($request, $material, $engagement, false);
    }

    public function logEnd(Request $request, Material $material, \App\Services\MaterialEngagementService $engagement)
    {
        return $this->recordEngagement($request, $material, $engagement, true);
    }

    private function recordEngagement(Request $request, Material $material, \App\Services\MaterialEngagementService $engagement, bool $close)
    {
        $data = $request->validate(['session_id' => 'required|integer|min:1', 'sequence' => 'required|integer|min:1|max:2147483647',
            'active_seconds' => 'required|integer|min:0|max:2147483647']);
        $log = $engagement->record($request->user(), $material, (int) $data['session_id'], (int) $data['sequence'], (int) $data['active_seconds'], $close);
        return response()->json(['session_id' => $log->id, 'duration_seconds' => $log->duration_seconds, 'closed' => $log->closed_at !== null]);
    }
    /**
     * Update the specified learning material.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content_url' => 'required_if:type,youtube|nullable|url',
            'content_file' => 'nullable|file|mimes:pdf,ppt,pptx|max:20480',
        ]);

        $material = Material::findOrFail($id);
        $material->title = $request->title;

        if ($material->type === 'youtube') {
            if ($request->has('content_url')) {
                $material->content = $request->content_url;
            }
        } else {
            // Check if a brand new file replacement is uploaded
            if ($request->hasFile('content_file')) {
                // Delete old local asset path securely from public/materials
                $oldFilePath = public_path($material->content);
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    @unlink($oldFilePath);
                }

                $file = $request->file('content_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('materials'), $filename);

                $material->content = 'materials/' . $filename;
            }
        }

        $material->save();

        $this->logProfessorActivity(
            $material->lab_session_id,
            'Updated learning material: "' . $material->title . '"'
        );

        return response()->json([
            'success' => true,
            'message' => 'Material updated successfully!'
        ]);
    }

    /**
     * Remove the material resource and clean log entries.
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);

        // Delete physical file from application storage if it isn't a web URL link
        if ($material->type !== 'youtube') {
            $filePath = public_path($material->content);
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
        }

        // Wipe associated tracking histories from table to avoid broken record dependencies
        \DB::table('material_logs')->where('material_id', $id)->delete();

        $materialTitle = $material->title;
        $labSessionId = $material->lab_session_id;

        $material->delete();

        $this->logProfessorActivity(
            $labSessionId,
            'Deleted the learning material: "' . $materialTitle . '"'
        );

        return response()->json([
            'success' => true,
            'message' => 'Learning material dropped successfully!'
        ]);
    }

    /**
     * Fetch viewing history records utilizing your custom table.
     */
    public function getViewers($id)
    {
        // Join with your platform's users structural table to map names & identifiers
        $logs = \DB::table('material_logs')
            ->join('users', 'material_logs.user_id', '=', 'users.id')
            ->where('material_logs.material_id', $id)
            ->select(
                'material_logs.id',
                'users.name as student_name',
                'users.school_id as student_identifier', // Swapped out safely for generic user properties
                'material_logs.opened_at',
                'material_logs.duration_seconds',
                'material_logs.closed_at', 'material_logs.collection_version', 'material_logs.measurement_mode'
            )
            ->orderBy('material_logs.opened_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'student_name' => $log->student_name,
                    'student_info' => $log->student_identifier,
                    'viewed_at' => \Carbon\Carbon::parse($log->opened_at)->format('M d, Y h:i A'),
                    'seconds_spent' => $log->duration_seconds,
                    'is_closed' => $log->closed_at !== null,
                    'collection_version' => $log->collection_version,
                    'measurement_mode' => $log->measurement_mode,
                ];
            });

        return response()->json($logs);
    }

    private function logProfessorActivity($labSessionId, $content)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'lab_session_id' => $labSessionId,
            'log_type' => 'professor_activity',
            'content' => $content,
        ]);
    }


    public function updateTopics(Request $request, Material $material)
    {
        $session = \App\Models\LabSession::findOrFail(
            $material->lab_session_id
        );

        abort_unless(
            (int) $session->faculty_id === (int) auth()->id(),
            403
        );

        $validated = $request->validate([
            'learning_topic_ids' => ['nullable', 'array'],
            'learning_topic_ids.*' => [
                'integer',
                'distinct',
                'exists:learning_topics,id',
            ],
        ]);

        $topicMappings = collect(
            $validated['learning_topic_ids'] ?? []
        )->mapWithKeys(fn($id) => [
                $id => ['tagged_by' => auth()->id()],
            ])->all();

        $material->learningTopics()->sync($topicMappings);

        // Reload the saved topics from the database.
        $material->load('learningTopics');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Topics saved successfully.',
                'topics' => $material->learningTopics
                    ->map(fn($topic) => [
                        'id' => $topic->id,
                        'name' => $topic->name,
                    ])
                    ->values(),
            ]);
        }

        return redirect()
            ->route('professor.classroom.show', [
                'id' => $session->id,
                'tab' => 'materials',
            ])
            ->with('success', 'Topics saved successfully.');
    }


}
