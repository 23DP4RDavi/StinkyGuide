<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Guide;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PDO;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $legacyPath = base_path('data/stinkyguide.sqlite');
        $legacy = file_exists($legacyPath) ? new PDO('sqlite:'.$legacyPath, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]) : null;
        $legacyGames = $legacy?->query('SELECT * FROM games ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $legacyGuides = $legacy?->query('SELECT * FROM guides ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $legacyReviews = $legacy?->query('SELECT * FROM reviews ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        DB::transaction(function () use ($legacyGames, $legacyGuides, $legacyReviews): void {
            $gameDefinitions = $legacyGames ?: [
                ['name' => 'Elden Ring', 'genre' => 'Action RPG · Open world', 'steam_app_id' => 1245620, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 96, 'platform' => 'PC · PS5'],
                ['name' => 'Hades II', 'genre' => 'Roguelike · Action', 'steam_app_id' => 1145350, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 92, 'platform' => 'PC'],
                ['name' => 'Stardew Valley', 'genre' => 'Farming · Cozy', 'steam_app_id' => 413150, 'tags_json' => '["Cozy","Indie"]', 'match_score' => 89, 'platform' => 'EVERYWHERE'],
                ['name' => "Baldur's Gate 3", 'genre' => 'RPG · Turn-based', 'steam_app_id' => 1086940, 'tags_json' => '["RPG","Adventure"]', 'match_score' => 94, 'platform' => 'PC · PS5'],
                ['name' => 'Slay the Spire', 'genre' => 'Deckbuilder · Strategy', 'steam_app_id' => 646570, 'tags_json' => '["Strategy","Indie"]', 'match_score' => 88, 'platform' => 'PC · SWITCH'],
                ['name' => 'Sea of Stars', 'genre' => 'RPG · Turn-based', 'steam_app_id' => 1244090, 'tags_json' => '["RPG","Adventure"]', 'match_score' => 85, 'platform' => 'ALL PLATFORMS'],
                ['name' => 'Dredge', 'genre' => 'Fishing · Lovecraftian', 'steam_app_id' => 1562430, 'tags_json' => '["Cozy","Indie"]', 'match_score' => 83, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Into the Breach', 'genre' => 'Strategy · Tactical', 'steam_app_id' => 590380, 'tags_json' => '["Strategy","Indie"]', 'match_score' => 82, 'platform' => 'PC · SWITCH'],
                ['name' => 'Cyberpunk 2077', 'genre' => 'RPG · Open world', 'steam_app_id' => 1091500, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 90, 'platform' => 'PC · PS5'],
            ];

            foreach ($gameDefinitions as $row) {
                Game::unguarded(fn () => Game::firstOrCreate(
                    ['steam_app_id' => (int) $row['steam_app_id']],
                    [
                        'name' => $row['name'],
                        'genre' => $row['genre'],
                        'tags' => json_decode($row['tags_json'], true) ?: [],
                        'match_score' => (int) $row['match_score'],
                        'platform' => $row['platform'],
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? $row['created_at'] ?? now(),
                    ],
                ));
            }

            $guideDefinitions = $legacyGuides ?: [
                ['game' => 'ELDEN RING', 'title' => 'So you picked up a sword. Now what?', 'body' => 'A no-nonsense first 10 hours: where to go, what to level, and when to just run away.', 'type' => 'beginner', 'seeded_helpful' => 342, 'seeded_author' => 'mossy_moth'],
                ['game' => "BALDUR'S GATE 3", 'title' => 'The party comp that carried my first run', 'body' => "No respecs, no multiclass homework. Just four friends who have each other's backs.", 'type' => 'builds', 'seeded_helpful' => 218, 'seeded_author' => 'crit_magnet'],
                ['game' => 'HADES II', 'title' => 'Every weapon, explained like you are busy', 'body' => 'Quick reads on all six weapons, their best aspects, and which one is secretly busted.', 'type' => 'builds', 'seeded_helpful' => 196, 'seeded_author' => 'witchybits'],
                ['game' => 'STARDEW VALLEY', 'title' => 'A cozy first spring (with room to breathe)', 'body' => 'A gentle day-by-day plan for your first season. No min-maxing required, promise.', 'type' => 'beginner', 'seeded_helpful' => 174, 'seeded_author' => 'turnip_enthusiast'],
                ['game' => 'ELDEN RING', 'title' => 'The little cave that gives you a big head start', 'body' => 'An early-game detour for a useful talisman, a lovely view, and an easier first boss.', 'type' => 'secrets', 'seeded_helpful' => 153, 'seeded_author' => 'ashen_kite'],
                ['game' => 'SLAY THE SPIRE', 'title' => 'Stop losing Act 2: a tiny deckbuilding guide', 'body' => 'What to remove, when to skip, and how to tell a good card from a tempting one.', 'type' => 'beginner', 'seeded_helpful' => 141, 'seeded_author' => 'cardboardhero'],
                ['game' => 'DREDGE', 'title' => 'All the fish you need before the fog gets weird', 'body' => 'A spoiler-light checklist for the early catches, upgrades, and close calls.', 'type' => 'secrets', 'seeded_helpful' => 108, 'seeded_author' => 'brine_time'],
                ['game' => 'SEA OF STARS', 'title' => 'Combo points, timed hits, and other nice tricks', 'body' => "The combat rhythm clicked when I stopped mashing. Here's what finally worked.", 'type' => 'builds', 'seeded_helpful' => 89, 'seeded_author' => 'pixelpilgrim'],
            ];

            foreach ($guideDefinitions as $row) {
                Guide::unguarded(fn () => Guide::firstOrCreate(
                    ['title' => $row['title']],
                    [
                        'game' => $row['game'],
                        'body' => $row['body'],
                        'type' => $row['type'],
                        'seeded_helpful' => (int) ($row['seeded_helpful'] ?? 0),
                        'seeded_author' => $row['seeded_author'] ?? null,
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['updated_at'] ?? $row['created_at'] ?? now(),
                    ],
                ));
            }

            $reviewDefinitions = $legacyReviews ?: [
                ['game' => 'Elden Ring', 'rating' => 5, 'body' => 'The kind of world you move into, not just play. Three runs in and I am still finding little details that make this place feel alive. The story trusts you to pay attention, the combat rewards patience, and the soundtrack? Still thinking about it.', 'hours_played' => 86, 'seeded_author' => 'mossy_moth'],
                ['game' => 'Cyberpunk 2077', 'rating' => 5, 'body' => 'Finally got the glow-up it deserved. Night City still has me checking every side street.', 'hours_played' => 73, 'seeded_author' => 'streetkid.exe'],
                ['game' => 'Hades II', 'rating' => 5, 'body' => 'One more run. Every single time. The weapons are a blast and the characters are already living in my head.', 'hours_played' => 41, 'seeded_author' => 'witchybits'],
                ['game' => 'Stardew Valley', 'rating' => 5, 'body' => 'My tiny farm is my happy place. Started a new save for the thousandth time; no notes.', 'hours_played' => 212, 'seeded_author' => 'turnip_enthusiast'],
            ];

            foreach ($reviewDefinitions as $row) {
                $gameName = $row['game'] ?? $row['game_name'];
                Review::unguarded(fn () => Review::firstOrCreate(
                    ['seeded_author' => $row['seeded_author'] ?? null, 'game_name' => $gameName],
                    [
                        'game_id' => Game::whereRaw('lower(name) = ?', [mb_strtolower($gameName)])->value('id'),
                        'rating' => (int) $row['rating'],
                        'body' => $row['body'],
                        'hours_played' => (int) ($row['hours_played'] ?? 0),
                        'created_at' => $row['created_at'] ?? now(),
                        'updated_at' => $row['created_at'] ?? now(),
                    ],
                ));
            }
        });
    }
}
