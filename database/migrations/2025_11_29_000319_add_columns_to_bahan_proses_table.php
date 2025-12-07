<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan_proses', function (Blueprint $table) {
            if (!Schema::hasColumn('bahan_proses', 'tanggal')) {
                $table->date('tanggal')->after('id')->nullable();
            }

            if (!Schema::hasColumn('bahan_proses', 'saldo_awal')) {
                $table->decimal('saldo_awal', 15, 2)->default(0)->after('uraian');
            }

            if (!Schema::hasColumn('bahan_proses', 'rekfif')) {
                $table->decimal('rekfif', 15, 2)->default(0)->after('produksi_sir20');
            }
        });
    }


    public function down(): void
    {
        Schema::table('bahan_proses', function (Blueprint $table) {
            // Menghapus kolom jika di-rollback
            $table->dropColumn(['tanggal', 'saldo_awal', 'rekfif']);
        });
    }
};