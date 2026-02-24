<?php

namespace App\Http\Controllers\Api;

use App\Models\Maturasi;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabBokarDiolah;

class TimbangBokarApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            // 🔥 TAMBAHAN FILTER TANGGAL
            $query = PengolahanBasah::with('maturasi')
                        ->orderBy('tanggal', 'desc');

            // Jika ada parameter ?date=YYYY-MM-DD
            if ($request->has('date') && !empty($request->date)) {
                $query->whereDate('tanggal', $request->date);
            }

            $data = $query->get()->map(function($item) {
                // Flatten data untuk Android
                $item->bak_maturasi = $item->maturasi ? $item->maturasi->uraian : '-';
                return $item;
            });

            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'tanggal'       => 'required|date_format:Y-m-d',
                // HAPUS validasi 'jenis' => 'required...'
                'bak_maturasi'  => 'required|string', 
                'berat_truck'   => 'required|numeric|min:0',
                'berat_timbang' => 'required|numeric|min:0',
            ]);

            // Cari ID Maturasi (Tetap sama)
            $maturasi = Maturasi::where('uraian', $request->bak_maturasi)->first();
            if (!$maturasi) {
                return response()->json(['success' => false, 'message' => 'Bak Maturasi tidak ditemukan'], 404);
            }

            $netto_basah = $request->berat_timbang - $request->berat_truck;

            $basah = PengolahanBasah::create([
                'tanggal'       => $request->tanggal,
                'jenis'         => 'PENDING', // 🔥 OTOMATIS PENDING (Tunggu Admin Web Pecah)
                'id_maturasi'   => $maturasi->id_maturasi,
                'berat_truck'   => $request->berat_truck,
                'berat_timbang' => $request->berat_timbang,
                'netto_basah'   => $netto_basah,
                'k3'            => null,
                'netto_kering'  => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Data tersimpan (Pending Split)', 'data' => $basah], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        $data = PengolahanBasah::with('maturasi')->where('id_pengolahan_basah', $id)->firstOrFail();
        // Flatten bak_maturasi untuk edit form di Android
        $data->bak_maturasi = $data->maturasi ? $data->maturasi->uraian : '';
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $id) 
    {
        // 1. Cari data yang mau diedit
        $data = PengolahanBasah::where('id_pengolahan_basah', $id)->firstOrFail();

        // 2. Validasi Input (Tanpa validasi 'jenis')
        $request->validate([
            'tanggal'       => 'required|date_format:Y-m-d',
            'bak_maturasi'  => 'required|string', 
            'berat_truck'   => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:0',
        ]);

        // 3. Cari ID Maturasi berdasarkan nama (String dari Android)
        $maturasi = Maturasi::where('uraian', $request->bak_maturasi)->first();
        
        // 🔥 PERBAIKAN: Jika bak tidak ditemukan, kembalikan error JSON (Jangan kosong)
        if (!$maturasi) { 
            return response()->json(['success' => false, 'message' => 'Bak Maturasi tidak ditemukan'], 404);
        }

        // 4. Hitung ulang Netto Basah
        $netto_basah = $request->berat_timbang - $request->berat_truck;

        // 5. Update Data
        $data->update([
            'tanggal'       => $request->tanggal,
            'id_maturasi'   => $maturasi->id_maturasi,
            'berat_truck'   => $request->berat_truck,
            'berat_timbang' => $request->berat_timbang,
            'netto_basah'   => $netto_basah,
            // JANGAN UPDATE KOLOM 'jenis'. Biarkan apa adanya.
        ]);

        return response()->json(['success' => true, 'message' => 'Data diperbarui']);
    }

    public function destroy($id) {
        $pengolahan = PengolahanBasah::find($id); 
        
        if ($pengolahan) {
            $id_maturasi = $pengolahan->id_maturasi;
            $tanggal     = $pengolahan->tanggal;

            // 1. Hapus Data Lab Terkait
            HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $id)->delete();

            // 2. Hapus Data Utama
            $pengolahan->delete();

            // 3. 🔥 LOGIKA PERBAIKAN: Hitung Ulang & Bersihkan Log Maturasi
            $sisaNettoKering = PengolahanBasah::where('id_maturasi', $id_maturasi)
                ->where('tanggal', $tanggal)
                ->sum('netto_kering'); 

            $logMaturasi = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
                ->whereDate('tgl_laporan', $tanggal)
                ->first();

            if ($logMaturasi) {
                // 🔥 SINKRON WEB: Hapus tuntas jika kosong dan tidak ada aktivitas lain
                if ($sisaNettoKering <= 0.01 && $logMaturasi->diolah <= 0.01 && $logMaturasi->mutasi == 0) {
                    $logMaturasi->delete();
                } else {
                    $logMaturasi->masuk_hi = $sisaNettoKering;
                    $logMaturasi->save();
                }

                // 4. Update Master Stok Maturasi (Stok Akhir)
                $stokAwal = PengolahanMaturasi::where('id_maturasi', $id_maturasi)
                    ->whereDate('tgl_laporan', '<', $tanggal)
                    ->sum(DB::raw('masuk_hi - diolah - mutasi'));

                // Hitung dari log baru (jika masih ada)
                $logBaru = PengolahanMaturasi::where('id_maturasi', $id_maturasi)->whereDate('tgl_laporan', $tanggal)->first();
                $masukBaru = $logBaru ? $logBaru->masuk_hi : 0;
                $keluarBaru = $logBaru ? ($logBaru->diolah + $logBaru->mutasi) : 0;
                
                $stokAkhirBaru = $stokAwal + $masukBaru - $keluarBaru;

                $masterBak = Maturasi::find($id_maturasi);
                if ($masterBak) {
                    $masterBak->stok_akhir = max(0, $stokAkhirBaru);
                    
                    if ($masterBak->stok_akhir <= 0.01) {
                        $masterBak->stok_akhir = 0;
                        $masterBak->keterangan = 'KOSONG';
                        $masterBak->asal_bokar = null;
                        $masterBak->umur = 0;
                        $masterBak->tgl_masuk = null;
                    }
                    $masterBak->save();
                }
            }
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus dan Stok Maturasi disesuaikan']);
        }
        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }
}