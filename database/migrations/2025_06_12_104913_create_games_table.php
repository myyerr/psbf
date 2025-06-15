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
            $table->foreignId('player_x_id')->constrained('users'); // Pemain X
            $table->foreignId('player_o_id')->constrained('users'); // Pemain O
            $table->string('status')->default('pending'); // pending, playing, finished, cancelled
            $table->foreignId('current_turn_user_id')->nullable()->constrained('users'); // Siapa giliran saat ini
            $table->foreignId('winner_id')->nullable()->constrained('users'); // Siapa pemenangnya
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