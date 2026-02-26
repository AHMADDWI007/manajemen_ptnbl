<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Traits\MaturasiSyncTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimbangBokarApiController extends Controller
{

    use MaturasiSyncTrait;

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
                'bak_maturasi'  => 'required|string', 
                'berat_truck'   => 'required|numeric|min:0',
                'berat_timbang' => 'required|numeric|min:0',
            ]);

            $maturasi = Maturasi::where('uraian', $request->bak_maturasi)->first();
            if (!$maturasi) {
                return response()->json(['success' => false, 'message' => 'Bak Maturasi tidak ditemukan'], 404);
            }

            $netto_basah = $request->berat_timbang - $request->berat_truck;

            $basah = PengolahanBasah::create([
                'tanggal'       => $request->tanggal,
                'jenis'         => 'PENDING', 
                'id_maturasi'   => $maturasi->id_maturasi,
                'berat_truck'   => $request->berat_truck,
                'berat_timbang' => $request->berat_timbang,
                'netto_basah'   => $netto_basah,
                'k3'            => null,
                'netto_kering'  => null,
            ]);

            // 🔥 PERBAIKAN: Panggil Trait agar stok langsung muncul di Web
            $this->syncMaturasi($maturasi->id_maturasi, $request->tanggal);

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
        $data = PengolahanBasah::where('id_pengolahan_basah', $id)->firstOrFail();
        $tanggalLama = \Carbon\Carbon::parse($data->tanggal)->format('Y-m-d');
        $idMaturasiLama = $data->id_maturasi;

        $request->validate([
            'tanggal'       => 'required|date_format:Y-m-d',
            'bak_maturasi'  => 'required|string', 
            'berat_truck'   => 'required|numeric|min:0',
            'berat_timbang' => 'required|numeric|min:0',
        ]);

        $maturasiBaru = Maturasi::where('uraian', $request->bak_maturasi)->first();
        if (!$maturasiBaru) {
            return response()->json(['success' => false, 'message' => 'Bak tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            $netto_basah = $request->berat_timbang - $request->berat_truck;
            
            // 1. Update Tabel Utama
            $data->update([
                'tanggal'       => $request->tanggal,
                'id_maturasi'   => $maturasiBaru->id_maturasi,
                'berat_truck'   => $request->berat_truck,
                'berat_timbang' => $request->berat_timbang,
                'netto_basah'   => $netto_basah,
            ]);

            // 2. 🔥 Update juga Tanggal di rincian lab agar filter dashboard sinkron
            HasilUjiLabBokarDiolah::where('id_pengolahan_basah', $id)->update([
                'tanggal'     => $request->tanggal,
                'id_maturasi' => $maturasiBaru->id_maturasi
            ]);

            // 3. 🔥 Panggil Trait untuk pembersihan tanggal lama dan update tanggal baru
            $this->syncMaturasi($idMaturasiLama, $tanggalLama);
            $this->syncMaturasi($maturasiBaru->id_maturasi, $request->tanggal);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Berhasil pindah']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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

            // 3. 🔥 PERBAIKAN: Panggil Trait untuk hitung ulang sisa stok
            $this->syncMaturasi($id_maturasi, $tanggal);

            return response()->json(['success' => true, 'message' => 'Data dihapus dan stok disesuaikan']);
        }
        return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
    }
}