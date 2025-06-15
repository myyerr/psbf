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
        Schema::table('games', function (Blueprint $table) {
            // Menambahkan kolom winner_id (nullable, karena game bisa berakhir seri atau belum selesai)
            // Menggunakan unsignedBigInteger karena ini adalah foreign key ke tabel 'users'
            // Tambahkan foreign key constraint jika Anda ingin menjaga integritas referensial
            $table->unsignedBigInteger('winner_id')->nullable()->after('status');
            $table->foreign('winner_id')->references('id')->on('users')->onDelete('set null'); // Opsional: Tambahkan foreign key

            // Menambahkan kolom winning_line (nullable, karena tidak selalu ada pemenang)
            $table->integer('winning_line')->nullable()->after('winner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Hapus foreign key terlebih dahulu jika ada
            $table->dropForeign(['winner_id']);
            // Hapus kolom-kolom
            $table->dropColumn(['winner_id', 'winning_line']);
        });
    }
};