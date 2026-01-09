<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengolahanBasah extends Model
{
    use HasFactory;
    protected $table = 'pengolahan_basah';
    protected $primaryKey = 'id_pengolahan_basah';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'berat_truck' => 'double', 'berat_timbang' => 'double', 
        'netto_basah' => 'double', 'k3' => 'double', 'netto_kering' => 'double',
    ];

    public function maturasi(): BelongsTo
    {
        return $this->belongsTo(Maturasi::class, 'id_maturasi');
    }

    public function hasilUjiLabBokarDiolah(): HasOne
    {
        return $this->hasOne(HasilUjiLabBokarDiolah::class, 'id_pengolahan_basah');
    }
}