<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus "s" di sini agar nama tabel jadi 'transaksi_api_bokar'
        Schema::create('transaksi_api_bokar', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('kode_api'); // petani, inhut, ptpn, total
            
            // Kita pakai decimal atau bigInteger agar aman untuk angka besar
            $table->decimal('masuk_sd_kemarin', 20, 2)->default(0);
            $table->decimal('masuk_hi', 20, 2)->default(0);
            
            $table->timestamps();

            // Mencegah duplikasi data untuk tanggal & kode yang sama
            $table->unique(['tanggal', 'kode_api']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_api_bokar');
    }
};