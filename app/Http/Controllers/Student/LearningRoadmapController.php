<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LearningRoadmapController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $joinedClasses = $user->joinedClasses()->with('faculty')->get();
        $accessAllowed = $this->studentHasExperimentalAccess((int) $user->id);

        $recommendations = collect();

        if ($accessAllowed) {
            $recommendations = LearningRecommendation::query()
                ->where('user_id', $user->id)
                ->with([
                    'topic',
                    'labSession.faculty',
                    'quizAttempt.quiz',
                    'professorMaterial',
                    'libraryResource',
                ])
                ->latest('recommended_at')
                ->get();

            $ids = $recommendations->pluck('id');
            if ($ids->isNotEmpty()) {
                LearningRecommendation::whereIn('id', $ids)
                    ->whereNull('displayed_at')
                    ->update(['displayed_at' => now()]);
            }
        }

        $stats = [
            'total' => $recommendations->count(),
            'pending' => $recommendations->whereIn('status', ['recommended', 'opened'])->count(),
            'completed' => $recommendations->where('status', 'completed')->count(),
            'topics' => $recommendations->pluck('learning_topic_id')->unique()->count(),
        ];

        return view('student.learning-roadmap.index', compact(
            'joinedClasses',
            'recommendations',
            'stats',
            'accessAllowed'
        ));
    }

    public function open(LearningRecommendation $recommendation)
    {
        $this->authorizeRecommendation($recommendation);

        if (!$recommendation->opened_at) {
            $recommendation->opened_at = now();
        }
        if ($recommendation->status === 'recommended') {
            $recommendation->status = 'opened';
        }
        $recommendation->save();

        if ($recommendation->learning_resource_id) {
            $resource = $recommendation->libraryResource;
            abort_unless($resource && $resource->status === 'approved', 404);

            if ($resource->type === 'pdf') {
                return redirect()->away(Storage::disk('public')->url($resource->content));
            }

            return redirect()->away($resource->content);
        }

        if ($recommendation->material_id) {
            $material = $recommendation->professorMaterial;
            abort_unless($material, 404);

            if (in_array($material->type, ['youtube', 'url'], true)) {
                return redirect()->away($material->content);
            }

            return redirect()->away(url('/' . ltrim($material->content, '/')));
        }

        abort(404, 'Recommended resource is no longer available.');
    }

    public function complete(LearningRecommendation $recommendation)
    {
        $this->authorizeRecommendation($recommendation);

        $recommendation->update([
            'status' => 'completed',
            'completed_at' => $recommendation->completed_at ?? now(),
            'opened_at' => $recommendation->opened_at ?? now(),
        ]);

        return back()->with('success', 'Resource marked as completed.');
    }

    private function authorizeRecommendation(LearningRecommendation $recommendation): void
    {
        abort_unless((int) $recommendation->user_id === (int) auth()->id(), 403);
        abort_unless($recommendation->research_experiment_id, 403);

        $eligible = DB::table('research_groups as rg')
            ->join('research_participants as rp', 'rp.research_group_id', '=', 'rg.id')
            ->where('rg.research_experiment_id', $recommendation->research_experiment_id)
            ->where('rg.lab_session_id', $recommendation->lab_session_id)
            ->where('rg.group_type', 'experimental')
            ->where('rg.recommendations_enabled', true)
            ->where('rp.user_id', auth()->id())
            ->where('rp.consent_status', 'consented')
            ->exists();

        abort_unless($eligible, 403);
    }

    private function studentHasExperimentalAccess(int $userId): bool
    {
        return DB::table('research_participants as rp')
            ->join('research_groups as rg', 'rg.id', '=', 'rp.research_group_id')
            ->join('research_experiments as re', 're.id', '=', 'rg.research_experiment_id')
            ->where('rp.user_id', $userId)
            ->where('rp.consent_status', 'consented')
            ->where('rg.group_type', 'experimental')
            ->where('rg.recommendations_enabled', true)
            ->where('re.status', 'active')
            ->exists();
    }
}
