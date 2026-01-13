<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengolahan_basah', function (Blueprint $table) {
            // Kolom baru untuk menyimpan nilai rektif (wajib ditambahkan)
            $table->decimal('rektif', 10, 2)->default(0.00)->after('netto_kering'); 
        });
    }

    public function down(): void
    {
        Schema::table('pengolahan_basah', function (Blueprint $table) {
            $table->dropColumn('rektif');
        });
    }
};