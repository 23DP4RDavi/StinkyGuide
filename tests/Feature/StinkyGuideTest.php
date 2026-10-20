<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StinkyGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_homepage_serves_the_vue_application_and_legacy_content_is_imported(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('StinkyGuide')
            ->assertSee('id="app"', false);

        $this->getJson('/api/games')->assertOk()
            ->assertJsonCount(39, 'games')
            ->assertJsonFragment(['name' => 'Hollow Knight'])
            ->assertJsonFragment(['name' => 'A Short Hike']);
        $this->getJson('/api/guides')->assertOk()->assertJsonCount(8, 'guides');
        $this->getJson('/api/reviews')->assertOk()->assertJsonPath('summary.count', 4);
    }

    public function test_players_can_register_log_in_update_their_profile_and_sign_out(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'guide_player',
            'email' => 'PLAYER@example.com',
            'password' => 'remember-this-password',
        ])->assertCreated()->assertJsonPath('user.name', 'guide_player');

        $this->assertAuthenticated();
        $user = User::firstOrFail();
        $this->assertTrue(Hash::check('remember-this-password', $user->password));
        $this->assertNotSame('remember-this-password', $user->password);

        $response->assertJsonPath('user.email', 'player@example.com');
        $this->putJson('/api/profile', ['name' => 'guide_player_2', 'bio' => 'Here for the cozy farming games.'])
            ->assertOk()
            ->assertJsonPath('user.bio', 'Here for the cozy farming games.');

        $this->putJson('/api/interests', ['interests' => ['Cozy', 'Indie']])
            ->assertOk()
            ->assertJsonPath('interests', ['Cozy', 'Indie']);

        $this->postJson('/api/logout')->assertOk();
        $this->assertGuest();

        $this->postJson('/api/login', ['email' => 'player@example.com', 'password' => 'remember-this-password'])
            ->assertOk()
            ->assertJsonPath('user.name', 'guide_player_2')
            ->assertJsonPath('interests', ['Cozy', 'Indie']);
        $this->assertAuthenticated();
    }

    public function test_players_can_manage_their_game_library_and_saved_guides(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $game = Game::where('name', 'Elden Ring')->firstOrFail();
        $guide = Guide::where('game', 'ELDEN RING')->firstOrFail();

        $this->putJson("/api/games/{$game->id}/library", ['status' => 'playing'])
            ->assertOk()->assertJsonPath('status', 'playing');
        $this->getJson('/api/library')
            ->assertOk()->assertJsonPath('library.0.status', 'playing');

        $this->postJson("/api/guides/{$guide->id}/helpful")
            ->assertOk()->assertJsonPath('helpfulByMe', true);
        $this->postJson("/api/guides/{$guide->id}/helpful")
            ->assertOk()->assertJsonPath('helpfulByMe', false);

        $this->postJson("/api/guides/{$guide->id}/save")
            ->assertOk()->assertJsonPath('saved', true);
        $this->getJson('/api/saved-guides')
            ->assertOk()->assertJsonPath('guides.0.id', $guide->id);

        $this->postJson("/api/guides/{$guide->id}/comments", ['body' => 'gg, this helped'])
            ->assertCreated()->assertJsonPath('comment.author', $user->name);
        $this->getJson("/api/guides/{$guide->id}")
            ->assertOk()->assertJsonPath('guide.commentsCount', 1);

        $this->putJson("/api/games/{$game->id}/library", ['status' => null])
            ->assertOk()->assertJsonPath('status', null);
        $this->assertDatabaseMissing('game_libraries', ['user_id' => $user->id, 'game_id' => $game->id]);
    }

    public function test_recommendations_rank_games_by_the_players_selected_interests(): void
    {
        $this->actingAs(User::factory()->create());

        $this->putJson('/api/interests', ['interests' => ['Cozy', 'Indie']])
            ->assertOk()
            ->assertJsonPath('interests', ['Cozy', 'Indie']);

        $this->getJson('/api/games')
            ->assertOk()
            ->assertJsonPath('games.0.match', 100)
            ->assertJsonFragment(['name' => 'Dredge', 'match' => 100])
            ->assertJsonFragment(['name' => 'Stardew Valley', 'match' => 100])
            ->assertJsonFragment(['name' => 'Cyberpunk 2077', 'match' => 0]);
    }

    public function test_players_can_publish_guides_and_reviews_and_see_updated_ratings(): void
    {
        $this->actingAs(User::factory()->create());

        $guide = $this->postJson('/api/guides', [
            'game' => 'Cosmic Soup Odyssey',
            'title' => 'My first cosmic soup walkthrough',
            'body' => 'A detailed guide that explains all the essential steps for beating the first boss.',
            'type' => 'walkthrough',
        ])->assertCreated()
            ->assertJsonPath('guide.game', 'COSMIC SOUP ODYSSEY')
            ->assertJsonPath('guide.type', 'walkthrough')
            ->assertJsonPath('guide.tag', 'WALKTHROUGH');

        $game = Game::where('name', 'Elden Ring')->firstOrFail();
        $this->postJson('/api/reviews', [
            'game' => 'Elden Ring',
            'rating' => 4,
            'body' => 'The world is incredible and I only fell off a cliff a few times.',
            'hoursPlayed' => 55,
        ])->assertCreated()->assertJsonPath('review.hoursPlayed', 55);

        $this->getJson('/api/games')->assertOk()
            ->assertJsonPath('games.0.name', 'Elden Ring')
            ->assertJsonPath('games.0.match', 96);
        $this->getJson("/api/games/{$game->id}")
            ->assertOk()->assertJsonPath('game.reviewCount', 2);
        $this->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('guides', 9)
            ->assertJsonPath('players', 1);
        $this->assertDatabaseHas('guides', [
            'game' => 'COSMIC SOUP ODYSSEY',
            'title' => 'My first cosmic soup walkthrough',
            'type' => 'walkthrough',
        ]);
        $this->assertDatabaseHas('reviews', ['game_name' => 'Elden Ring', 'hours_played' => 55]);
        $guide->assertJsonPath('guide.author', $this->app['auth']->user()->name);
    }
}
