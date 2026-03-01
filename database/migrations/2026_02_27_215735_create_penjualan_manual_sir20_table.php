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
        Schema::create('penjualan_manual_sir20', function (Blueprint $table) {
            $table->id('id_penjualan_manual');
            // Relasi ke Invoice Utama di tabel penjualan_sir20
            $table->unsignedBigInteger('id_penjualan_sir20'); 
            $table->date('tanggal');
            
            // Data "Hutang" Pallet
            $table->integer('pallet_manual')->default(0); 
            $table->decimal('berat_manual', 15, 2)->default(0);
            
            // Status Pelunasan
            $table->enum('status', ['Pending', 'Settled'])->default('Pending');
            
            $table->timestamps();

            // Foreign key (opsional tapi bagus untuk integritas data)
            $table->foreign('id_penjualan_sir20')
                ->references('id_penjualan_sir20')
                ->on('penjualan_sir20')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_manual_sir20');
    }
};
