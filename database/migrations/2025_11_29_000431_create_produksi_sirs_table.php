<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi_sirs', function (Blueprint $table) {
            $table->id();
            $table->string('uraian'); // Kunci utama (misal: 'Di Gudang SIR' atau 'Mutu Prima')
            
            // Kolom untuk Tabel IV (Stock Gudang)
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('masuk', 15, 2)->default(0);
            // 'total' biasanya dihitung (saldo_awal + masuk), tapi bisa disimpan jika mau
            $table->decimal('prod_bln_lalu', 15, 2)->default(0);
            $table->decimal('prod_sd_hi', 15, 2)->default(0);
            $table->decimal('pengiriman', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            $table->decimal('ptnb', 15, 2)->default(0);
            $table->decimal('total_i_sd_iv', 15, 2)->default(0);

            // Kolom untuk Tabel VI (Mutu)
            $table->decimal('kg', 15, 2)->default(0);
            $table->integer('pallet')->default(0);

            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_sirs');
    }
};