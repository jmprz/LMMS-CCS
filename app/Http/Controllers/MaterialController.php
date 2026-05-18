<?php

namespace App\Http\Controllers;

use App\Models\Material;
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

    \App\Models\Material::create([
        'lab_session_id' => $labSessionId,
        'title' => $request->title,
        'type' => $request->type,
        'content' => $contentPath,
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Material posted successfully!'
        ]);
    }

    return back()->with('success', 'Material posted successfully!');
}

public function logStart(\App\Models\Material $material)
{
    // 1. Keep this: Tracks precise structural analytics session windows
    \DB::table('material_logs')->insert([
        'user_id'     => auth()->id(),
        'material_id' => $material->id,
        'opened_at'   => now(),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    return response()->json(['message' => 'Log started']);
}

public function logEnd(Request $request, \App\Models\Material $material)
{
    $userId = auth()->id();
    $duration = $request->duration ?? 0;

    // 1. Core update to structural material session logs
    \DB::table('material_logs')
        ->where('user_id', $userId)
        ->where('material_id', $material->id)
        ->whereNull('closed_at')
        ->latest()
        ->update([
            'closed_at' => now(),
            'duration_seconds' => $duration,
            'updated_at' => now(),
        ]);

    // 2. ALTERNATIVE: Write to timeline modal ONLY when they finish reading
    \App\Models\ActivityLog::create([
        'user_id'          => $userId,
        'log_type'         => 'material',
        'content'          => "Student finished reading material: \"" . $material->title . "\"",
        'lab_session_id'   => $material->lab_session_id, // Match with your session foreign key column
        'duration_seconds' => $duration,
    ]);

    return response()->json(['message' => 'Log ended', 'duration' => $duration]);
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
        
        $material->delete();

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
                'material_logs.duration_seconds'
            )
            ->orderBy('material_logs.opened_at', 'desc')
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'student_name' => $log->student_name,
                    'student_info' => $log->student_identifier,
                    'viewed_at' => \Carbon\Carbon::parse($log->opened_at)->format('M d, Y h:i A'),
                    'seconds_spent' => $log->duration_seconds ?? 0,
                ];
            });

        return response()->json($logs);
    }
}