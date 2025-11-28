<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RektifikasiStok extends Model
{
    use HasFactory;
    
    protected $table = 'rektifikasi_stok';
    protected $guarded = [];
}
