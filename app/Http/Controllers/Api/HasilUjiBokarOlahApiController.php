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
use App\Models\HasilUjiLabBokarDiolah; 

class HasilUjiBokarOlahApiController extends Controller
{
    /**
     * [LIST UTAMA] Mengambil data history dari tabel hasil_uji_lab_bokar_diolah
     * Android Endpoint: GET /hasil-uji-bokar-olah
     */
    public function index(Request $request) // 🔥 TERIMA REQUEST
    {
        try {
            $query = HasilUjiLabBokarDiolah::with(['pengolahanBasah', 'maturasi']);

            // 🔥 FILTER TANGGAL JIKA ADA REQUEST 'date' DARI ANDROID
            if ($request->has('date')) {
                $date = Carbon::parse($request->query('date'))->format('Y-m-d');
                $query->whereDate('tanggal', $date);
            }

            $data = $query->orderBy('tanggal', 'desc')
                        ->get()
                        ->map(function($item) {
                            return [
                                'id' => $item->id_hasil_uji_lab_bokar_diolah, // ID History
                                'pengolahan_basah_id' => $item->id_pengolahan_basah, // ID Timbang
                                'tanggal' => Carbon::parse($item->tanggal)->format('Y-m-d'),
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
            $data = PengolahanBasah::with('maturasi')
                        ->whereNull('k3')
                        ->orderBy('tanggal', 'desc')
                        ->get()
                        ->map(function($item) {
                            return [
                                'id_pengolahan_basah' => $item->id_pengolahan_basah, 
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
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'id_pengolahan_basah' => 'required|exists:pengolahan_basah,id_pengolahan_basah',
                'k3' => 'required|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $dataBasah = PengolahanBasah::where('id_pengolahan_basah', $request->id_pengolahan_basah)->firstOrFail();
            
            $k3 = $request->k3;
            $netto_kering = $dataBasah->netto_basah * ($k3 / 100);

            $dataBasah->update([
                'k3'            => $k3,
                'netto_kering' => $netto_kering
            ]);

            $log = HasilUjiLabBokarDiolah::create([
                'id_pengolahan_basah' => $dataBasah->id_pengolahan_basah,
                'tanggal'             => $dataBasah->tanggal,
                'id_maturasi'         => $dataBasah->id_maturasi,
                'jenis'               => $dataBasah->jenis,
                'netto_basah'         => $dataBasah->netto_basah,
                'k3'                  => $k3,
                'netto_kering'        => $netto_kering
            ]);

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
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * [UPDATE] Edit K3 yang sudah ada
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
            
            // 🔥 PERBAIKAN: Gunakan updateOrCreate untuk API Mobile
            HasilUjiLabBokarDiolah::updateOrCreate(
                ['id_pengolahan_basah' => $id],
                [
                    'id_maturasi'  => $dataBasah->id_maturasi,
                    'tanggal'      => $dataBasah->tanggal,
                    'jenis'        => $dataBasah->jenis,
                    'netto_basah'  => $dataBasah->netto_basah,
                    'k3'           => $k3,
                    'netto_kering' => $netto_kering
                ]
            );

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
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $history = HasilUjiLabBokarDiolah::where('id_hasil_uji_lab_bokar_diolah', $id)->firstOrFail();
            $idPengolahan = $history->id_pengolahan_basah;
            $idMaturasi = $history->id_maturasi;
            $tanggal = $history->tanggal;
            $jenis = $history->jenis;

            $history->delete();

            PengolahanBasah::where('id_pengolahan_basah', $idPengolahan)->update([
                'k3' => null,
                'netto_kering' => null
            ]);

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
        $maturasi = Maturasi::find($idMaturasi);
        if (!$maturasi) return;

        $tgl = Carbon::parse($tanggal);
        
        $log = PengolahanMaturasi::firstOrNew([
            'id_maturasi' => $idMaturasi,
            'tgl_laporan' => $tgl
        ]);

        $totalMasukHariIni = HasilUjiLabBokarDiolah::where('id_maturasi', $idMaturasi)
            ->whereDate('tanggal', $tgl)
            ->sum('netto_kering');

        $log->masuk_hi = $totalMasukHariIni;
        
        if (empty($log->keterangan)) {
            $log->keterangan = 'Fisik Bokar Masuk dari API';
        }
        $log->save();

        $stokAwalH1 = round(PengolahanMaturasi::where('id_maturasi', $idMaturasi)
            ->whereDate('tgl_laporan', '<', $tgl)
            ->sum(DB::raw('masuk_hi - diolah - mutasi')), 2);

        $masuk  = $log->masuk_hi;
        $keluar = $log->diolah + $log->mutasi;
        
        $maturasi->stok_awal  = $stokAwalH1;
        $maturasi->stok_akhir = $stokAwalH1 + $masuk - $keluar;
        $maturasi->updated_at = $tgl;
        
        if ($maturasi->stok_akhir > 0) {
            $listJenis = PengolahanBasah::where('id_maturasi', $idMaturasi)
                        ->where('tanggal', $tanggal)
                        ->pluck('jenis')
                        ->filter(function ($value) { return !is_null($value) && $value !== '' && $value !== 'PENDING'; }) 
                        ->unique()
                        ->sort()
                        ->values();

            $labelAkhir = '';
            if ($listJenis->count() > 1) {
                $rincian = $listJenis->implode(', ');
                $labelAkhir = "CMP ($rincian)";
            } 
            elseif ($listJenis->count() == 1) {
                $labelAkhir = $listJenis->first();
            } 
            else {
                $labelAkhir = $maturasi->asal_bokar; 
            }

            $maturasi->asal_bokar = $labelAkhir; 
            
            // 🔥 PERBAIKAN: Update keterangan langsung di hari H
            if ($masuk > 0) {
                $maturasi->keterangan = strtoupper($tgl->translatedFormat('d M Y'));
                // ❌ DILARANG update tgl_masuk dan umur di sini! (Biar Web Admin yang handle simulasinya)
            }

        } else {
            $maturasi->keterangan = 'KOSONG'; 
            $maturasi->asal_bokar = null;
        }

        $maturasi->save();
    }
}