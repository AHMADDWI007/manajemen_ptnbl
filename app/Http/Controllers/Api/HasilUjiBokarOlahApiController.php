<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PengolahanBasah;
use App\Traits\MaturasiSyncTrait; // 🔥 Import Trait
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\HasilUjiLabBokarDiolah; 

class HasilUjiBokarOlahApiController extends Controller
{
    use MaturasiSyncTrait; // 🔥 Gunakan Trait agar logika hitung stok sama dengan Web

    public function index(Request $request)
    {
        try {
            $query = HasilUjiLabBokarDiolah::with(['pengolahanBasah', 'maturasi']);

            if ($request->has('date') && !empty($request->date)) {
                $query->whereDate('tanggal', $request->date);
            }

            $data = $query->orderBy('tanggal', 'desc')
                        ->get()
                        ->map(function($item) {
                            return [
                                'id' => $item->id_hasil_uji_lab_bokar_diolah,
                                'pengolahan_basah_id' => $item->id_pengolahan_basah,
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
                                'netto_basah' => $item->netto_basah,
                            ];
                        });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

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

            $dataBasah = PengolahanBasah::findOrFail($request->id_pengolahan_basah);
            $netto_kering = $dataBasah->netto_basah * ($request->k3 / 100);

            // 1. Update Induk
            $dataBasah->update([
                'k3' => $request->k3,
                'netto_kering' => $netto_kering
            ]);

            // 2. Create Log (Gunakan updateOrCreate agar aman)
            $log = HasilUjiLabBokarDiolah::updateOrCreate(
                ['id_pengolahan_basah' => $dataBasah->id_pengolahan_basah],
                [
                    'tanggal'      => $dataBasah->tanggal,
                    'id_maturasi'  => $dataBasah->id_maturasi,
                    'jenis'        => $dataBasah->jenis,
                    'netto_basah'  => $dataBasah->netto_basah,
                    'k3'           => $request->k3,
                    'netto_kering' => $netto_kering
                ]
            );

            // 3. 🔥 Panggil Trait (Standar Web)
            $this->syncMaturasi($dataBasah->id_maturasi, $dataBasah->tanggal);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'K3 disimpan & Stok diperbarui.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateK3(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $dataBasah = PengolahanBasah::findOrFail($id);
            $netto_kering = $dataBasah->netto_basah * ($request->k3 / 100);

            // 1. Update Induk
            $dataBasah->update(['k3' => $request->k3, 'netto_kering' => $netto_kering]);
            
            // 2. Update Log
            HasilUjiLabBokarDiolah::updateOrCreate(
                ['id_pengolahan_basah' => $id],
                [
                    'id_maturasi'  => $dataBasah->id_maturasi,
                    'tanggal'      => $dataBasah->tanggal,
                    'jenis'        => $dataBasah->jenis,
                    'netto_basah'  => $dataBasah->netto_basah,
                    'k3'           => $request->k3,
                    'netto_kering' => $netto_kering
                ]
            );

            // 3. 🔥 Panggil Trait
            $this->syncMaturasi($dataBasah->id_maturasi, $dataBasah->tanggal);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data K3 diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Karena di Android $id yang dikirim bisa ID Log atau ID Timbang, pastikan dulu
            $history = HasilUjiLabBokarDiolah::where('id_hasil_uji_lab_bokar_diolah', $id)->first();
            
            if (!$history) {
                 return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            }

            $idBasah    = $history->id_pengolahan_basah;
            $idMaturasi = $history->id_maturasi;
            $tanggal    = $history->tanggal;

            // 1. Hapus Log
            $history->delete();

            // 2. Null-kan di Pengolahan Basah
            PengolahanBasah::where('id_pengolahan_basah', $idBasah)->update([
                'k3' => null,
                'netto_kering' => null
            ]);

            // 3. 🔥 Panggil Trait
            $this->syncMaturasi($idMaturasi, $tanggal);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data K3 di-reset.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    // ❌ FUNGSI syncToMaturasi LAMA DIHAPUS SEMUA KARENA SUDAH DIGANTI TRAIT
}