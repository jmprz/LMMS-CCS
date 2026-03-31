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

    return back()->with('success', 'Material posted successfully!');
}
}