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
        // 1. TABEL LOKASI (Master Gudang/Lokasi)
        Schema::create('lokasi', function (Blueprint $table) {
            $table->id('id_lokasi'); // Primary Key
            $table->string('nama');  // Contoh: "Gudang SIR", "Gudang TOH 1"
            $table->timestamps();
        });

        // 2. TABEL MUTU (Master Kategori Mutu)
        Schema::create('mutu', function (Blueprint $table) {
            $table->id('id_mutu'); // Primary Key
            $table->string('uraian'); // Contoh: "Mutu Prima", "Low", "WS"
            $table->timestamps();
        });

        // 3. TABEL PALLET (Identitas Pallet Unik)
        Schema::create('pallet', function (Blueprint $table) {
            $table->id('id_pallet'); // Primary Key
            // 🔥🔥 PASTIKAN BARIS INI ADA! INI YANG BIKIN ERROR 🔥🔥
            $table->unsignedBigInteger('id_produksi_sir')->nullable();
            $table->string('no_pallet')->index(); // Nomor Pallet (String biar aman)
            // 🔥 PASTIKAN BARIS INI ADA! 🔥
            $table->decimal('berat', 8, 2)->default(0);
            // 🔥 TAMBAHAN KOLOM JENIS PALLET 🔥
            // Default diset ke 'SW' agar data lama tidak kosong saat migration dijalankan
            $table->string('jenis_pallet', 10)->default('SW');
            $table->date('tanggal_produksi');
            $table->date('tanggal_penjualan')->nullable(); // Nullable karena belum tentu langsung terjual
            $table->timestamps();
        });

        // 4. TABEL LOKASI PALLET (Tracking Posisi Pallet)
        Schema::create('lokasi_pallet', function (Blueprint $table) {
            $table->id('id_lokasi_pallet'); // Primary Key
            
            // Foreign Keys
            $table->unsignedBigInteger('id_lokasi');
            $table->unsignedBigInteger('id_pallet');
            
            $table->date('tanggal'); // Tanggal pallet masuk ke lokasi ini
            $table->timestamps();

            // Relasi (Opsional, diaktifkan agar data konsisten)
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi')->onDelete('cascade');
            $table->foreign('id_pallet')->references('id_pallet')->on('pallet')->onDelete('cascade');
        });

        // 5. TABEL STOK SIR (Saldo Awal per Lokasi)
        Schema::create('stok_sir', function (Blueprint $table) {
            $table->id('id_stok_sir'); // Primary Key
            
            $table->unsignedBigInteger('id_lokasi');
            $table->decimal('saldo_awal', 15, 2)->default(0); // Menggunakan decimal agar presisi Kg
            
            $table->timestamps();

            // Relasi
            $table->foreign('id_lokasi')->references('id_lokasi')->on('lokasi')->onDelete('cascade');
        });

        // 6. TABEL KONDISI PALLET (Tracking Mutu Pallet)
        // Saya namakan PK-nya 'id_kondisi_pallet' biar beda dengan tabel lokasi_pallet
        Schema::create('kondisi_pallet', function (Blueprint $table) {
            $table->id('id_kondisi_pallet'); // Primary Key
            
            $table->unsignedBigInteger('id_pallet');
            $table->unsignedBigInteger('id_mutu');
            
            $table->date('tanggal'); // Tanggal penetapan mutu
            $table->timestamps();

            // Relasi
            $table->foreign('id_pallet')->references('id_pallet')->on('pallet')->onDelete('cascade');
            $table->foreign('id_mutu')->references('id_mutu')->on('mutu')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus tabel dengan urutan terbalik (dari anak ke induk) agar tidak error Foreign Key
        Schema::dropIfExists('kondisi_pallet');
        Schema::dropIfExists('stok_sir');
        Schema::dropIfExists('lokasi_pallet');
        Schema::dropIfExists('pallet');
        Schema::dropIfExists('mutu');
        Schema::dropIfExists('lokasi');
    }
};