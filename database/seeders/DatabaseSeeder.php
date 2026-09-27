<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('local') && ! User::where('username', 'demo')->exists()) {
            User::factory()->create(['name' => 'Demo Customer', 'username' => 'demo']);
        }
    }
}
