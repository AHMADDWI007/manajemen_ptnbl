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
        Schema::table('produksi_sirs', function (Blueprint $table) {
            // Menghapus kolom ptnb
            $table->dropColumn('ptnb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produksi_sirs', function (Blueprint $table) {
            // Mengembalikan kolom jika di-rollback (sesuaikan tipe datanya, misal decimal)
            $table->decimal('ptnb', 15, 2)->nullable()->after('saldo_akhir');
        });
    }
};