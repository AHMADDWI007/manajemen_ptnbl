<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('penjualan_sir20', function (Blueprint $table) {
        // Menambahkan kolom untuk menyimpan daftar no palet (misal: "2,3,4")
        $table->text('no_palet_list')->nullable()->after('harga');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan_sir20', function (Blueprint $table) {
            //
        });
    }
};
