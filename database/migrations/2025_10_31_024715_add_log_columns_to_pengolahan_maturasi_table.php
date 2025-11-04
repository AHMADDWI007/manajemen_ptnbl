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
        Schema::table('pengolahan_maturasi', function (Blueprint $table) {
            // TAMBAHKAN 2 BARIS INI
            $table->date('tgl_masuk_log')->nullable()->after('keterangan');
            $table->integer('umur_log')->default(0)->after('tgl_masuk_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengolahan_maturasi', function (Blueprint $table) {
            // TAMBAHKAN 2 BARIS INI
            $table->dropColumn('tgl_masuk_log');
            $table->dropColumn('umur_log');
        });
    }
};