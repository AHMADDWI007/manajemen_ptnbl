<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiApiBokar extends Model
{
    use HasFactory;

    // PENTING: Definisikan nama tabel secara eksplisit (tanpa s)
    protected $table = 'transaksi_api_bokar';

    // Izinkan semua kolom diisi
    protected $guarded = [];
}
