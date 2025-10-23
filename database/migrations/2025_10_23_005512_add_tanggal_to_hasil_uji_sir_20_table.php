<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_uji_sir_20', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->after('id');
        });

        // Update semua baris lama dengan tanggal hari ini
        DB::table('hasil_uji_sir_20')->update(['tanggal' => date('Y-m-d')]);

        // Ubah kolom menjadi NOT NULL
        Schema::table('hasil_uji_sir_20', function (Blueprint $table) {
            $table->date('tanggal')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('hasil_uji_sir_20', function (Blueprint $table) {
            $table->dropColumn('tanggal');
        });
    }
};