<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use App\Models\LearningTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class LearningResourceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['pdf', 'youtube', 'url'])],
            'status' => ['nullable', Rule::in(['draft', 'approved'])],
            'topic' => ['nullable', 'integer', Rule::exists('learning_topics', 'id')],
        ]);

        $query = LearningResource::query()->with('learningTopics');

        if (filled($filters['q'] ?? null)) {
            $term = trim($filters['q']);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                    ->orWhere('description', 'like', '%' . $term . '%')
                    ->orWhere('subject_key', 'like', '%' . $term . '%');
            });
        }
        if (filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (filled($filters['topic'] ?? null)) {
            $query->whereHas('learningTopics', function ($q) use ($filters) {
                $q->where('learning_topics.id', $filters['topic']);
            });
        }

        return view('admin.learning-resources.index', [
            'resources' => $query->latest()->paginate(12)->withQueryString(),
            'topics' => LearningTopic::orderBy('name')->get(),
            'totalResources' => LearningResource::count(),
            'approvedCount' => LearningResource::where('status', 'approved')->count(),
            'draftCount' => LearningResource::where('status', 'draft')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedResource($request, false);
        $path = null;

        try {
            $content = $data['content_url'] ?? null;
            if ($data['type'] === 'pdf') {
                $path = $request->file('content_file')->store('learning-resources', 'public');
                $content = $path;
            }

            DB::transaction(function () use ($data, $content) {
                $resource = LearningResource::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'type' => $data['type'],
                    'content' => $content,
                    'subject_key' => strtoupper(trim($data['subject_key'])),
                    'program' => $data['program'] ?? null,
                    'status' => 'draft',
                    'uploaded_by' => auth()->id(),
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
                $resource->learningTopics()->sync($data['learning_topic_ids']);
            });
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return redirect()->route('admin.learning-resources.index')
            ->with('success', 'Supplementary resource saved as a draft.');
    }

    public function update(Request $request, LearningResource $learning_resource)
    {
        $data = $this->validatedResource($request, true);
        $newPath = null;
        $oldPath = $learning_resource->type === 'pdf' ? $learning_resource->content : null;
        $newContent = $learning_resource->content;

        try {
            if ($data['type'] === 'pdf') {
                if ($request->hasFile('content_file')) {
                    $newPath = $request->file('content_file')->store('learning-resources', 'public');
                    $newContent = $newPath;
                } elseif ($learning_resource->type !== 'pdf') {
                    throw ValidationException::withMessages([
                        'content_file' => 'Please upload a PDF when changing a link into a PDF.',
                    ]);
                }
            } else {
                $newContent = $data['content_url'];
            }

            DB::transaction(function () use ($learning_resource, $data, $newContent) {
                $learning_resource->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'type' => $data['type'],
                    'content' => $newContent,
                    'subject_key' => strtoupper(trim($data['subject_key'])),
                    'program' => $data['program'] ?? null,
                    // Every modification goes through approval again.
                    'status' => 'draft',
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
                $learning_resource->learningTopics()->sync($data['learning_topic_ids']);
            });
        } catch (Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $e;
        }

        // Delete only files managed by the new library, never legacy paths.
        if ($oldPath && $oldPath !== $newContent && str_starts_with($oldPath, 'learning-resources/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('admin.learning-resources.index')
            ->with('success', 'Changes saved. The resource is now a draft pending approval.');
    }

    public function updateStatus(Request $request, LearningResource $learning_resource)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'approved'])],
        ]);

        if ($data['status'] === 'approved' && !in_array($learning_resource->type, ['pdf', 'youtube', 'url'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Convert legacy resources to PDF or a supported link before approving them.',
            ]);
        }

        if ($data['status'] === 'approved' && !$learning_resource->learningTopics()->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Assign at least one topic before approving this resource.',
            ]);
        }

        $learning_resource->update([
            'status' => $data['status'],
            'approved_by' => $data['status'] === 'approved' ? auth()->id() : null,
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        return redirect()->route('admin.learning-resources.index')
            ->with('success', $data['status'] === 'approved' ? 'Resource approved.' : 'Resource returned to draft.');
    }

    public function destroy(LearningResource $learning_resource)
    {
        // Preserve resources that are referenced by historical recommendations.
        if ($learning_resource->recommendations()->exists()) {
            return back()->withErrors([
                'delete' => 'This resource has recommendation history. Return it to draft instead of deleting it.',
            ]);
        }

        $file = $learning_resource->type === 'pdf' ? $learning_resource->content : null;
        DB::transaction(function () use ($learning_resource) {
            $learning_resource->learningTopics()->detach();
            $learning_resource->delete();
        });

        if ($file && str_starts_with($file, 'learning-resources/')) {
            Storage::disk('public')->delete($file);
        }

        return redirect()->route('admin.learning-resources.index')
            ->with('success', 'Resource deleted.');
    }

    private function validatedResource(Request $request, bool $updating): array
    {
        $type = $request->input('type');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'type' => ['required', Rule::in(['pdf', 'youtube', 'url'])],
            'subject_key' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9_-]+$/'],
            'program' => ['nullable', 'string', 'max:32'],
            'learning_topic_ids' => ['required', 'array', 'min:1'],
            'learning_topic_ids.*' => ['required', 'integer', 'distinct', Rule::exists('learning_topics', 'id')],
            'content_file' => [
                Rule::requiredIf($type === 'pdf' && !$updating),
                'nullable', 'file', 'mimes:pdf', 'max:20480',
            ],
            'content_url' => [
                Rule::requiredIf(in_array($type, ['youtube', 'url'], true)),
                'nullable', 'url', 'max:2048',
            ],
        ]);

        if (in_array($type, ['youtube', 'url'], true)) {
            $url = $data['content_url'];
            $parts = parse_url($url);
            $scheme = strtolower($parts['scheme'] ?? '');
            $host = strtolower($parts['host'] ?? '');

            if (!in_array($scheme, ['http', 'https'], true) || !$host) {
                throw ValidationException::withMessages([
                    'content_url' => 'Enter a valid HTTP or HTTPS URL.',
                ]);
            }

            if ($type === 'youtube' && !in_array($host, [
                'youtube.com', 'www.youtube.com', 'm.youtube.com',
                'music.youtube.com', 'youtu.be', 'www.youtu.be',
            ], true)) {
                throw ValidationException::withMessages([
                    'content_url' => 'YouTube resources must use a youtube.com or youtu.be URL.',
                ]);
            }
        }

        return $data;
    }
}
