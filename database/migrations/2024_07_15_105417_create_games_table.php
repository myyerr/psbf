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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_one_id')->constrained()->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('player_two_id')->nullable()->constrained()->references('id')->on('users')->cascadeOnDelete();

            $table->string('state')->nullable();

            $table->decimal('player_one_credit')->default(0);
            $table->decimal('player_two_credit')->default(0);
            $table->decimal('match_reward')->default(0);
            $table->decimal('match_fee')->default(0);

            $table->boolean('status')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
