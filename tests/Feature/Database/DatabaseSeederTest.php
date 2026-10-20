<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('admin.initial_password', null);
        $this->seed();
    }

    public function test_seeder_creates_the_configured_admin_with_a_hashed_password(): void
    {
        config()->set('admin.initial_password', 'test-admin-password-that-is-long');

        $this->seed();

        $admin = User::where('email', 'admin@admin.lv')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('test-admin-password-that-is-long', $admin->password));
        $this->assertNotSame('test-admin-password-that-is-long', $admin->password);
        $this->postJson('/api/login', [
            'email' => 'admin@admin.lv',
            'password' => 'test-admin-password-that-is-long',
        ])->assertOk()->assertJsonPath('user.is_admin', true);
        $this->getJson('/api/session')->assertOk()->assertJsonPath('user.is_admin', true);
    }

    public function test_seeder_promotes_existing_admin_email_without_resetting_its_password(): void
    {
        $existingAccount = User::factory()->create([
            'email' => 'admin@admin.lv',
            'password' => 'existing-account-password',
        ]);
        config()->set('admin.initial_password', 'do-not-replace-the-existing-password');

        $this->seed();

        $existingAccount->refresh();
        $this->assertTrue($existingAccount->is_admin);
        $this->assertTrue(Hash::check('existing-account-password', $existingAccount->password));
        $this->assertFalse(Hash::check('do-not-replace-the-existing-password', $existingAccount->password));
    }

    public function test_seeder_does_not_promote_existing_account_without_a_configured_initial_password(): void
    {
        $existingAccount = User::factory()->create(['email' => 'admin@admin.lv']);

        $this->seed();

        $this->assertFalse($existingAccount->fresh()->is_admin);
    }

    public function test_seeder_does_not_create_an_admin_without_a_configured_initial_password(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'admin@admin.lv']);
    }
}
