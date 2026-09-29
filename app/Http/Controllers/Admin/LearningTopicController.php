<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LearningTopicController extends Controller
{
    /** Learn where standardized topics are already referenced. */
    private function hasMaterialTags(): bool
    {
        return Schema::hasTable('professor_material_topic')
            && Schema::hasColumn('professor_material_topic', 'learning_topic_id');
    }

    private function hasQuizTags(): bool
    {
        return Schema::hasTable('quizzes')
            && Schema::hasColumn('quizzes', 'learning_topic_id');
    }

    private function hasRecommendationTags(): bool
    {
        return Schema::hasTable('learning_recommendations')
            && Schema::hasColumn('learning_recommendations', 'learning_topic_id');
    }

    private function scopeInUse($query, bool $materialTags, bool $quizTags, bool $recommendationTags): void
    {
        $query->where(function ($q) use ($materialTags, $quizTags, $recommendationTags) {
            $q->whereHas('learningResources');

            if ($materialTags) {
                $q->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')->from('professor_material_topic')
                        ->whereColumn('professor_material_topic.learning_topic_id', 'learning_topics.id');
                });
            }
            if ($quizTags) {
                $q->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')->from('quizzes')
                        ->whereColumn('quizzes.learning_topic_id', 'learning_topics.id');
                });
            }
            if ($recommendationTags) {
                $q->orWhereExists(function ($sub) {
                    $sub->selectRaw('1')->from('learning_recommendations')
                        ->whereColumn('learning_recommendations.learning_topic_id', 'learning_topics.id');
                });
            }
        });
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:191'],
            'usage' => ['nullable', Rule::in(['all', 'used', 'unused'])],
            'sort' => ['nullable', Rule::in(['name', 'newest', 'oldest', 'resources'])],
        ]);

        $materialTags = $this->hasMaterialTags();
        $quizTags = $this->hasQuizTags();
        $recommendationTags = $this->hasRecommendationTags();

        $topicsQuery = LearningTopic::query()
            ->select('learning_topics.*')
            ->withCount('learningResources');

        if ($materialTags) {
            $topicsQuery->selectSub(
                DB::table('professor_material_topic')->selectRaw('COUNT(*)')
                    ->whereColumn('professor_material_topic.learning_topic_id', 'learning_topics.id'),
                'materials_count'
            );
        } else {
            $topicsQuery->selectRaw('0 AS materials_count');
        }

        if ($quizTags) {
            $topicsQuery->selectSub(
                DB::table('quizzes')->selectRaw('COUNT(*)')
                    ->whereColumn('quizzes.learning_topic_id', 'learning_topics.id'),
                'quizzes_count'
            );
        } else {
            $topicsQuery->selectRaw('0 AS quizzes_count');
        }

        if ($recommendationTags) {
            $topicsQuery->selectSub(
                DB::table('learning_recommendations')->selectRaw('COUNT(*)')
                    ->whereColumn('learning_recommendations.learning_topic_id', 'learning_topics.id'),
                'recommendations_count'
            );
        } else {
            $topicsQuery->selectRaw('0 AS recommendations_count');
        }

        if ($search = trim($filters['search'] ?? '')) {
            $topicsQuery->where(function ($q) use ($search) {
                $q->where('learning_topics.name', 'like', '%' . $search . '%')
                    ->orWhere('learning_topics.slug', 'like', '%' . $search . '%')
                    ->orWhere('learning_topics.description', 'like', '%' . $search . '%');
            });
        }

        if (($filters['usage'] ?? 'all') === 'used') {
            $this->scopeInUse($topicsQuery, $materialTags, $quizTags, $recommendationTags);
        } elseif (($filters['usage'] ?? 'all') === 'unused') {
            $topicsQuery->whereDoesntHave('learningResources');
            if ($materialTags) {
                $topicsQuery->whereNotExists(function ($sub) {
                    $sub->selectRaw('1')->from('professor_material_topic')
                        ->whereColumn('professor_material_topic.learning_topic_id', 'learning_topics.id');
                });
            }
            if ($quizTags) {
                $topicsQuery->whereNotExists(function ($sub) {
                    $sub->selectRaw('1')->from('quizzes')
                        ->whereColumn('quizzes.learning_topic_id', 'learning_topics.id');
                });
            }
            if ($recommendationTags) {
                $topicsQuery->whereNotExists(function ($sub) {
                    $sub->selectRaw('1')->from('learning_recommendations')
                        ->whereColumn('learning_recommendations.learning_topic_id', 'learning_topics.id');
                });
            }
        }

        switch ($filters['sort'] ?? 'name') {
            case 'newest': $topicsQuery->orderByDesc('learning_topics.created_at')->orderBy('learning_topics.id'); break;
            case 'oldest': $topicsQuery->orderBy('learning_topics.created_at')->orderBy('learning_topics.id'); break;
            case 'resources': $topicsQuery->orderByDesc('learning_resources_count')->orderBy('learning_topics.name'); break;
            default: $topicsQuery->orderBy('learning_topics.name');
        }

        $topics = $topicsQuery->paginate(12)->withQueryString();
        $totalTopics = LearningTopic::count();
        $usedQuery = LearningTopic::query();
        $this->scopeInUse($usedQuery, $materialTags, $quizTags, $recommendationTags);
        $usedTopics = $usedQuery->count();
        $resourceLinks = Schema::hasTable('learning_resource_topic')
            ? DB::table('learning_resource_topic')->count()
            : 0;

        return view('admin.learning-topics.index', compact(
            'topics', 'totalTopics', 'usedTopics', 'resourceLinks'
        ));
    }

    private function validatedTopic(Request $request, ?LearningTopic $learningTopic = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191',
                Rule::unique('learning_topics', 'name')->ignore($learningTopic?->id)],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function topicSlug(string $name, ?LearningTopic $learningTopic = null): string
    {
        $slug = Str::slug(trim($name));
        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'Please provide a valid topic name.']);
        }
        $exists = LearningTopic::query()->where('slug', $slug)
            ->when($learningTopic, fn ($q) => $q->whereKeyNot($learningTopic->id))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A topic with the same URL-friendly name already exists.',
            ]);
        }
        return $slug;
    }

    public function store(Request $request)
    {
        $validated = $this->validatedTopic($request);
        $slug = $this->topicSlug($validated['name']);
        LearningTopic::create([
            'name' => trim($validated['name']),
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);
        return redirect()->route('admin.learning-topics.index')
            ->with('success', 'Learning topic added successfully.');
    }

    public function update(Request $request, LearningTopic $learningTopic)
    {
        $validated = $this->validatedTopic($request, $learningTopic);
        $slug = $this->topicSlug($validated['name'], $learningTopic);
        $learningTopic->update([
            'name' => trim($validated['name']),
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);
        return redirect()->route('admin.learning-topics.index', $request->only(['search', 'usage', 'sort', 'page']))
            ->with('success', 'Learning topic updated. Existing quiz records and resource tags were preserved.');
    }

    public function destroy(LearningTopic $learningTopic)
    {
        DB::transaction(function () use ($learningTopic) {
            $topic = LearningTopic::query()->lockForUpdate()->findOrFail($learningTopic->id);
            $linkedResources = $topic->learningResources()->exists();
            $linkedMaterials = $this->hasMaterialTags()
                && DB::table('professor_material_topic')->where('learning_topic_id', $topic->id)->exists();
            $linkedQuizzes = $this->hasQuizTags()
                && DB::table('quizzes')->where('learning_topic_id', $topic->id)->exists();
            $linkedRecommendations = $this->hasRecommendationTags()
                && DB::table('learning_recommendations')->where('learning_topic_id', $topic->id)->exists();

            if ($linkedResources || $linkedMaterials || $linkedQuizzes || $linkedRecommendations) {
                throw ValidationException::withMessages([
                    'delete' => 'This topic is already used by a resource, classroom material, quiz, or recommendation. It cannot be deleted because that would break existing records.',
                ]);
            }
            $topic->delete();
        });

        return back()->with('success', 'Unused learning topic deleted.');
    }
}
