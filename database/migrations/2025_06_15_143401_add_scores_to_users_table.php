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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('wins')->default(0)->after('email_verified_at');
            $table->integer('losses')->default(0)->after('wins');
            // Anda bisa juga menambahkan 'draws' jika mau
            // $table->integer('draws')->default(0)->after('losses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wins', 'losses']);
            // $table->dropColumn('draws'); // Jika Anda menambahkan ini
        });
    }
};