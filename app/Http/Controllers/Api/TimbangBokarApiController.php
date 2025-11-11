<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; // <-- ✅ TAMBAHKAN BARIS INI
use App\Models\PengolahanBasah;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TimbangBokarApiController extends Controller
{
    /**
     * ✅ [FIX] [TABEL TIMBANG BOKAR] Mengambil semua data Timbang Bokar (Basah)
     * Endpoint: GET /timbang-bokar
     */
    public function index()
    {
        Log::info('======== TES MANUAL: FUNGSI INDEX TIMBANG BOKAR API DIPANGGIL ========');

        try {
            // Ambil semua data dari pengolahan_basah, urutkan terbaru
            $data = PengolahanBasah::orderBy('tanggal', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Data Timbang Bokar (Basah) berhasil diambil.',
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error index TimbangBokarApi: ' . $e->getMessage());
            // Kirim pesan error yang jelas jika server gagal
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ✅ [FORM 1] Menyimpan data Timbang Bokar BARU
     * Endpoint: POST /timbang-bokar
     */
    public function store(Request $request)
    {
        try {
            // ✅ PERBAIKAN: Validasi 'bak_maturasi' seharusnya tidak 'unique'
            //    Anda mungkin ingin unique per tanggal, tapi untuk API lebih baik
            //    serahkan ke logika bisnis. Kita hapus 'unique' agar tidak error.
            $validatedData = $request->validate([
                'tanggal'       => 'required|date_format:Y-m-d',
                'jenis'         => 'required|string|max:255|in:PT,DS,INHUT',
                'bak_maturasi'  => 'required|string|max:255', 
                'berat_truck'    => 'required|numeric|min:0',
                'berat_timbang' => 'required|numeric|min:0',
                'netto_basah'   => 'required|numeric|min:0',
            ]);

            // k3 dan netto_kering otomatis NULL
            $basah = PengolahanBasah::create($validatedData);

            try {
                $this->updateMaturasiTrigger(
                    $basah->bak_maturasi, 
                    $basah->netto_basah, 
                    $basah->tanggal, 
                    $basah->jenis,
                    false 
                );
            } catch (\Exception $e) {
                Log::error("API Gagal trigger Maturasi (Basah) saat store: " . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Timbang Bokar berhasil disimpan.',
                'data'    => $basah
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak valid.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error store Timbang Bokar: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ [DETAIL] Menampilkan 1 data Timbang Bokar
     * Endpoint: GET /timbang-bokar/{id}
     */
    public function show($id)
    {
         try {
            $data = PengolahanBasah::findOrFail($id);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Fungsi update (PUT/PATCH) tidak umum untuk list API, 
    // jadi kita biarkan kosong kecuali Anda membutuhkannya.
    // public function update(Request $request, $id) { /* ... */ }

    /**
     * ✅ [HAPUS] Menghapus data Timbang Bokar
     * Endpoint: DELETE /timbang-bokar/{id}
     */
    public function destroy($id)
    {
        try {
            $data = PengolahanBasah::findOrFail($id);
            
            // TODO: Tambahkan logika untuk membatalkan trigger maturasi jika diperlukan
            // (Saat ini, data di maturasi akan tetap tercatat)

            $data->delete();
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        } catch (\Exception $e) {
             Log::error('Error destroy TimbangBokarApi: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    // --- METODE KHUSUS UNTUK FORM 2 (Input K3) ---
    // (Fungsi-fungsi di bawah ini sudah benar dari kode Anda sebelumnya)

    /**
     * ✅ [FORM 2 SPINNER] Ambil data yang K3-nya masih NULL.
     * Endpoint: GET /timbang-bokar/pending-k3
     */
    public function getPendingK3()
    {
        try {
            $data = PengolahanBasah::whereNull('k3')->orderBy('tanggal', 'asc')->get();
            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data pending dimuat']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ [FORM 2 SIMPAN] Update K3 dan hitung Netto Kering.
     * Endpoint: PUT /timbang-bokar/update-k3/{id}
     */
    public function updateK3(Request $request, $id)
    {
        try {
            $data = PengolahanBasah::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'k3' => 'required|numeric|min:0|max:100',
            ]);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi K3 gagal', 'errors' => $validator->errors()], 422);
            }

            $k3Value = (double)$request->k3;
            $nettoBasah = (double)$data->netto_basah;
            $nettoKeringValue = ($nettoBasah * $k3Value) / 100.0;

            $data->k3 = $k3Value;
            $data->netto_kering = $nettoKeringValue;
            $data->save();

            try {
                $this->updateMaturasiTrigger(
                    $data->bak_maturasi, 
                    $nettoKeringValue, 
                    $data->tanggal, 
                    $data->jenis,
                    true 
                );
            } catch (\Exception $e) {
                Log::error("API Gagal trigger Maturasi (Kering) saat updateK3: " . $e->getMessage());
            }

            return response()->json(['success' => true, 'data' => $data, 'message' => 'Data K3 berhasil disimpan']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Error updateK3: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Fungsi helper trigger maturasi (Sudah benar)
    private function updateMaturasiTrigger(string $bakNameFromBasah, float $nilai, string $tanggalInput, ?string $asal_bokar = null, bool $isNettoKering = false): void
    {
        // ... (Kode ini sudah ada dan benar) ...
        $nomor = (int) filter_var($bakNameFromBasah, FILTER_SANITIZE_NUMBER_INT);
        if ($nomor == 0) {
            Log::error("Trigger Gagal: Tidak bisa menemukan nomor Bak dari '$bakNameFromBasah'");
            return;
        }
        $uraianMaturasi = "Di Bak Maturasi-" . $nomor; 

        $maturasi = Maturasi::where('uraian', $uraianMaturasi)->first();
        if (!$maturasi) { 
            Log::error("Trigger Gagal: Bak '$uraianMaturasi' tidak ditemukan di tabel maturasis.");
            return; 
        }

        $tanggalKejadian = Carbon::parse($tanggalInput);
        $keteranganLog = "";

        if (!$maturasi->updated_at || !$maturasi->updated_at->isSameDay($tanggalKejadian)) {
            $maturasi->stok_awal = $maturasi->stok_akhir;
            $maturasi->diolah = 0;
            $maturasi->mutasi = 0;
            $maturasi->masuk_hi = 0;
        }

        if ($isNettoKering) {
            $maturasi->masuk_hi += $nilai; 
            $keteranganLog = 'Otomatis dari Uji Bokar (Netto Kering)';
        } else {
            $keteranganLog = 'Otomatis dari Timbang Bokar (Netto Basah)';
        }

        $maturasi->stok_akhir = $maturasi->stok_awal - $maturasi->diolah - $maturasi->mutasi + $maturasi->masuk_hi;
        
        if ($isNettoKering && $nilai > 0) {
             $maturasi->tgl_masuk = $tanggalKejadian;
             $maturasi->umur = 0;
             $maturasi->keterangan = strtoupper(Carbon::parse($tanggalKejadian)->locale('en')->translatedFormat('d M Y'));
        }

        if (!empty($asal_bokar)) {
            if (empty($maturasi->asal_bokar)) {
                $maturasi->asal_bokar = $asal_bokar;
            } elseif ($maturasi->asal_bokar !== $asal_bokar && $maturasi->asal_bokar !== 'CMP') {
                $maturasi->asal_bokar = 'CMP';
            }
        }

        $maturasi->updated_at = $tanggalKejadian;
        $maturasi->save();

        PengolahanMaturasi::create([
            'maturasi_id' => $maturasi->id,
            'tgl_laporan' => $tanggalKejadian,
            'diolah' => 0, 
            'mutasi' => 0,
            'masuk_hi' => $isNettoKering ? $nilai : 0,
            'keterangan' => $keteranganLog
        ]);
        
        Log::info("Trigger Berhasil: Maturasi '$uraianMaturasi' diupdate.");
    }
}