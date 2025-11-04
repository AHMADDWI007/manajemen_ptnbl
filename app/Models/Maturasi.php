<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maturasi extends Model
{
    use HasFactory;
    protected $fillable = [ 'uraian', 'stok_awal', 'tgl_masuk', 'umur', 'diolah', 'mutasi', 'masuk_hi', 'stok_akhir', 'asal_bokar', 'keterangan' ];
    protected $casts = [ 'tgl_masuk' => 'date' ];
    public function riwayatPengolahan(): HasMany {
        return $this->hasMany(PengolahanMaturasi::class, 'maturasi_id');
    }
}