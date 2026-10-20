<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reviews = Review::query()->with('author:id,name')
            ->when($request->filled('game'), fn ($query) => $query->where('game_name', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->string('game')->toString()).'%'))
            ->when($request->filled('rating'), fn ($query) => $query->where('rating', $request->integer('rating')))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Review $review): array => $this->serializeReview($review));

        $summary = Review::query()
            ->selectRaw('COUNT(*) as count, AVG(rating) as average_rating')
            ->first();

        return response()->json([
            'reviews' => $reviews,
            'summary' => [
                'count' => (int) ($summary->count ?? 0),
                'averageRating' => $summary->average_rating === null ? 0 : round((float) $summary->average_rating, 1),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game' => ['required', 'string', 'min:2', 'max:60'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:20', 'max:1200'],
            'hoursPlayed' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);
        $game = Game::whereRaw('lower(name) = ?', [mb_strtolower(trim($validated['game']))])->first();

        $review = $request->user()->reviews()->create([
            'game_id' => $game?->id,
            'game_name' => $game?->name ?? trim($validated['game']),
            'rating' => $validated['rating'],
            'body' => trim($validated['body']),
            'hours_played' => $validated['hoursPlayed'] ?? 0,
        ])->load('author:id,name');

        return response()->json(['review' => $this->serializeReview($review)], 201);
    }

    private function serializeReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'game' => $review->game_name,
            'rating' => $review->rating,
            'body' => $review->body,
            'hoursPlayed' => $review->hours_played,
            'author' => $review->author?->name ?? $review->seeded_author ?? 'player',
            'createdAt' => $review->created_at,
        ];
    }
}
