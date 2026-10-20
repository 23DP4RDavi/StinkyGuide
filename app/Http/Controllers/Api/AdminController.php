<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\Review;
use App\Models\TrafficEvent;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();
        $dayStart = $now->copy()->subDays(6)->startOfDay();
        $recentStart = $now->copy()->subDay();

        $dailyTraffic = TrafficEvent::query()
            ->where('created_at', '>=', $dayStart)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as requests')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get()
            ->map(fn (TrafficEvent $event): array => [
                'day' => $event->day,
                'requests' => (int) $event->requests,
            ]);

        return response()->json([
            'traffic' => [
                'requests24h' => TrafficEvent::where('created_at', '>=', $recentStart)->count(),
                'players24h' => TrafficEvent::where('created_at', '>=', $recentStart)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'daily' => $dailyTraffic,
                'popularPages' => TrafficEvent::query()
                    ->where('created_at', '>=', $recentStart)
                    ->selectRaw('path, COUNT(*) as requests')
                    ->groupBy('path')
                    ->orderByDesc('requests')
                    ->limit(8)
                    ->get()
                    ->map(fn (TrafficEvent $event): array => [
                        'path' => $event->path,
                        'requests' => (int) $event->requests,
                    ]),
                'statusCodes' => TrafficEvent::query()
                    ->where('created_at', '>=', $recentStart)
                    ->selectRaw('status, COUNT(*) as requests')
                    ->groupBy('status')
                    ->orderBy('status')
                    ->get()
                    ->map(fn (TrafficEvent $event): array => [
                        'status' => (int) $event->status,
                        'requests' => (int) $event->requests,
                    ]),
            ],
            'content' => [
                'guides' => Guide::query()->with('author:id,name')->latest()->limit(20)->get()->map(fn (Guide $guide): array => [
                    'id' => $guide->id,
                    'title' => $guide->title,
                    'game' => $guide->game,
                    'author' => $guide->author?->name ?? $guide->seeded_author ?? 'player',
                    'createdAt' => $guide->created_at,
                ]),
                'reviews' => Review::query()->whereNull('seeded_author')->with('author:id,name')->latest()->limit(20)->get()->map(fn (Review $review): array => [
                    'id' => $review->id,
                    'game' => $review->game_name,
                    'body' => $review->body,
                    'author' => $review->author?->name ?? 'Former player',
                    'createdAt' => $review->created_at,
                ]),
                'comments' => GuideComment::query()->with(['author:id,name', 'guide:id,title'])->latest()->limit(20)->get()->map(fn (GuideComment $comment): array => [
                    'id' => $comment->id,
                    'guide' => $comment->guide->title,
                    'body' => $comment->body,
                    'author' => $comment->author->name,
                    'createdAt' => $comment->created_at,
                ]),
            ],
        ]);
    }

    public function destroyGuide(Guide $guide): JsonResponse
    {
        $guide->delete();

        return response()->json(['deleted' => true]);
    }

    public function destroyReview(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json(['deleted' => true]);
    }

    public function destroyComment(GuideComment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(['deleted' => true]);
    }
}
