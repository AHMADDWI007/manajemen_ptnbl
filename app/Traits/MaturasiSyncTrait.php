<?php

namespace App\Traits;

use App\Models\HasilUjiLabBokarDiolah;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait MaturasiSyncTrait
{
    /**
     * FUNGSI SAKTI: Sinkronisasi Stok & Label Maturasi
     * Digunakan setelah: Update Tanggal, Pecah Data, Input K3 Lab, atau Hapus Data.
     */
    public function syncMaturasi($id_maturasi, $tanggal)
    {
        $tglString = Carbon::parse($tanggal)->format('Y-m-d');

        // 1. Hitung Total Netto Kering secara Cerdas (Logic Diskusi Kemarin)
        $listData = PengolahanBasah::where('id_maturasi', $id_maturasi)
                    ->whereDate('tanggal', $tglString)
                    ->get();

        $totalNettoKering = 0;
        foreach ($listData as $row) {
            if ($row->netto_kering > 0) {
                $totalNettoKering += $row->netto_kering;
            } elseif ($row->k3 > 0 && $row->netto_basah > 0) {
                $totalNettoKering += ($row->netto_basah * ($row->k3 / 100));
            }
        }

        // 2. Update Log Harian (Tabel pengolahan_maturasi)
        $logMaturasi = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', $tglString)
            ->first();

        if ($totalNettoKering > 0.01) {
            PengolahanMaturasi::updateOrCreate(
                ['id_maturasi' => $id_maturasi, 'tgl_laporan' => $tglString],
                ['masuk_hi' => $totalNettoKering]
            );
        } else {
            if ($logMaturasi) {
                // Hapus log jika sudah tidak ada sisa masuk, gilingan (diolah), dan mutasi
                if ($logMaturasi->diolah <= 0.01 && $logMaturasi->mutasi == 0) {
                    $logMaturasi->delete();
                } else {
                    $logMaturasi->update(['masuk_hi' => 0]);
                }
            }
        }

        // 3. Update Label Asal Bokar (Logic Trigger Label)
        $this->updateLabelMaturasi($id_maturasi, $tglString);

        // 4. Update Stok Akhir Realtime di Tabel Master Maturasi
        $this->refreshMasterStokMaturasi($id_maturasi);
    }

    /**
     * Logic untuk Update Kolom asal_bokar (PT, DS, CMP)
     */
    private function updateLabelMaturasi($id_maturasi, $tanggal)
    {
        // 1. Cari dulu dari tabel Pengolahan Basah (Data Fresh)
        $listJenis = PengolahanBasah::where('id_maturasi', $id_maturasi)
            ->whereDate('tanggal', $tanggal)
            ->pluck('jenis')
            ->filter(function ($value) { 
                return !is_null($value) && $value !== '' && $value !== 'PENDING'; 
            })
            ->unique()->sort()->values();

        $labelAkhir = null;

        if ($listJenis->count() > 0) {
            // Jika ada data di Pengolahan Basah, pakai itu
            if ($listJenis->count() > 1) {
                $labelAkhir = "CMP (" . $listJenis->implode(', ') . ")";
            } else {
                $labelAkhir = $listJenis->first();
            }
        } else {
            // 🔥 2. Jika tidak ada di tabel Basah, cek Log Mutasi (PengolahanMaturasi)
            $logMutasi = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
                ->whereDate('tgl_laporan', $tanggal)
                ->where('keterangan', 'LIKE', '%Jenis: %')
                ->first();

            if ($logMutasi) {
                // Ambil info Jenis dari string keterangan pakai regex
                // 🔥 PERBAIKAN REGEX: Ambil semua teks sampai ketemu kurung tutup yang ada di akhir kalimat atau sebelum tanda pipa (|)
                if (preg_match('/Jenis:\s*(.*?)(?=\)\s*(?:\||$))/', $logMutasi->keterangan, $matches)) {
                    $labelAkhir = trim($matches[1]);
                }
            }
        }

        // 3. Update ke Master Bak jika label ditemukan
        // Jika labelAkhir tetap null, jangan langsung hapus, 
        // biarkan logic refreshMasterStokMaturasi yang menghapus jika stok benar-benar 0
        if ($labelAkhir) {
            $bak = Maturasi::find($id_maturasi);
            if ($bak) {
                $bak->asal_bokar = $labelAkhir;
                $bak->save();
            }
        }
    }

    /**
     * Logic untuk Hitung Ulang Stok Akhir Master Bak
     */
    private function refreshMasterStokMaturasi($id_maturasi)
    {
        $masterBak = Maturasi::find($id_maturasi);
        if (!$masterBak) return;

        $totalMasuk = PengolahanMaturasi::where('id_maturasi', $id_maturasi)->sum('masuk_hi');
        $totalKeluar = PengolahanMaturasi::where('id_maturasi', $id_maturasi)->sum(DB::raw('diolah + mutasi'));
        
        $stokAkhirBaru = max(0, $totalMasuk - $totalKeluar);

        $masterBak->stok_akhir = $stokAkhirBaru;
        
        if ($stokAkhirBaru <= 0.01) {
            $masterBak->stok_akhir = 0;
            $masterBak->keterangan = 'KOSONG';
            $masterBak->asal_bokar = null;
            $masterBak->umur = 0;
            $masterBak->tgl_masuk = null;
        }
        $masterBak->save();
    }

    /**
     * Helper untuk mencari tanggal masuk terakhir sebagai dasar hitungan UMUR
     */
    public function getHistoryDateFromLog(int $id_maturasi, $reportDate)
    {
        $reportDate = Carbon::parse($reportDate);

        $lastEntry = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
            ->whereDate('tgl_laporan', '<', $reportDate->toDateString())
            ->where(function($q) {
                $q->where('masuk_hi', '>', 0.01)->orWhere('mutasi', '<', -0.01); 
            })->orderBy('tgl_laporan', 'desc')->first();

        if ($lastEntry) {
            if ($lastEntry->masuk_hi > 0.01) {
                $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $id_maturasi)
                    ->whereDate('tanggal', '<=', $lastEntry->tgl_laporan)->orderBy('tanggal', 'desc')->first();
                return $lab ? Carbon::parse($lab->tanggal) : Carbon::parse($lastEntry->tgl_laporan);
            }
            return Carbon::parse($lastEntry->tgl_laporan);
        }

        $logLab = HasilUjiLabBokarDiolah::where('id_maturasi', $id_maturasi)
            ->whereDate('tanggal', '<', $reportDate->toDateString())->orderBy('tanggal', 'desc')->first();
            
        return $logLab ? Carbon::parse($logLab->tanggal) : null;
    }
}