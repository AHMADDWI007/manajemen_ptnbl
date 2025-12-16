<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produksi_sir20', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            
            // 1. Remahan
            $table->string('remah_ruang_maturasi')->nullable();
            $table->decimal('remah_berat', 15, 2)->default(0);
            $table->date('remah_tgl_masuk')->nullable();
            $table->integer('remah_umur')->default(0);
            
            // 2. Pengeringan (Dryer)
            $table->time('dryer_jam_start')->nullable();
            $table->integer('dryer_troli_masuk')->default(0);
            $table->string('dryer_aktual_temp')->nullable();
            $table->string('dryer_waktu_cycle')->nullable();
            
            // Bahan Bakar & Utilitas
            $table->decimal('bb_solar', 15, 2)->default(0);
            $table->decimal('bb_batubara', 15, 2)->default(0);
            $table->decimal('bb_cangkang', 15, 2)->default(0);
            $table->decimal('jam_genset', 8, 2)->default(0);
            $table->decimal('r_solar_ton', 15, 2)->default(0);
            $table->decimal('listrik_kwh', 15, 2)->default(0); // Baru
            
            $table->integer('dryer_troli_keluar')->default(0);
            $table->time('dryer_jam_stop')->nullable();
            $table->decimal('dryer_jam_jalan', 8, 2)->default(0);
            
            // 3. Produksi (Press)
            $table->integer('jml_bales_press')->default(0);
            
            // --- INI NILAI KUNCI YANG DIAMBIL WIP ---
            $table->decimal('kg_press', 15, 2)->default(0); 
            // ----------------------------------------

            $table->decimal('capacity_per_jam', 15, 2)->default(0);
            $table->decimal('jam_kerja', 8, 2)->default(0);
            $table->decimal('produktivitas', 15, 2)->default(0);
            $table->decimal('kg_sir_cake', 15, 2)->default(0);
            
            // Quality
            $table->string('kontaminasi_logam')->nullable();
            $table->decimal('berat_kontaminan', 8, 2)->default(0); // Baru
            
            // 4. Packing
            $table->integer('pack_pallet_sw')->default(0);
            $table->string('pack_nomor')->nullable();
            
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produksi_sir20');
    }
};