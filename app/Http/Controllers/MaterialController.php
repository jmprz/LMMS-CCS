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
}