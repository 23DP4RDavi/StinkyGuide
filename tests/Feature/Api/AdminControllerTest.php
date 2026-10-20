<?php

namespace Tests\Feature\Api;

use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\GuideVote;
use App\Models\Review;
use App\Models\SavedGuide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('admin.initial_password', null);
        $this->seed();
    }

    public function test_admin_dashboard_returns_traffic_and_recent_community_content(): void
    {
        $admin = User::factory()->create()->forceFill(['is_admin' => true]);
        $admin->save();
        $guide = Guide::create([
            'user_id' => $admin->id,
            'game' => 'Bog Quest',
            'title' => 'A guide from the swamp',
            'body' => 'A helpful guide body.',
            'type' => 'tips',
        ]);
        $review = Review::create([
            'user_id' => $admin->id,
            'game_name' => 'Bog Quest',
            'rating' => 5,
            'body' => 'A test review.',
        ]);
        $comment = GuideComment::create([
            'user_id' => $admin->id,
            'guide_id' => $guide->id,
            'body' => 'A test comment.',
        ]);

        $this->actingAs($admin)->getJson('/api/games?private-query-marker=do-not-store')->assertOk();
        $this->postJson('/api/login', [
            'email' => 'private-body-marker@example.com',
            'password' => 'private-password-marker',
        ])->assertUnprocessable();

        $this->getJson('/api/admin')
            ->assertOk()
            ->assertJsonPath('traffic.requests24h', 2)
            ->assertJsonPath('traffic.players24h', 1)
            ->assertJsonFragment(['path' => 'api/games'])
            ->assertJsonFragment(['title' => 'A guide from the swamp'])
            ->assertJsonFragment(['game' => 'Bog Quest'])
            ->assertJsonCount(1, 'content.reviews')
            ->assertJsonPath('content.reviews.0.author', $admin->name)
            ->assertJsonFragment(['body' => 'A test comment.'])
            ->assertDontSee('private-query-marker')
            ->assertDontSee('private-body-marker')
            ->assertDontSee('private-password-marker');

        $this->assertDatabaseHas('traffic_events', [
            'path' => 'api/games',
            'method' => 'GET',
            'status' => 200,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('traffic_events', ['path' => 'api/games?private-query-marker=do-not-store']);
        $this->assertDatabaseHas('traffic_events', ['path' => 'api/login', 'status' => 422]);
        $this->assertModelExists($guide);
        $this->assertModelExists($review);
        $this->assertModelExists($comment);
    }

    public function test_admin_dashboard_returns_401_for_unauthenticated_players(): void
    {
        $this->getJson('/api/admin')->assertUnauthorized();
    }

    public function test_non_admin_players_receive_403_and_cannot_delete_guides(): void
    {
        $member = User::factory()->create();
        $guide = Guide::create([
            'user_id' => $member->id,
            'game' => 'Bog Quest',
            'title' => 'Keep this guide',
            'body' => 'This guide should remain.',
            'type' => 'tips',
        ]);
        $this->actingAs($member)
            ->getJson('/api/admin')
            ->assertForbidden();

        $this->deleteJson("/api/admin/guides/{$guide->id}")->assertForbidden();
        $this->assertModelExists($guide);
    }

    public function test_admin_can_delete_guides_reviews_and_comments(): void
    {
        $admin = User::factory()->create()->forceFill(['is_admin' => true]);
        $admin->save();
        $guide = Guide::create([
            'user_id' => $admin->id,
            'game' => 'Bog Quest',
            'title' => 'Remove this guide',
            'body' => 'This guide is no longer useful.',
            'type' => 'tips',
        ]);
        $commentOnGuide = GuideComment::create([
            'user_id' => $admin->id,
            'guide_id' => $guide->id,
            'body' => 'Cascade this comment with its guide.',
        ]);
        $vote = GuideVote::create(['user_id' => $admin->id, 'guide_id' => $guide->id]);
        $savedGuide = SavedGuide::create(['user_id' => $admin->id, 'guide_id' => $guide->id]);
        $commentGuide = Guide::create([
            'user_id' => $admin->id,
            'game' => 'Bog Quest',
            'title' => 'Comment host guide',
            'body' => 'A guide for the comment.',
            'type' => 'tips',
        ]);
        $review = Review::create([
            'user_id' => $admin->id,
            'game_name' => 'Bog Quest',
            'rating' => 1,
            'body' => 'This review is no longer useful.',
        ]);
        $comment = GuideComment::create([
            'user_id' => $admin->id,
            'guide_id' => $commentGuide->id,
            'body' => 'This comment is no longer useful.',
        ]);

        $this->actingAs($admin);
        $this->deleteJson("/api/admin/guides/{$guide->id}")->assertOk()->assertJsonPath('deleted', true);
        $this->deleteJson("/api/admin/reviews/{$review->id}")->assertOk()->assertJsonPath('deleted', true);
        $this->deleteJson("/api/admin/comments/{$comment->id}")->assertOk()->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('guides', ['id' => $guide->id]);
        $this->assertDatabaseMissing('guide_comments', ['id' => $commentOnGuide->id]);
        $this->assertDatabaseMissing('guide_votes', ['id' => $vote->id]);
        $this->assertDatabaseMissing('saved_guides', ['id' => $savedGuide->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('guide_comments', ['id' => $comment->id]);
    }
}
