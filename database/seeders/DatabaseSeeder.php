<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Guide;
use App\Models\Review;
use App\Models\User;
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

        $adminEmail = strtolower((string) config('admin.email'));
        $adminPassword = config('admin.initial_password');

        DB::transaction(function () use ($legacyGames, $legacyGuides, $legacyReviews, $adminEmail, $adminPassword): void {
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

            $additionalGames = [
                ['name' => 'Hollow Knight', 'genre' => 'Metroidvania · Action', 'steam_app_id' => 367520, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 94, 'platform' => 'PC · SWITCH'],
                ['name' => 'Hollow Knight: Silksong', 'genre' => 'Metroidvania · Action', 'steam_app_id' => 1030300, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 93, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Terraria', 'genre' => 'Sandbox · Adventure', 'steam_app_id' => 105600, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 92, 'platform' => 'EVERYWHERE'],
                ['name' => 'Celeste', 'genre' => 'Platformer · Indie', 'steam_app_id' => 504230, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Portal 2', 'genre' => 'Puzzle · Adventure', 'steam_app_id' => 620, 'tags_json' => '["Indie","Strategy"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'The Witcher 3: Wild Hunt', 'genre' => 'RPG · Open world', 'steam_app_id' => 292030, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 96, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Sekiro: Shadows Die Twice', 'genre' => 'Action · Adventure', 'steam_app_id' => 814380, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Monster Hunter: World', 'genre' => 'Action RPG · Co-op', 'steam_app_id' => 582010, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Subnautica', 'genre' => 'Survival · Exploration', 'steam_app_id' => 264710, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Outer Wilds', 'genre' => 'Exploration · Mystery', 'steam_app_id' => 753640, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 95, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Disco Elysium', 'genre' => 'RPG · Detective', 'steam_app_id' => 632470, 'tags_json' => '["RPG","Indie"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Balatro', 'genre' => 'Deckbuilder · Roguelike', 'steam_app_id' => 2379780, 'tags_json' => '["Strategy","Indie"]', 'match_score' => 92, 'platform' => 'EVERYWHERE'],
                ['name' => 'RimWorld', 'genre' => 'Colony sim · Strategy', 'steam_app_id' => 294100, 'tags_json' => '["Strategy","Indie"]', 'match_score' => 89, 'platform' => 'PC'],
                ['name' => 'Factorio', 'genre' => 'Factory sim · Strategy', 'steam_app_id' => 427520, 'tags_json' => '["Strategy","Indie"]', 'match_score' => 89, 'platform' => 'PC · SWITCH'],
                ['name' => 'Dave the Diver', 'genre' => 'Adventure · Fishing', 'steam_app_id' => 1868140, 'tags_json' => '["Cozy","Indie"]', 'match_score' => 88, 'platform' => 'PC · SWITCH'],
                ['name' => 'Spiritfarer', 'genre' => 'Cozy · Management', 'steam_app_id' => 972660, 'tags_json' => '["Cozy","Indie"]', 'match_score' => 90, 'platform' => 'EVERYWHERE'],
                ['name' => 'Phasmophobia', 'genre' => 'Horror · Co-op', 'steam_app_id' => 739630, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 84, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Lethal Company', 'genre' => 'Horror · Co-op', 'steam_app_id' => 1966720, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 87, 'platform' => 'PC'],
                ['name' => 'Deep Rock Galactic', 'genre' => 'Co-op · Shooter', 'steam_app_id' => 548430, 'tags_json' => '["Adventure","Strategy"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Cuphead', 'genre' => 'Platformer · Boss rush', 'steam_app_id' => 268910, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'No Man’s Sky', 'genre' => 'Exploration · Survival', 'steam_app_id' => 275850, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 87, 'platform' => 'EVERYWHERE'],
                ['name' => 'Ori and the Will of the Wisps', 'genre' => 'Metroidvania · Adventure', 'steam_app_id' => 1057090, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Overcooked! 2', 'genre' => 'Co-op · Cooking', 'steam_app_id' => 728880, 'tags_json' => '["Cozy","Indie"]', 'match_score' => 86, 'platform' => 'EVERYWHERE'],
                ['name' => 'The Binding of Isaac: Rebirth', 'genre' => 'Roguelike · Action', 'steam_app_id' => 250900, 'tags_json' => '["Indie","Strategy"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'A Short Hike', 'genre' => 'Cozy · Exploration', 'steam_app_id' => 1055540, 'tags_json' => '["Cozy","Indie"]', 'match_score' => 87, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Return of the Obra Dinn', 'genre' => 'Puzzle · Mystery', 'steam_app_id' => 653530, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 89, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Inscryption', 'genre' => 'Card game · Horror', 'steam_app_id' => 1092790, 'tags_json' => '["Strategy","Indie"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Valheim', 'genre' => 'Survival · Co-op', 'steam_app_id' => 892970, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Vampire Survivors', 'genre' => 'Roguelike · Action', 'steam_app_id' => 1794680, 'tags_json' => '["Indie","Strategy"]', 'match_score' => 88, 'platform' => 'EVERYWHERE'],
                ['name' => 'Grounded', 'genre' => 'Survival · Co-op', 'steam_app_id' => 962130, 'tags_json' => '["Adventure","Cozy"]', 'match_score' => 85, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Counter-Strike 2', 'genre' => 'Tactical · Competitive shooter', 'steam_app_id' => 730, 'tags_json' => '["Shooter","Strategy"]', 'match_score' => 98, 'platform' => 'PC'],
                ['name' => "Tom Clancy's Rainbow Six Siege", 'genre' => 'Tactical · Competitive shooter', 'steam_app_id' => 359550, 'tags_json' => '["Shooter","Strategy"]', 'match_score' => 95, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Apex Legends', 'genre' => 'Battle royale · Hero shooter', 'steam_app_id' => 1172470, 'tags_json' => '["Shooter"]', 'match_score' => 94, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Overwatch 2', 'genre' => 'Team-based · Hero shooter', 'steam_app_id' => 2357570, 'tags_json' => '["Shooter","Strategy"]', 'match_score' => 92, 'platform' => 'PC · CONSOLE'],
                ['name' => 'PUBG: BATTLEGROUNDS', 'genre' => 'Battle royale · Shooter', 'steam_app_id' => 578080, 'tags_json' => '["Shooter"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Team Fortress 2', 'genre' => 'Team-based · Class shooter', 'steam_app_id' => 440, 'tags_json' => '["Shooter","Strategy"]', 'match_score' => 90, 'platform' => 'PC'],
                ['name' => 'Warframe', 'genre' => 'Action · Co-op shooter', 'steam_app_id' => 230410, 'tags_json' => '["Shooter","RPG"]', 'match_score' => 94, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Destiny 2', 'genre' => 'Action · Online shooter', 'steam_app_id' => 1085660, 'tags_json' => '["Shooter","RPG"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'THE FINALS', 'genre' => 'Competitive · Arena shooter', 'steam_app_id' => 2073850, 'tags_json' => '["Shooter"]', 'match_score' => 89, 'platform' => 'PC · CONSOLE'],
                ['name' => 'HELLDIVERS 2', 'genre' => 'Co-op · Sci-fi shooter', 'steam_app_id' => 553850, 'tags_json' => '["Shooter","Adventure"]', 'match_score' => 92, 'platform' => 'PC · PS5'],
                ['name' => 'Left 4 Dead 2', 'genre' => 'Co-op · Zombie shooter', 'steam_app_id' => 550, 'tags_json' => '["Shooter","Adventure"]', 'match_score' => 90, 'platform' => 'PC'],
                ['name' => 'Borderlands 3', 'genre' => 'Action RPG · Looter shooter', 'steam_app_id' => 397540, 'tags_json' => '["Shooter","RPG"]', 'match_score' => 87, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Grand Theft Auto V', 'genre' => 'Open world · Action', 'steam_app_id' => 271590, 'tags_json' => '["Adventure","Shooter"]', 'match_score' => 95, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Red Dead Redemption 2', 'genre' => 'Open world · Adventure', 'steam_app_id' => 1174180, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 97, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Forza Horizon 5', 'genre' => 'Open world · Racing', 'steam_app_id' => 1551360, 'tags_json' => '["Racing"]', 'match_score' => 93, 'platform' => 'PC · XBOX'],
                ['name' => 'Hogwarts Legacy', 'genre' => 'Action RPG · Fantasy', 'steam_app_id' => 990080, 'tags_json' => '["RPG","Adventure"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'God of War', 'genre' => 'Action · Mythology', 'steam_app_id' => 1593500, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 93, 'platform' => 'PC · PLAYSTATION'],
                ['name' => 'Marvel’s Spider-Man Remastered', 'genre' => 'Open world · Superhero', 'steam_app_id' => 1817070, 'tags_json' => '["Adventure"]', 'match_score' => 94, 'platform' => 'PC · PLAYSTATION'],
                ['name' => 'Resident Evil 4', 'genre' => 'Survival horror · Action', 'steam_app_id' => 2050650, 'tags_json' => '["Shooter","Adventure"]', 'match_score' => 94, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Dead by Daylight', 'genre' => 'Multiplayer · Survival horror', 'steam_app_id' => 381210, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Dota 2', 'genre' => 'Competitive · MOBA', 'steam_app_id' => 570, 'tags_json' => '["Strategy"]', 'match_score' => 94, 'platform' => 'PC'],
                ['name' => 'Sid Meier’s Civilization VI', 'genre' => 'Turn-based · 4X strategy', 'steam_app_id' => 289070, 'tags_json' => '["Strategy"]', 'match_score' => 93, 'platform' => 'PC · CONSOLE'],
                ['name' => 'The Sims 4', 'genre' => 'Life sim · Creative', 'steam_app_id' => 1222670, 'tags_json' => '["Simulation","Cozy"]', 'match_score' => 91, 'platform' => 'PC'],
                ['name' => 'Cities: Skylines II', 'genre' => 'City builder · Simulation', 'steam_app_id' => 949230, 'tags_json' => '["Simulation","Strategy"]', 'match_score' => 86, 'platform' => 'PC'],
                ['name' => 'It Takes Two', 'genre' => 'Co-op · Platform adventure', 'steam_app_id' => 1426210, 'tags_json' => '["Cozy","Adventure"]', 'match_score' => 93, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Among Us', 'genre' => 'Social deduction · Party', 'steam_app_id' => 945360, 'tags_json' => '["Strategy","Indie"]', 'match_score' => 89, 'platform' => 'EVERYWHERE'],
                ['name' => 'Fall Guys', 'genre' => 'Party · Platformer', 'steam_app_id' => 1097150, 'tags_json' => '["Indie","Cozy"]', 'match_score' => 87, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Euro Truck Simulator 2', 'genre' => 'Driving · Simulation', 'steam_app_id' => 227300, 'tags_json' => '["Simulation","Racing"]', 'match_score' => 90, 'platform' => 'PC'],
                ['name' => 'PowerWash Simulator', 'genre' => 'Casual · Simulation', 'steam_app_id' => 1290000, 'tags_json' => '["Simulation","Cozy"]', 'match_score' => 89, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Palworld', 'genre' => 'Open world · Creature survival', 'steam_app_id' => 1623730, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 89, 'platform' => 'PC · XBOX'],
                ['name' => 'Rust', 'genre' => 'Online · Survival', 'steam_app_id' => 252490, 'tags_json' => '["Adventure","Shooter"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Sons Of The Forest', 'genre' => 'Survival · Horror', 'steam_app_id' => 1326470, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 90, 'platform' => 'PC'],
                ['name' => 'Sea of Thieves', 'genre' => 'Open world · Co-op adventure', 'steam_app_id' => 1172620, 'tags_json' => '["Adventure"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Monster Hunter Rise', 'genre' => 'Action RPG · Co-op', 'steam_app_id' => 1446780, 'tags_json' => '["RPG","Adventure"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Persona 5 Royal', 'genre' => 'JRPG · Life sim', 'steam_app_id' => 1687950, 'tags_json' => '["RPG","Strategy"]', 'match_score' => 95, 'platform' => 'PC · CONSOLE'],
                ['name' => 'House Flipper 2', 'genre' => 'Creative · Simulation', 'steam_app_id' => 1190970, 'tags_json' => '["Simulation","Cozy"]', 'match_score' => 87, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Age of Empires IV: Anniversary Edition', 'genre' => 'Real-time · Strategy', 'steam_app_id' => 1466860, 'tags_json' => '["Strategy"]', 'match_score' => 91, 'platform' => 'PC · XBOX'],
                ['name' => 'Hades', 'genre' => 'Roguelike · Action', 'steam_app_id' => 1145360, 'tags_json' => '["Indie","Strategy"]', 'match_score' => 94, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Satisfactory', 'genre' => 'Factory building · Simulation', 'steam_app_id' => 526870, 'tags_json' => '["Simulation","Strategy"]', 'match_score' => 92, 'platform' => 'PC'],
                ['name' => 'Stellaris', 'genre' => 'Grand strategy · Sci-fi', 'steam_app_id' => 281990, 'tags_json' => '["Strategy"]', 'match_score' => 90, 'platform' => 'PC'],
                ['name' => 'Brotato', 'genre' => 'Arena survival · Roguelike', 'steam_app_id' => 1942280, 'tags_json' => '["Indie","Strategy"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Brawlhalla', 'genre' => 'Platform fighter · Party', 'steam_app_id' => 291550, 'tags_json' => '["Indie","Adventure"]', 'match_score' => 86, 'platform' => 'EVERYWHERE'],
                ['name' => 'Subnautica: Below Zero', 'genre' => 'Survival · Exploration', 'steam_app_id' => 848450, 'tags_json' => '["Adventure","Cozy"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Resident Evil Village', 'genre' => 'Survival horror · Action', 'steam_app_id' => 1196590, 'tags_json' => '["Shooter","Adventure"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Sid Meier’s Civilization VII', 'genre' => 'Turn-based · 4X strategy', 'steam_app_id' => 1295660, 'tags_json' => '["Strategy"]', 'match_score' => 89, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Pacific Drive', 'genre' => 'Survival · Driving adventure', 'steam_app_id' => 1458140, 'tags_json' => '["Racing","Adventure"]', 'match_score' => 87, 'platform' => 'PC · PLAYSTATION'],
                ['name' => 'Manor Lords', 'genre' => 'City builder · Strategy', 'steam_app_id' => 1363080, 'tags_json' => '["Strategy","Simulation"]', 'match_score' => 90, 'platform' => 'PC'],
                ['name' => 'Cult of the Lamb', 'genre' => 'Roguelike · Colony sim', 'steam_app_id' => 1313140, 'tags_json' => '["Indie","Strategy"]', 'match_score' => 91, 'platform' => 'PC · CONSOLE'],
                ['name' => 'TUNIC', 'genre' => 'Action · Exploration puzzle', 'steam_app_id' => 553420, 'tags_json' => '["Adventure","Indie"]', 'match_score' => 89, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Need for Speed Heat', 'genre' => 'Street racing · Open world', 'steam_app_id' => 1222680, 'tags_json' => '["Racing"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'EA SPORTS FC 25', 'genre' => 'Football · Sports', 'steam_app_id' => 2669320, 'tags_json' => '["Sports"]', 'match_score' => 87, 'platform' => 'PC · CONSOLE'],
                ['name' => 'NBA 2K25', 'genre' => 'Basketball · Sports', 'steam_app_id' => 2878980, 'tags_json' => '["Sports"]', 'match_score' => 84, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Farming Simulator 25', 'genre' => 'Farming · Simulation', 'steam_app_id' => 2300320, 'tags_json' => '["Simulation","Cozy"]', 'match_score' => 88, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Crusader Kings III', 'genre' => 'Grand strategy · Role-playing', 'steam_app_id' => 1158310, 'tags_json' => '["Strategy","RPG"]', 'match_score' => 90, 'platform' => 'PC · CONSOLE'],
                ['name' => 'Planet Zoo', 'genre' => 'Zoo management · Simulation', 'steam_app_id' => 703080, 'tags_json' => '["Simulation","Cozy"]', 'match_score' => 88, 'platform' => 'PC'],
                ['name' => 'Oxygen Not Included', 'genre' => 'Colony sim · Survival', 'steam_app_id' => 457140, 'tags_json' => '["Simulation","Strategy"]', 'match_score' => 89, 'platform' => 'PC'],
                ['name' => 'Black Myth: Wukong', 'genre' => 'Action RPG · Mythology', 'steam_app_id' => 2358720, 'tags_json' => '["Adventure","RPG"]', 'match_score' => 93, 'platform' => 'PC · PLAYSTATION'],
                ['name' => 'Starfield', 'genre' => 'Sci-fi · Open-world RPG', 'steam_app_id' => 1716740, 'tags_json' => '["RPG","Adventure"]', 'match_score' => 85, 'platform' => 'PC · XBOX'],
            ];

            foreach ([...$gameDefinitions, ...$additionalGames] as $row) {
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

            $admin = User::query()->whereRaw('lower(email) = ?', [$adminEmail])->first();
            $hasSecureInitialPassword = is_string($adminPassword) && mb_strlen($adminPassword) >= 16;

            if ($admin && $hasSecureInitialPassword) {
                $admin->forceFill(['is_admin' => true])->save();
            } elseif (! $admin && $hasSecureInitialPassword) {
                User::create([
                    'name' => 'StinkyGuide Admin',
                    'email' => $adminEmail,
                    'password' => $adminPassword,
                ])->forceFill(['is_admin' => true])->save();
            } else {
                $this->command?->warn('Admin account not created or promoted. Set ADMIN_INITIAL_PASSWORD to a unique password of at least 16 characters, then run php artisan db:seed.');
            }
        });
    }
}
