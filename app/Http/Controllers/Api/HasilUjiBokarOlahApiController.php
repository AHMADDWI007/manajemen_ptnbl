<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Maturasi;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\HasilUjiLabBokarDiolah; // Gunakan Model yang benar

class HasilUjiBokarOlahApiController extends Controller
{
    /**
     * [LIST UTAMA] Mengambil data history dari tabel hasil_uji_lab_bokar_diolah
     * Android Endpoint: GET /hasil-uji-bokar-olah
     */
    public function index()
    {
        try {
            // Ambil dari tabel log history, join ke pengolahan basah jika perlu info tambahan
            $data = HasilUjiLabBokarDiolah::with(['pengolahanBasah', 'maturasi'])
                        ->orderBy('tanggal', 'desc')
                        ->get()
                        ->map(function($item) {
                            // Flatten data untuk Android (agar mudah dibaca)
                            return [
                                'id' => $item->id_hasil_uji_lab_bokar_diolah, // ID History
                                'pengolahan_basah_id' => $item->id_pengolahan_basah, // ID Timbang
                                // 🔥 PERBAIKAN BARIS 33: Pakai Carbon::parse
                                'tanggal' => Carbon::parse($item->tanggal)->format('Y-m-d'),
                                // Ambil nama bak dari relasi maturasi atau string manual
                                'bak_maturasi' => $item->maturasi ? $item->maturasi->uraian : '-',
                                'jenis' => $item->jenis,
                                'netto_basah' => (float) $item->netto_basah,
                                'k3' => (float) $item->k3,
                                'netto_kering' => (float) $item->netto_kering,
                            ];
                        });
            
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
     * Android Endpoint: GET /timbang-bokar/pending-k3
     */
    public function getPendingK3()
    {
        try {
            // Cari data di pengolahan_basah yang belum punya K3
            $data = PengolahanBasah::with('maturasi')
                        ->whereNull('k3')
                        ->orderBy('tanggal', 'desc')
                        ->get()
                        ->map(function($item) {
                            // Format ulang agar Android bisa baca ID dan Nama Bak
                            return [
                                'id' => $item->id_pengolahan_basah, // ID Timbang
                                // 🔥 PERBAIKAN BARIS 69: Pakai Carbon::parse
                                'tanggal' => Carbon::parse($item->tanggal)->format('Y-m-d'),
                                'bak_maturasi' => $item->maturasi ? $item->maturasi->uraian : 'Unknown',
                                'jenis' => $item->jenis,
                                'berat_truck' => $item->berat_truck,
                                'berat_timbang' => $item->berat_timbang,
                                'netto_basah' => $item->netto_basah,
                            ];
                        });

            return response()->json([
                'success' => true, 
                'message' => 'Daftar bak pending berhasil dimuat.',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [STORE] Simpan K3 Baru
     * Android Endpoint: POST /hasil-uji-bokar-olah
     */
    public function store(Request $request) 
    {
        // Gunakan Transaction agar aman
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                // Validasi ID Pengolahan Basah
                'pengolahan_basah_id' => 'required|exists:pengolahan_basah,id_pengolahan_basah',
                'k3' => 'required|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            // 1. Ambil Data Induk
            $dataBasah = PengolahanBasah::where('id_pengolahan_basah', $request->pengolahan_basah_id)->firstOrFail();
            
            // 2. Hitung Kering
            $k3 = $request->k3;
            $netto_kering = $dataBasah->netto_basah * ($k3 / 100);

            // 3. Update Tabel Induk (Pengolahan Basah)
            $dataBasah->update([
                'k3'           => $k3,
                'netto_kering' => $netto_kering
            ]);

            // 4. Catat Log History (Tabel Hasil Uji)
            $log = HasilUjiLabBokarDiolah::create([
                'id_pengolahan_basah' => $dataBasah->id_pengolahan_basah,
                'tanggal'             => $dataBasah->tanggal,
                'id_maturasi'         => $dataBasah->id_maturasi, // Ambil ID Maturasi dari induk
                'jenis'               => $dataBasah->jenis,
                'netto_basah'         => $dataBasah->netto_basah,
                'k3'                  => $k3,
                'netto_kering'        => $netto_kering
            ]);

            // 5. TRIGGER MATURASI
            $this->syncToMaturasi($dataBasah->id_maturasi, $dataBasah->jenis, $dataBasah->tanggal);

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'K3 disimpan & Stok Maturasi bertambah.',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [UPDATE] Edit K3 yang sudah ada
     * Android Endpoint: PUT /hasil-uji-bokar-olah/update-k3/{id}
     * Parameter {id} adalah ID PENGOLAHAN BASAH
     */
    public function updateK3(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $dataBasah = PengolahanBasah::where('id_pengolahan_basah', $id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'k3' => 'required|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) return response()->json(['success'=>false, 'errors'=>$validator->errors()], 422);
            
            $k3 = $request->k3;
            $netto_kering = $dataBasah->netto_basah * ($k3 / 100);

            // Update Induk
            $dataBasah->update(['k3' => $k3, 'netto_kering' => $netto_kering]);
            
            // Update Log History
            HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $id)->update([
                'k3' => $k3, 'netto_kering' => $netto_kering
            ]);

            // Trigger Ulang Maturasi
            $this->syncToMaturasi($dataBasah->id_maturasi, $dataBasah->jenis, $dataBasah->tanggal);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data K3 diperbarui.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [DESTROY] Reset K3 (Hapus History & Null-kan Induk)
     * Android Endpoint: DELETE /uji-bokar-olah/{id}
     * Parameter {id} adalah ID HISTORY (id_hasil_uji_lab_bokar_diolah)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Cari data history
            $history = HasilUjiLabBokarDiolah::where('id_hasil_uji_lab_bokar_diolah', $id)->firstOrFail();
            $idPengolahan = $history->id_pengolahan_basah;
            $idMaturasi = $history->id_maturasi;
            $tanggal = $history->tanggal;
            $jenis = $history->jenis;

            // Hapus History
            $history->delete();

            // Reset Induk (Pengolahan Basah) jadi NULL
            PengolahanBasah::where('id_pengolahan_basah', $idPengolahan)->update([
                'k3' => null,
                'netto_kering' => null
            ]);

            // Sync Ulang Maturasi (Stok akan berkurang otomatis)
            $this->syncToMaturasi($idMaturasi, $jenis, $tanggal);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data K3 di-reset.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================================
    // LOGIKA SINKRONISASI MATURASI (THE BRAIN)
    // ==========================================================
    private function syncToMaturasi($idMaturasi, $jenis, $tanggal)
    {
        $maturasiMaster = Maturasi::find($idMaturasi);
        if (!$maturasiMaster) return;

        // 1. Update Asal Bokar (CMP Logic)
        $asalBaru = strtoupper($jenis);
        if (!empty($maturasiMaster->asal_bokar) && $maturasiMaster->asal_bokar != $asalBaru && $maturasiMaster->asal_bokar !== 'CMP') {
            $asalBaru = 'CMP';
        }
        $maturasiMaster->asal_bokar = $asalBaru;

        // 2. Hitung Total Masuk Hari Ini (Agregat dari semua Pengolahan Basah di tanggal & bak yg sama)
        $totalMasukHariIni = PengolahanBasah::where('id_maturasi', $idMaturasi)
            ->whereDate('tanggal', $tanggal)
            ->sum('netto_kering'); // Sum netto kering yang valid (yg null diabaikan)

        // 3. Update Log Harian Maturasi
        $logHarian = PengolahanMaturasi::updateOrCreate(
            ['id_maturasi' => $idMaturasi, 'tgl_laporan' => $tanggal],
            ['masuk_hi' => $totalMasukHariIni, 'keterangan' => 'Auto-Sync dari Lab API']
        );

        // 4. Hitung Saldo Akhir Master (Snapshot saat ini)
        // Rumus: Stok Awal + Masuk - Diolah - Mutasi = Stok Akhir
        // Note: Stok awal idealnya statis per periode, tapi di sini kita update snapshot real-time
        
        $maturasiMaster->masuk_hi = $logHarian->masuk_hi;
        $maturasiMaster->stok_akhir = $maturasiMaster->stok_awal + $maturasiMaster->masuk_hi - $maturasiMaster->diolah - $maturasiMaster->mutasi;
        
        // Update Tanggal Masuk & Umur jika ada barang masuk
        if ($totalMasukHariIni > 0) {
            $maturasiMaster->tgl_masuk = $tanggal;
            $maturasiMaster->umur = 0;
        }

        $maturasiMaster->save();
    }
}