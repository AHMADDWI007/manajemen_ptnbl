<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menghapus kolom berat_truck dan berat_timbang.
     *
     * @return void
     */
    public function up()
    {
        // Pastikan nama tabel sudah benar ('hasil_uji_bokar_olah')
        Schema::table('hasil_uji_bokar_olah', function (Blueprint $table) {
            // Hanya hapus kolom jika ada (untuk keamanan jika migration dijalankan ulang)
            if (Schema::hasColumn('hasil_uji_bokar_olah', 'berat_truck')) {
                $table->dropColumn('berat_truck');
            }
            if (Schema::hasColumn('hasil_uji_bokar_olah', 'berat_timbang')) {
                $table->dropColumn('berat_timbang');
            }
        });
    }

    /**
     * Reverse the migrations.
     * Menambahkan kembali kolom jika di-rollback.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hasil_uji_bokar_olah', function (Blueprint $table) {
            // Tambahkan kembali kolom dengan definisi sebelumnya
            // Sesuaikan 'after()' jika urutan kolom penting
            $table->decimal('berat_truck', 10, 2)->nullable()->after('jenis');
            $table->decimal('berat_timbang', 10, 2)->nullable()->after('berat_truck');
        });
    }
};