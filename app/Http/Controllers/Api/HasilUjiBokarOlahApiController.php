<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah; 
use App\Models\HasilUjiBokarDiolah; 
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class HasilUjiBokarOlahApiController extends Controller
{
    /**
     * [LIST UTAMA] Mengambil data history yang K3-nya SUDAH diisi.
     */
    public function index()
    {
        try {
            // Ambil data yang sudah ada K3-nya (History)
            $data = PengolahanBasah::whereNotNull('k3')
                        ->orderBy('tanggal', 'desc')
                        ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Data history berhasil diambil.',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ [SPINNER] Mengambil daftar Bak yang K3-nya MASIH KOSONG (NULL).
     * Ini yang dipanggil oleh Spinner/Dropdown di Android.
     * Logikanya SAMA PERSIS dengan Web Admin ($daftar_bak_belum_uji).
     */
    public function getPendingK3()
    {
        try {
            $data = PengolahanBasah::whereNull('k3')
                        ->orderBy('tanggal', 'desc') // Urutkan tanggal terbaru
                        ->get();

            return response()->json([
                'success' => true, 
                'message' => 'Daftar bak pending berhasil dimuat.',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error getPendingK3: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [STORE] Simpan K3 Baru (Update PengolahanBasah & Trigger Maturasi)
     */
    public function store(Request $request) 
    {
        try {
            $validator = Validator::make($request->all(), [
                'pengolahan_basah_id' => 'required|exists:pengolahan_basah,id',
                'k3' => 'required|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            // 1. Ambil Data Induk
            $dataBasah = PengolahanBasah::findOrFail($request->pengolahan_basah_id);
            
            // 2. Hitung Kering
            $k3 = $request->k3;
            $netto_kering = $dataBasah->netto_basah * ($k3 / 100);

            // 3. Update Tabel Induk (Pengolahan Basah)
            $dataBasah->update([
                'k3'           => $k3,
                'netto_kering' => $netto_kering
            ]);

            // 4. Catat Log History (Tabel Hasil Uji)
            $log = HasilUjiBokarDiolah::create([
                'pengolahan_basah_id' => $dataBasah->id,
                'tanggal'             => $dataBasah->tanggal,
                'bak_maturasi'        => $dataBasah->bak_maturasi,
                'k3'                  => $k3,
                'netto_kering'        => $netto_kering
            ]);

            // 5. TRIGGER MATURASI (Logic "The Brain" dari Web)
            $this->syncToMaturasi($dataBasah);

            return response()->json([
                'success' => true, 
                'message' => 'K3 disimpan & Stok Maturasi bertambah.',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error store API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [UPDATE] Edit K3 yang sudah ada
     */
    public function updateK3(Request $request, $id)
    {
        try {
            // $id adalah ID pengolahan_basah
            $dataBasah = PengolahanBasah::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'k3' => 'required|numeric|min:0|max:100',
            ]);
            if ($validator->fails()) return response()->json(['success'=>false, 'errors'=>$validator->errors()], 422);
            
            $k3 = $request->k3;
            $netto_kering = $dataBasah->netto_basah * ($k3 / 100);

            // Update Induk
            $dataBasah->update(['k3' => $k3, 'netto_kering' => $netto_kering]);
            
            // Update Log
            HasilUjiBokarDiolah::where('pengolahan_basah_id', $id)->update([
                'k3' => $k3, 'netto_kering' => $netto_kering
            ]);

            // Trigger Ulang Maturasi
            $this->syncToMaturasi($dataBasah);

            return response()->json(['success' => true, 'message' => 'Data K3 diperbarui.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================================
    // LOGIKA SINKRONISASI MATURASI (COPY DARI WEB)
    // ==========================================================
    private function syncToMaturasi($dataBasah)
    {
        preg_match('/(\d+)/', $dataBasah->bak_maturasi, $matches);
        $nomor = $matches[0] ?? 0;
        if ($nomor == 0) return; 

        $namaBakResmi = "Di Bak Maturasi-" . $nomor;

        // 1. Master View
        $maturasiMaster = Maturasi::firstOrCreate(
            ['uraian' => $namaBakResmi],
            ['stok_awal' => 0, 'stok_akhir' => 0]
        );

        // 2. Header CMP
        if (!empty($maturasiMaster->asal_bokar) && $maturasiMaster->asal_bokar != $dataBasah->jenis) {
            $maturasiMaster->asal_bokar = 'CMP';
        } else {
            $maturasiMaster->asal_bokar = $dataBasah->jenis;
        }

        // 3. Hitung Total Hari Ini
        $totalMasukHariIni = PengolahanBasah::where('bak_maturasi', $dataBasah->bak_maturasi)
            ->where('tanggal', $dataBasah->tanggal)
            ->sum('netto_kering'); 

        // 4. Update Log Harian
        $logHarian = PengolahanMaturasi::updateOrCreate(
            ['maturasi_id' => $maturasiMaster->id, 'tgl_laporan' => $dataBasah->tanggal],
            ['masuk_hi' => $totalMasukHariIni, 'keterangan' => 'Auto-Sync dari Lab API']
        );

        // 5. Hitung Saldo Akhir Master
        $maturasiMaster->masuk_hi = $logHarian->masuk_hi;
        $maturasiMaster->stok_akhir = $maturasiMaster->stok_awal + $maturasiMaster->masuk_hi - $maturasiMaster->diolah - $maturasiMaster->mutasi;
        
        if ($totalMasukHariIni > 0) {
            $maturasiMaster->tgl_masuk = $dataBasah->tanggal;
            $maturasiMaster->umur = 0;
        }

        $maturasiMaster->save();
    }
}