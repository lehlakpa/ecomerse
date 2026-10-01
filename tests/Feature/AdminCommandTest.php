<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_be_created_with_a_seven_character_password(): void
    {
        $this->artisan('app:create-admin', ['username' => 'storeadmin'])
            ->expectsQuestion('Full name', 'Store Admin')
            ->expectsQuestion('Password (at least 7 characters)', 'pass123')
            ->expectsQuestion('Confirm password', 'pass123')
            ->assertSuccessful();
        $user = User::where('username', 'storeadmin')->firstOrFail();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('pass123', $user->password));
    }

    public function test_a_six_character_admin_password_is_rejected(): void
    {
        $this->artisan('app:create-admin', ['username' => 'storeadmin'])
            ->expectsQuestion('Full name', 'Store Admin')
            ->expectsQuestion('Password (at least 7 characters)', 'pass12')
            ->expectsQuestion('Confirm password', 'pass12')
            ->expectsOutput('The password field must be at least 7 characters.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_admin_username_is_rejected(): void
    {
        User::factory()->create(['username' => 'existing']);
        $this->artisan('app:create-admin', ['username' => 'existing'])
            ->expectsQuestion('Full name', 'Store Admin')
            ->expectsQuestion('Password (at least 7 characters)', 'secure-password-123')
            ->expectsQuestion('Confirm password', 'secure-password-123')
            ->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }
}
