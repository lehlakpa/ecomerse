<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_be_created_with_a_secure_password(): void
    {
        $this->artisan('app:create-admin', ['username' => 'storeadmin'])
            ->expectsQuestion('Full name', 'Store Admin')
            ->expectsQuestion('Password (at least 12 characters)', 'secure-password-123')
            ->expectsQuestion('Confirm password', 'secure-password-123')
            ->assertSuccessful();
        $user = User::where('username', 'storeadmin')->firstOrFail();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('secure-password-123', $user->password));
    }

    public function test_duplicate_admin_username_is_rejected(): void
    {
        User::factory()->create(['username' => 'existing']);
        $this->artisan('app:create-admin', ['username' => 'existing'])
            ->expectsQuestion('Full name', 'Store Admin')
            ->expectsQuestion('Password (at least 12 characters)', 'secure-password-123')
            ->expectsQuestion('Confirm password', 'secure-password-123')
            ->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }
}
