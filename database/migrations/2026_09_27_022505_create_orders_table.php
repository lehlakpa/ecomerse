<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->string('phone');
            $table->string('location');

            $table->string('size')->nullable();
            $table->string('color')->nullable();

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            $table->enum('status', [
                'pending',
                'confirmed',
                'shipped',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
