<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\SavedGuide;
use App\Models\GuideVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuideController extends Controller
{
    private const TYPES = ['beginner', 'builds', 'secrets'];

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $guides = Guide::query()->with('author:id,name')
            ->withCount(['votes', 'comments'])
            ->when($userId, function ($query) use ($userId): void {
                $query->withExists([
                    'votes as helpful_by_me' => fn ($votes) => $votes->where('user_id', $userId),
                    'saves as saved_by_me' => fn ($saves) => $saves->where('user_id', $userId),
                ]);
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Guide $guide): array => $this->serializeGuide($guide));

        return response()->json(['guides' => $guides]);
    }

    public function show(Request $request, Guide $guide): JsonResponse
    {
        $guide->load('author:id,name')
            ->loadCount(['votes', 'comments'])
            ->loadExists([
                'votes as helpful_by_me' => fn ($votes) => $votes->where('user_id', $request->user()?->id ?? 0),
                'saves as saved_by_me' => fn ($saves) => $saves->where('user_id', $request->user()?->id ?? 0),
            ]);

        return response()->json([
            'guide' => $this->serializeGuide($guide),
            'comments' => $guide->comments()->with('author:id,name')->oldest()->take(100)->get()->map(
                fn (GuideComment $comment): array => [
                    'id' => $comment->id,
                    'author' => $comment->author->name,
                    'body' => $comment->body,
                    'createdAt' => $comment->created_at,
                ]
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game' => ['required', 'string', 'min:2', 'max:60'],
            'title' => ['required', 'string', 'min:8', 'max:90'],
            'body' => ['required', 'string', 'min:30', 'max:3000'],
            'type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
        ]);

        $guide = $request->user()->guides()->create([
            'game' => mb_strtoupper(trim($validated['game'])),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'type' => $validated['type'],
        ]);

        return response()->json(['guide' => $this->serializeGuide($guide->load('author:id,name'))], 201);
    }

    public function toggleHelpful(Request $request, Guide $guide): JsonResponse
    {
        $userId = $request->user()->id;
        $vote = GuideVote::where('user_id', $userId)->where('guide_id', $guide->id)->first();

        if ($vote) {
            $vote->delete();
        } else {
            GuideVote::create(['user_id' => $userId, 'guide_id' => $guide->id]);
        }

        return response()->json([
            'helpful' => (int) $guide->fresh()->seeded_helpful + $guide->votes()->count(),
            'helpfulByMe' => ! $vote,
        ]);
    }

    public function toggleSaved(Request $request, Guide $guide): JsonResponse
    {
        $saved = SavedGuide::where('user_id', $request->user()->id)->where('guide_id', $guide->id)->first();

        if ($saved) {
            $saved->delete();
        } else {
            SavedGuide::create(['user_id' => $request->user()->id, 'guide_id' => $guide->id]);
        }

        return response()->json(['saved' => ! $saved]);
    }

    public function saved(Request $request): JsonResponse
    {
        $guides = $request->user()->savedGuides()->with([
            'guide.author:id,name',
            'guide.votes',
            'guide.comments',
        ])->get()->map(fn (SavedGuide $saved): array => $this->serializeGuide($saved->guide));

        return response()->json(['guides' => $guides]);
    }

    public function comment(Request $request, Guide $guide): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000'],
        ]);
        $comment = $guide->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ])->load('author:id,name');

        return response()->json([
            'comment' => [
                'id' => $comment->id,
                'author' => $comment->author->name,
                'body' => $comment->body,
                'createdAt' => $comment->created_at,
            ],
        ], 201);
    }

    private function serializeGuide(Guide $guide): array
    {
        $typeLabels = ['beginner' => 'BEGINNER FRIENDLY', 'builds' => 'BUILDS & LOADOUTS', 'secrets' => 'SECRETS & QUESTS'];

        return [
            'id' => $guide->id,
            'game' => $guide->game,
            'title' => $guide->title,
            'summary' => $guide->body,
            'author' => $guide->author?->name ?? $guide->seeded_author ?? 'player',
            'avatar' => mb_strtoupper(mb_substr($guide->author?->name ?? $guide->seeded_author ?? 'P', 0, 1)),
            'createdAt' => $guide->created_at,
            'helpful' => (int) $guide->seeded_helpful + (int) ($guide->votes_count ?? $guide->votes()->count()),
            'helpfulByMe' => (bool) ($guide->helpful_by_me ?? false),
            'savedByMe' => (bool) ($guide->saved_by_me ?? false),
            'commentsCount' => (int) ($guide->comments_count ?? $guide->comments()->count()),
            'type' => $guide->type,
            'tag' => $typeLabels[$guide->type] ?? 'COMMUNITY GUIDE',
        ];
    }
}
