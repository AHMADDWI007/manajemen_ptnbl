<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    use HasFactory;

    protected $table = 'pengaturan';
    protected $primaryKey = 'id_pengaturan'; // Sesuaikan ID
    
    protected $fillable = [
        'kunci',
        'nilai',
        'deskripsi'
    ];
}