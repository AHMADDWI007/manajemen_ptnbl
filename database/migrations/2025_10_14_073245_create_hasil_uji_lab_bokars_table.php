<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('hasil_uji_lab_bokar', function (Blueprint $table) {
            $table->id(); // Ini akan menjadi kolom 'no' atau 'id' (primary key)
            $table->date('tanggal');
            $table->string('suplier');
            $table->string('no_sampel')->unique(); // 'no sampel' diubah menjadi 'no_sampel' dan dibuat unik
            $table->decimal('k3', 8, 2)->nullable();   // Tipe data diubah menjadi decimal untuk angka, boleh kosong
            $table->decimal('dirt', 8, 2)->nullable(); // Tipe data diubah menjadi decimal untuk angka, boleh kosong
            $table->decimal('ask', 8, 2)->nullable();  // Tipe data diubah menjadi decimal untuk angka, boleh kosong
            $table->timestamps(); // Membuat kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_uji_lab_bokar');
    }
};