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
        Schema::create('produksi_sir_barus', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal'); // Tanggal Laporan
            $table->tinyInteger('shift'); // 1, 2, atau 3

            // --- 1. REMAHAN YANG DIPROSES ---
            $table->string('remah_ruang_maturasi')->nullable(); // 1. Ruang Maturasi
            $table->decimal('remah_berat', 15, 2)->nullable();  // 2. Berat Remahan (Kg)
            $table->date('remah_tgl_masuk')->nullable();        // 3. Tanggal Masuk
            $table->integer('remah_umur')->nullable();          // 4. Umur (Hari)

            // --- 2. PENGERINGAN ---
            $table->time('dryer_jam_start')->nullable();        // A. Jam Start Dryer
            $table->integer('dryer_trolly_masuk')->nullable();  // B. Jumlah Trolly Diisi/Masuk
            
            // C. Aktual Temperatur (Simpan string biar bisa input range "126-128")
            $table->string('dryer_temp_burner_1')->nullable();  
            $table->string('dryer_temp_burner_2')->nullable();  

            // D. Waktu Setiap Cycle (Simpan string biar bisa range "13.30-13.40")
            $table->string('dryer_cycle_time')->nullable();     

            // E. Bahan Bakar (Total Pemakaian)
            $table->decimal('bb_solar', 15, 2)->nullable();     // E(1) Solar (Liter)
            $table->decimal('bb_batubara', 15, 2)->nullable();  // E(2) Batubara (Kg)
            $table->decimal('bb_cangkang', 15, 2)->nullable();  // E(3) Cangkang (Kg)

            $table->integer('dryer_trolly_keluar')->nullable(); // F. Jumlah Trolly Keluar
            $table->time('dryer_jam_stop')->nullable();         // G. Jam Stop Dryer
            $table->decimal('dryer_jam_jalan', 15, 2)->nullable(); // H. Jumlah Jam Jalan Dryer (G-A)

            // Pressing
            $table->integer('press_jml_bales')->nullable();     // I. Jumlah Bales Yang Di Press
            $table->decimal('press_kg', 15, 2)->nullable();     // J. Kg Yang Di Press (I x 35)
            $table->decimal('capacity_per_jam', 15, 2)->nullable(); // K. Capacity Per Jam (J:H)

            // Produktivitas
            $table->decimal('jam_kerja', 15, 2)->nullable();    // L. Jam Kerja
            $table->decimal('produktivitas', 15, 2)->nullable(); // M. Produktivitas (Kg : Jam Kerja)
            $table->decimal('kg_sir_20_cake', 15, 2)->nullable(); // N. KG SIR 20 / Cake

            // Kontaminasi
            $table->string('kontaminasi_bales')->nullable();    // O. Hasil Bales yg ada kontaminasi (Text/Jam)
            $table->decimal('kontaminasi_berat', 15, 4)->nullable(); // P. Berat Kontaminan (Gram) - Pakai 4 desimal biar presisi (0.02)

            // Lain-lain
            $table->decimal('jam_ops_genset', 15, 2)->nullable(); // Q. Jam Operasional Genset

            // R. Bahan Bakar Per Ton
            $table->decimal('bb_solar_per_ton', 15, 2)->nullable();
            $table->decimal('bb_batubara_per_ton', 15, 2)->nullable();
            $table->decimal('bb_cangkang_per_ton', 15, 2)->nullable();

            $table->decimal('listrik_pln', 15, 2)->nullable(); // S. Pemakaian Listrik PLN (KWH)

            // --- 3. PACKING ROOM ---
            $table->integer('packing_jml_pallet')->nullable(); // A. Jumlah Pallet Diisi
            $table->string('packing_nomor')->nullable();       // B. Nomor (SW: 3620 s/d 3634)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksi_sir_barus');
    }
};