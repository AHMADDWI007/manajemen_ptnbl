<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. TABEL UTAMA: PRODUKSI
        Schema::create('produksi_sir20', function (Blueprint $table) {
            $table->id('id_produksi_sir20');
            $table->date('tanggal_produksi');
            $table->string('shift_kerja');

            // --- OPERASIONAL DRYER ---
            $table->time('jam_start_dryer')->nullable();
            $table->integer('jumlah_trolly_masuk')->default(0);
            $table->integer('jumlah_trolly_keluar')->default(0);
            $table->time('jam_stop_dryer')->nullable();
            $table->float('jumlah_jam_dryer')->default(0); 

            // --- PRESS & HASIL ---
            $table->integer('jumlah_bales_dipress')->default(0);
            $table->float('kg_yang_dipress')->default(0); 
            $table->float('capacity_per_jam')->default(0); 
            $table->float('jam_kerja')->default(0);
            $table->float('produktivitas')->default(0); 
            
            // --- QUALITY / WASTE ---
            $table->float('kg_cake')->default(0);
            $table->integer('bales_terkontaminasi')->default(0);
            $table->float('berat_kontaminan')->default(0); 

            // --- UTILITAS ---
            $table->float('jam_operasional_genset')->default(0);
            $table->float('pemakaian_listrik_pln')->default(0);

            // --- PACKING ---
            $table->integer('jumlah_pallet')->default(0);
            $table->integer('total_nomor')->default(0);
            $table->integer('mc_val')->default(0);
            $table->string('nomor_start')->nullable();
            $table->string('nomor_end')->nullable();
            $table->integer('total_nomor_akhir')->default(0);
            
            $table->string('petugas')->nullable();
            $table->text('keterangan')->nullable(); // 🔥 TAMBAHKAN BARIS INI
            $table->timestamps();
        });

        // 2. TABEL REMAHAN
        Schema::create('remahan_sir20', function (Blueprint $table) {
            $table->id('id_remahan_sir20');
            $table->foreignId('id_produksi_sir20')
                  ->constrained('produksi_sir20', 'id_produksi_sir20')
                  ->onDelete('cascade');
            
            $table->string('ruang_maturasi')->nullable();
            $table->float('berat')->default(0);
            $table->integer('umur')->default(0);
            $table->timestamps();
        });

        // 3. TABEL AKTUAL TEMPERATURE
        Schema::create('aktual_temperature_sir20', function (Blueprint $table) {
            $table->id('id_aktual_temperature_sir20');
            $table->foreignId('id_produksi_sir20')
                  ->constrained('produksi_sir20', 'id_produksi_sir20')
                  ->onDelete('cascade');
            
            $table->string('jenis');
            $table->float('nilai_start')->default(0);
            $table->float('nilai_end')->default(0);
            $table->timestamps();
        });

        // 4. TABEL BAHAN BAKAR
        Schema::create('bahan_bakar_sir20', function (Blueprint $table) {
            $table->id('id_bahan_bakar_sir20');
            $table->foreignId('id_produksi_sir20')
                  ->constrained('produksi_sir20', 'id_produksi_sir20')
                  ->onDelete('cascade');
            $table->string('bahan_bakar');
            $table->float('digunakan')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bahan_bakar_sir20');
        Schema::dropIfExists('aktual_temperature_sir20');
        Schema::dropIfExists('remahan_sir20');
        Schema::dropIfExists('produksi_sir20');
    }
};