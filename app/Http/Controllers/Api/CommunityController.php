<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Guide;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    public function stats(): JsonResponse
    {
        $reviews = Review::query()
            ->whereNull('seeded_author')
            ->selectRaw('COUNT(*) AS count, AVG(rating) AS average_rating')
            ->first();

        return response()->json([
            'guides' => Guide::count(),
            'games' => Game::count(),
            'players' => User::count(),
            'reviews' => (int) ($reviews->count ?? 0),
            'averageRating' => $reviews->average_rating === null ? 0 : round((float) $reviews->average_rating, 1),
        ]);
    }
}
