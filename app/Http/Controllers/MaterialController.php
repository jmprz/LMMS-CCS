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
    // We create the record and get the ID so we can update it later if needed,
    // or just rely on the user/material/closed_at combo.
    \DB::table('material_logs')->insert([
        'user_id' => auth()->id(),
        'material_id' => $material->id,
        'opened_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['message' => 'Log started']);
}

public function logEnd(Request $request, \App\Models\Material $material)
{
    // Find the most recent log for this user/material that hasn't been closed yet
    \DB::table('material_logs')
        ->where('user_id', auth()->id())
        ->where('material_id', $material->id)
        ->whereNull('closed_at')
        ->latest()
        ->update([
            'closed_at' => now(),
            'duration_seconds' => $request->duration,
            'updated_at' => now(),
        ]);

    return response()->json(['message' => 'Log ended', 'duration' => $request->duration]);
}

}