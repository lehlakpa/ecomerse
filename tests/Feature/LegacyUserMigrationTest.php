<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyUserMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_users_are_preserved_and_new_username_accounts_can_be_created(): void
    {
        Schema::drop('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        DB::table('users')->insert(['id' => 7, 'name' => 'Existing User', 'email' => 'existing@example.test', 'password' => Hash::make('password')]);
        $migration = require database_path('migrations/2026_09_27_030715_align_users_with_username_authentication.php');
        $migration->up();
        $this->assertDatabaseHas('users', ['id' => 7, 'username' => 'user_7', 'email' => 'existing@example.test', 'role' => 'customer']);
        $user = User::factory()->create(['username' => 'newuser']);
        $this->assertNull($user->fresh()->email);
        $migration->up();
        $this->assertDatabaseCount('users', 2);
    }
}
