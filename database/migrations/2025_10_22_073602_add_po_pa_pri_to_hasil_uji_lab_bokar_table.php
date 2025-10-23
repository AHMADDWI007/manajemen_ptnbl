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
        // Fungsi ini akan dijalankan saat 'php artisan migrate'
        Schema::table('hasil_uji_lab_bokar', function (Blueprint $table) {
            // Tambahkan kolom po, pa, pri setelah kolom 'ask'
            // Ganti 'ask' dengan nama kolom terakhirmu jika berbeda
            $table->decimal('po', 8, 2)->nullable()->after('ask');
            $table->decimal('pa', 8, 2)->nullable()->after('po');
            $table->decimal('pri', 8, 2)->nullable()->after('pa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Fungsi ini akan dijalankan jika kamu melakukan 'rollback'
        Schema::table('hasil_uji_lab_bokar', function (Blueprint $table) {
            // Hapus kolom jika migrasi dibatalkan
            $table->dropColumn(['po', 'pa', 'pri']);
        });
    }
};




