<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengolahanMaturasi extends Model
{
    use HasFactory;
    protected $table = 'pengolahan_maturasi';
    protected $fillable = [ 'maturasi_id', 'tgl_laporan', 'diolah', 'mutasi', 'masuk_hi','asal_bokar', 'keterangan' ];
    protected $casts = [ 'tgl_laporan' => 'date' ];
    public function maturasi(): BelongsTo {
        return $this->belongsTo(Maturasi::class, 'maturasi_id');
    }
}