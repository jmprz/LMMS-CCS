<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningRecommendation;
use App\Models\RecommendationEngagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                    'engagements',
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

        $openedCount = $recommendations
            ->filter(fn ($recommendation) => $recommendation->engagements->isNotEmpty())
            ->count();

        $stats = [
            'total' => $recommendations->count(),
            'pending' => $recommendations
                ->filter(fn ($recommendation) => $recommendation->engagements->isEmpty())
                ->count(),
            'opened' => $openedCount,
            'topics' => $recommendations->pluck('learning_topic_id')->unique()->count(),
        ];

        return view('student.learning-roadmap.index', compact(
            'joinedClasses',
            'recommendations',
            'stats',
            'accessAllowed'
        ));
    }

    /**
     * Legacy direct-open endpoint kept for compatibility.
     * New roadmap UI uses engagementStart() so viewing time can be measured.
     */
    public function open(LearningRecommendation $recommendation)
    {
        $this->authorizeRecommendation($recommendation);
        $payload = $this->resourcePayload($recommendation);

        if (!$recommendation->opened_at) {
            $recommendation->opened_at = now();
        }
        if ($recommendation->status === 'recommended') {
            $recommendation->status = 'opened';
        }
        $recommendation->save();

        return redirect()->away($payload['url']);
    }

    public function engagementStart(Request $request, LearningRecommendation $recommendation)
    {
        $this->authorizeRecommendation($recommendation);
        $payload = $this->resourcePayload($recommendation);

        // Close any abandoned open row for this same recommendation/user.
        RecommendationEngagement::query()
            ->where('learning_recommendation_id', $recommendation->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('closed_at')
            ->update([
                'closed_at' => now(),
                'updated_at' => now(),
            ]);

        $engagement = RecommendationEngagement::create([
            'learning_recommendation_id' => $recommendation->id,
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'last_heartbeat_at' => now(),
            'duration_seconds' => 0,
        ]);

        if (!$recommendation->opened_at) {
            $recommendation->opened_at = now();
        }
        if ($recommendation->status === 'recommended') {
            $recommendation->status = 'opened';
        }
        $recommendation->save();

        return response()->json([
            'success' => true,
            'engagement_id' => $engagement->id,
            'resource' => $payload,
        ]);
    }

    public function engagementHeartbeat(Request $request, RecommendationEngagement $engagement)
    {
        $this->authorizeEngagement($engagement);

        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ]);

        // Client reports active-visible seconds. Never let a delayed request reduce it.
        $engagement->update([
            'duration_seconds' => max(
                (int) $engagement->duration_seconds,
                (int) $validated['duration_seconds']
            ),
            'last_heartbeat_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function engagementEnd(Request $request, RecommendationEngagement $engagement)
    {
        $this->authorizeEngagement($engagement);

        $validated = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ]);

        $engagement->update([
            'duration_seconds' => max(
                (int) $engagement->duration_seconds,
                (int) $validated['duration_seconds']
            ),
            'last_heartbeat_at' => now(),
            'closed_at' => $engagement->closed_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'duration_seconds' => (int) $engagement->duration_seconds,
        ]);
    }

    /**
     * Kept only so old links/routes do not break. The UI no longer relies on
     * manual completion because engagement is measured automatically.
     */
    public function complete(LearningRecommendation $recommendation)
    {
        $this->authorizeRecommendation($recommendation);

        return back()->with(
            'success',
            'Completion is now measured automatically from resource engagement.'
        );
    }

    private function resourcePayload(LearningRecommendation $recommendation): array
    {
        if ($recommendation->learning_resource_id) {
            $resource = $recommendation->libraryResource;
            abort_unless($resource && $resource->status === 'approved', 404);

            $url = $resource->type === 'pdf'
                ? Storage::disk('public')->url($resource->content)
                : $resource->content;

            if ($resource->type === 'youtube') {
                $url = $this->youtubeEmbedUrl($url);
            }

            return [
                'title' => $resource->title,
                'type' => $resource->type,
                'url' => $url,
                'source' => 'Resource Library',
            ];
        }

        if ($recommendation->material_id) {
            $material = $recommendation->professorMaterial;
            abort_unless($material, 404);

            $url = in_array($material->type, ['youtube', 'url'], true)
                ? $material->content
                : url('/' . ltrim($material->content, '/'));

            if ($material->type === 'youtube') {
                $url = $this->youtubeEmbedUrl($url);
            }

            return [
                'title' => $material->title,
                'type' => $material->type,
                'url' => $url,
                'source' => 'Professor Material',
            ];
        }

        abort(404, 'Recommended resource is no longer available.');
    }

    private function youtubeEmbedUrl(string $url): string
    {
        if (Str::contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        if (Str::contains($url, 'youtu.be/')) {
            $videoId = Str::before(Str::after($url, 'youtu.be/'), '?');
            return 'https://www.youtube.com/embed/' . $videoId;
        }

        if (Str::contains($url, 'youtube.com/watch')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            if (!empty($query['v'])) {
                return 'https://www.youtube.com/embed/' . $query['v'];
            }
        }

        return $url;
    }

    private function authorizeEngagement(RecommendationEngagement $engagement): void
    {
        abort_unless((int) $engagement->user_id === (int) auth()->id(), 403);
        $engagement->loadMissing('recommendation');
        abort_unless($engagement->recommendation, 404);
        $this->authorizeRecommendation($engagement->recommendation);
    }

    private function authorizeRecommendation(LearningRecommendation $recommendation): void
    {
        abort_unless((int) $recommendation->user_id === (int) auth()->id(), 403);
        abort_unless($recommendation->research_experiment_id, 403);

        $eligible = DB::table('research_groups as rg')
            ->join('research_participants as rp', 'rp.research_group_id', '=', 'rg.id')
            ->join('research_experiments as re', 're.id', '=', 'rg.research_experiment_id')
            ->where('rg.research_experiment_id', $recommendation->research_experiment_id)
            ->where('rg.lab_session_id', $recommendation->lab_session_id)
            ->where('rg.group_type', 'experimental')
            ->where('rg.recommendations_enabled', true)
            ->where('rp.user_id', auth()->id())
            ->where('rp.consent_status', 'consented')
            ->where('re.status', 'active')
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
