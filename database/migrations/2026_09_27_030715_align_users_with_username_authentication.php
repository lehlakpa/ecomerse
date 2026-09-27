<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('username')->nullable()->unique();
            });
            DB::table('users')->orderBy('id')->each(function (object $user): void {
                DB::table('users')->where('id', $user->id)->update(['username' => 'user_'.$user->id]);
            });
        }
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('phone')->nullable());
        }
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('customer'));
        }
        if (Schema::hasColumn('users', 'email')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('email')->nullable()->change());
        }
    }

    public function down(): void
    {
        // Preserve account identifiers and roles when rolling back this compatibility migration.
    }
};
