<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // Pastikan ini ada
use App\Models\PengolahanBasah; 
use App\Models\HasilUjiBokarDiolah; 
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB; // Pastikan ini ada

class HasilUjiBokarOlahApiController extends Controller
{
    /**
     * [TABEL 2] Mengambil data yang K3-nya SUDAH diisi.
     * Endpoint: GET /hasil-uji-bokar-olah
     */
    public function index()
    {
        try {
            // Query JOIN untuk mengambil ID asli dari pengolahan_basah
            $data = HasilUjiBokarDiolah::select([
                    'hasil_uji_bokar_diolah.*', 
                    DB::raw('pengolahan_basah.id as pengolahan_basah_id') 
                ])
                ->leftJoin('pengolahan_basah', function($join) {
                    $join->on('pengolahan_basah.bak_maturasi', '=', 'hasil_uji_bokar_diolah.bak_maturasi')
                         ->on('pengolahan_basah.tanggal', '=', 'hasil_uji_bokar_diolah.tanggal');
                })
                ->latest('hasil_uji_bokar_diolah.tanggal') 
                ->latest('hasil_uji_bokar_diolah.created_at')
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Data Uji Bokar Olah berhasil diambil.',
                'data' => $data
            ], 200);
        
        } catch (\Exception $e) {
            Log::error('Error index HasilUjiBokarOlah: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [FORM 2 - Simpan] Menerima data BARU untuk tabel hasil_uji_bokar_diolah.
     * Endpoint: POST /hasil-uji-bokar-olah
     */
    // ✅ PERHATIKAN: (Request $request) SUDAH ADA DI SINI
    public function store(Request $request) 
    {
        try {
            // $request sekarang sudah terdefinisi
            $validatedData = $request->validate([
                'tanggal' => 'required|date_format:Y-m-d H:i:s,Y-m-d\TH:i:s.u\Z,Y-m-d',
                'bak_maturasi' => 'required|string|unique:hasil_uji_bokar_diolah,bak_maturasi,NULL,id,tanggal,' . $request->tanggal, // $request sudah benar
                'jenis' => 'required|string',
                'netto_basah' => 'required|numeric',
                'k3' => 'required|numeric',
                'netto_kering' => 'required|numeric',
                'pengolahan_basah_id' => 'nullable|integer|exists:pengolahan_basah,id'
            ],[
                'bak_maturasi.unique' => 'Data K3 untuk Bak Maturasi & Tanggal ini sudah diinput.'
            ]);

            $dataBaru = HasilUjiBokarDiolah::create($validatedData);

            return response()->json([
                'success' => true, 
                'data' => $dataBaru, 
                'message' => 'Data berhasil disimpan ke tabel hasil uji'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error store HasilUjiBokarOlah: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    // ==========================================================
    // ✅ PERBAIKAN: TAMBAHKAN FUNGSI BARU UNTUK UPDATE K3
    // (Logika disalin dari HasilUjiBokarDiolahController@update milik Web)
    // ==========================================================
    public function updateK3(Request $request, $id)
    {
        try {
            // $id di sini adalah ID dari 'pengolahan_basah'
            $data = PengolahanBasah::findOrFail($id); 

            $validator = Validator::make($request->all(), [
                'k3' => 'required|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
            }
            
            $k3_value = (double)$request->k3;
            $netto_kering = $data->netto_basah * ($k3_value / 100);

            // 1. Update 'pengolahan_basah' (Tabel 1)
            $data->update([
                'k3' => $k3_value,
                'netto_kering' => $netto_kering
            ]);
            
            // 2. Update log 'hasil_uji_bokar_diolah' (Tabel 2)
            HasilUjiBokarDiolah::where('bak_maturasi', $data->bak_maturasi)
                                ->where('tanggal', $data->tanggal)
                                ->update([
                                    'k3' => $k3_value,
                                    'netto_kering' => $netto_kering
                                ]);
                                
            // 3. JALANKAN TRIGGER KE MATURASI
            // (Pastikan fungsi updateMaturasiMasukHI ada di bawah)
            $this->updateMaturasiMasukHI($data->bak_maturasi, $netto_kering, $data->tanggal, $data->jenis);

            return response()->json(['success' => true, 'message' => 'Data K3 berhasil diperbarui.']);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Data Timbang Bokar (ID ' . $id . ') tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Error updateK3 HasilUjiBokarOlah: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [TABEL 2] "Hapus" data.
     * Endpoint: DELETE /uji-bokar-olah/{id}
     */
    // ✅ PERHATIKAN: ($id) SUDAH ADA DI SINI
    public function destroy($id) 
    {
        try {
            // $id sekarang sudah terdefinisi
            $dataOlah = HasilUjiBokarDiolah::findOrFail($id); 

            $bakMaturasi = $dataOlah->bak_maturasi;
            $tanggal = $dataOlah->tanggal;

            $dataTimbang = PengolahanBasah::where('bak_maturasi', $bakMaturasi)
                                             ->where('tanggal', $tanggal)
                                             ->first();

            if ($dataTimbang) {
                $dataTimbang->k3 = null;
                $dataTimbang->netto_kering = null;
                $dataTimbang->save();
            }

            $dataOlah->delete();

            return response()->json([
                'success' => true, 
                'message' => 'Data K3 berhasil direset (dihapus dari tabel olah).'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Data olah tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Error destroy HasilUjiBokarOlah: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================================
    // ✅ PERBAIKAN: Salin fungsi TRIGGER dari Controller Web
    // (Dibutuhkan oleh fungsi updateK3 di atas)
    // ==========================================================
    private function updateMaturasiMasukHI(string $bakNameFromBasah, float $masuk_hi, string $tanggalInput, ?string $asal_bokar = null): void
    {
        try {
            $nomor = (int) filter_var($bakNameFromBasah, FILTER_SANITIZE_NUMBER_INT);
            if ($nomor == 0) return;
            $uraianMaturasi = "Di Bak Maturasi-" . $nomor;

            $maturasi = Maturasi::where('uraian', $uraianMaturasi)->first();
            if (!$maturasi) return; 

            $tanggalKejadian = Carbon::parse($tanggalInput);

            // Cek apakah log untuk hari ini sudah ada
            $logHariIni = PengolahanMaturasi::where('maturasi_id', $maturasi->id)
                                ->whereDate('tgl_laporan', $tanggalKejadian)
                                ->where('keterangan', 'LIKE', '%Otomatis dari Uji Bokar%')
                                ->first();
            
            if ($logHariIni) {
                // Jika log sudah ada (misal: di-update), update nilainya
                $logHariIni->masuk_hi = $masuk_hi;
                $logHariIni->save();
            } else {
                // Jika log belum ada, buat baru
                PengolahanMaturasi::create([
                    'maturasi_id' => $maturasi->id,
                    'tgl_laporan' => $tanggalKejadian,
                    'diolah' => 0, 'mutasi' => 0,
                    'masuk_hi' => $masuk_hi,
                    'keterangan' => 'Otomatis dari Uji Bokar'
                ]);
            }

            // Logika Asal Bokar (CMP)
            if (!empty($asal_bokar)) {
                if (empty($maturasi->asal_bokar)) {
                    $maturasi->asal_bokar = $asal_bokar;
                } elseif ($maturasi->asal_bokar !== $asal_bokar && $maturasi->asal_bokar !== 'CMP') {
                    $maturasi->asal_bokar = 'CMP';
                }
                $maturasi->save();
            }

            Log::info("Trigger API Berhasil: Maturasi '$uraianMaturasi' diupdate dengan 'masuk_hi' $masuk_hi");
        } catch (\Exception $e) {
            Log::error("Trigger API Gagal: " . $e->getMessage());
        }
    }
}