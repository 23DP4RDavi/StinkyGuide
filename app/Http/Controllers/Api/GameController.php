<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameLibrary;
use App\Models\UserInterest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    private const INTERESTS = ['Adventure', 'Cozy', 'RPG', 'Strategy', 'Indie'];

    private const LIBRARY_STATUSES = ['wishlist', 'playing', 'completed', 'dropped'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $interests = $user ? $user->interests()->pluck('interest')->all() : ['Adventure'];
        $library = $user
            ? $user->libraryEntries()->pluck('status', 'game_id')
            : collect();

        $games = Game::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('name')
            ->get()
            ->map(function (Game $game) use ($interests, $library): array {
                $tags = $game->tags ?? [];
                $matched = count(array_intersect($tags, $interests));
                $match = $interests === []
                    ? $game->match_score
                    : min(99, max(68, (int) round(($matched / max(count($tags), 1)) * 32 + 68)));

                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'genre' => $game->genre,
                    'steamAppId' => $game->steam_app_id,
                    'imageUrl' => "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$game->steam_app_id}/header.jpg",
                    'tags' => $tags,
                    'match' => $match,
                    'platform' => $game->platform,
                    'rating' => $game->reviews_avg_rating ? number_format((float) $game->reviews_avg_rating * 2, 1) : 'NEW',
                    'reviewCount' => $game->reviews_count,
                    'libraryStatus' => $library[$game->id] ?? null,
                ];
            });

        return response()->json(['games' => $games, 'interests' => $interests]);
    }

    public function show(Game $game): JsonResponse
    {
        $game->loadCount('reviews')->loadAvg('reviews', 'rating');

        return response()->json([
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
                'genre' => $game->genre,
                'steamAppId' => $game->steam_app_id,
                'imageUrl' => "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$game->steam_app_id}/header.jpg",
                'tags' => $game->tags,
                'platform' => $game->platform,
                'rating' => $game->reviews_avg_rating ? number_format((float) $game->reviews_avg_rating * 2, 1) : 'NEW',
                'reviewCount' => $game->reviews_count,
            ],
            'reviews' => $game->reviews()->with('author:id,name')->latest()->take(20)->get()->map(
                fn ($review): array => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'author' => $review->author?->name ?? $review->seeded_author ?? 'player',
                    'hoursPlayed' => $review->hours_played,
                    'createdAt' => $review->created_at,
                ]
            ),
        ]);
    }

    public function updateInterests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'interests' => ['required', 'array', 'max:5'],
            'interests.*' => ['required', 'string', 'distinct', 'in:Adventure,Cozy,RPG,Strategy,Indie'],
        ]);
        $user = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            $user->interests()->delete();
            $user->interests()->createMany(array_map(
                fn (string $interest): array => ['interest' => $interest],
                $validated['interests'],
            ));
        });

        return response()->json(['interests' => $user->interests()->pluck('interest')]);
    }

    public function library(Request $request): JsonResponse
    {
        $entries = $request->user()->libraryEntries()->with('game')->latest('updated_at')->get();

        return response()->json([
            'library' => $entries->map(fn (GameLibrary $entry): array => [
                'status' => $entry->status,
                'game' => $this->gameSummary($entry->game, $entry->status),
            ]),
        ]);
    }

    public function updateLibrary(Request $request, Game $game): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', self::LIBRARY_STATUSES)],
        ]);

        if ($validated['status'] === null) {
            $request->user()->libraryEntries()->where('game_id', $game->id)->delete();

            return response()->json(['status' => null]);
        }

        $entry = GameLibrary::updateOrCreate(
            ['user_id' => $request->user()->id, 'game_id' => $game->id],
            ['status' => $validated['status']],
        );

        return response()->json(['status' => $entry->status, 'game' => $this->gameSummary($game, $entry->status)]);
    }

    private function gameSummary(Game $game, ?string $status): array
    {
        return [
            'id' => $game->id,
            'name' => $game->name,
            'genre' => $game->genre,
            'steamAppId' => $game->steam_app_id,
            'imageUrl' => "https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/{$game->steam_app_id}/header.jpg",
            'tags' => $game->tags,
            'platform' => $game->platform,
            'status' => $status,
        ];
    }
}
