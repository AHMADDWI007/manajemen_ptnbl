<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProduksiSir20;
use App\Models\RemahanSir20;
use App\Models\AktualTemperatureSir20;
use App\Models\BahanBakarSir20;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProduksiSir20ApiController extends Controller
{
    /**
     * [INDEX] Ambil List Data Produksi
     */
    public function index(Request $request)
    {
        try {
            $query = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])
                ->orderBy('tanggal_produksi', 'desc');

            if ($request->has('date')) {
                $query->whereDate('tanggal_produksi', $request->query('date'));
            }

            $data = $query->get();
            return response()->json(['success' => true, 'data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [STORE] Simpan Produksi Baru & Potong Stok Maturasi
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_produksi' => 'required|date',
            'shift_kerja'      => 'required',
        ]);

        if ($validator->fails()) return response()->json(['success'=>false, 'errors'=>$validator->errors()], 422);

        DB::beginTransaction();
        try {
            // 1. Simpan Header Produksi
            $produksi = ProduksiSir20::create($this->filterRequest($request));

            // 2. Simpan Remahan (dan Trigger Pengurangan Stok Maturasi)
            if ($request->has('maturasi') && is_array($request->maturasi)) {
                foreach ($request->maturasi as $item) {
                    // Cek kelengkapan data item
                    if (!empty($item['ruang']) && (isset($item['berat']) && $item['berat'] > 0)) {
                        $beratBersih = $this->cleanNumber($item['berat']);
                        
                        RemahanSir20::create([
                            'id_produksi_sir20' => $produksi->id_produksi_sir20,
                            'ruang_maturasi'    => $item['ruang'],
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($item['umur'] ?? 0),
                        ]);

                        // 🔥 TRIGGER: Tambah 'Diolah' -> Stok Maturasi Berkurang
                        $this->triggerUpdateMaturasi($item['ruang'], $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 3. Simpan Suhu & Bahan Bakar
            $this->saveTemperatures($produksi->id_produksi_sir20, $request);
            $this->saveBahanBakar($produksi->id_produksi_sir20, $request);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produksi berhasil disimpan.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [UPDATE] Edit Produksi & Sinkronisasi Ulang Stok
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $produksi = ProduksiSir20::findOrFail($id);
            $oldDate = $produksi->tanggal_produksi;

            // 1. Update Header
            $produksi->update($this->filterRequest($request));

            // 2. REVERT Stok Lama (Kembalikan stok seolah produksi sebelumnya batal)
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach($oldRemahan as $old) {
                // 'kurang' = Kurangi 'Diolah' -> Stok Maturasi Kembali
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }
            RemahanSir20::where('id_produksi_sir20', $id)->delete();

            // 3. Simpan Remahan Baru & Potong Stok Baru
            if ($request->has('maturasi') && is_array($request->maturasi)) {
                foreach ($request->maturasi as $item) {
                    if (!empty($item['ruang']) && (isset($item['berat']) && $item['berat'] > 0)) {
                        $beratBersih = $this->cleanNumber($item['berat']);
                        
                        RemahanSir20::create([
                            'id_produksi_sir20' => $id,
                            'ruang_maturasi'    => $item['ruang'],
                            'berat'             => $beratBersih,
                            'umur'              => $this->cleanNumber($item['umur'] ?? 0),
                        ]);

                        $this->triggerUpdateMaturasi($item['ruang'], $request->tanggal_produksi, $beratBersih, 'tambah');
                    }
                }
            }

            // 4. Update Suhu & BB (Reset & Insert)
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            $this->saveTemperatures($id, $request);

            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            $this->saveBahanBakar($id, $request);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Produksi diperbarui.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * [DESTROY] Hapus Data
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $produksi = ProduksiSir20::findOrFail($id);
            $oldDate = $produksi->tanggal_produksi;

            // Revert Stok Maturasi
            $oldRemahan = RemahanSir20::where('id_produksi_sir20', $id)->get();
            foreach($oldRemahan as $old) {
                $this->triggerUpdateMaturasi($old->ruang_maturasi, $oldDate, $old->berat, 'kurang');
            }

            // Hapus Child Manual (Opsional jika cascade on delete di DB sudah aktif)
            RemahanSir20::where('id_produksi_sir20', $id)->delete();
            AktualTemperatureSir20::where('id_produksi_sir20', $id)->delete();
            BahanBakarSir20::where('id_produksi_sir20', $id)->delete();
            
            $produksi->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPER FUNCTIONS (LOGIKA "THE BRAIN")
    // =========================================================================

    private function filterRequest(Request $request)
    {
        return [
            'tanggal_produksi'      => $request->tanggal_produksi,
            'shift_kerja'           => $request->shift_kerja,
            'jam_start_dryer'       => $request->jam_start_dryer,
            'jam_stop_dryer'        => $request->jam_stop_dryer,
            'jumlah_jam_dryer'      => $this->cleanNumber($request->jumlah_jam_dryer),
            'jumlah_trolly_masuk'   => $this->cleanNumber($request->jumlah_trolly_masuk),
            'jumlah_trolly_keluar'  => $this->cleanNumber($request->jumlah_trolly_keluar),
            'jumlah_bales_dipress'  => $this->cleanNumber($request->jumlah_bales_dipress),
            'kg_yang_dipress'       => $this->cleanNumber($request->kg_yang_dipress),
            'capacity_per_jam'      => $this->cleanNumber($request->capacity_per_jam),
            'jam_kerja'             => $this->cleanNumber($request->jam_kerja),
            'produktivitas'         => $this->cleanNumber($request->produktivitas),
            'kg_cake'               => $this->cleanNumber($request->kg_cake),
            'bales_terkontaminasi'  => $this->cleanNumber($request->bales_terkontaminasi),
            'berat_kontaminan'      => $this->cleanNumber($request->berat_kontaminan),
            'jam_operasional_genset'=> $this->cleanNumber($request->jam_operasional_genset),
            'pemakaian_listrik_pln' => $this->cleanNumber($request->pemakaian_listrik_pln),
            'jumlah_pallet'         => $this->cleanNumber($request->jumlah_pallet),
            'total_nomor'           => $this->cleanNumber($request->total_nomor),
            'mc_val'                => $this->cleanNumber($request->mc_val),
            'nomor_start'           => $request->nomor_start,
            'nomor_end'             => $request->nomor_end,
            'total_nomor_akhir'     => $this->cleanNumber($request->total_nomor_akhir),
            'keterangan'            => $request->keterangan
        ];
    }

    private function saveTemperatures($id, $request)
    {
        $tempData = [
            ['jenis' => 'Burner 1',   'start' => $request->temp_b1_start, 'end' => $request->temp_b1_end],
            ['jenis' => 'Burner 2',   'start' => $request->temp_b2_start, 'end' => $request->temp_b2_end],
            ['jenis' => 'Cycle Time', 'start' => $request->cycle_start,   'end' => $request->cycle_end],
        ];
        foreach ($tempData as $temp) {
            if ($temp['start'] || $temp['end']) {
                AktualTemperatureSir20::create([
                    'id_produksi_sir20' => $id,
                    'jenis'             => $temp['jenis'],
                    'nilai_start'       => $this->cleanNumber($temp['start']),
                    'nilai_end'         => $this->cleanNumber($temp['end']),
                ]);
            }
        }
    }

    private function saveBahanBakar($id, $request)
    {
        $bbData = [
            ['nama' => 'Solar',     'jumlah' => $request->bb_solar],
            ['nama' => 'Batu Bara', 'jumlah' => $request->bb_batubara],
            ['nama' => 'Cangkang',  'jumlah' => $request->bb_cangkang],
        ];
        foreach ($bbData as $bb) {
            if ($bb['jumlah'] > 0) {
                BahanBakarSir20::create([
                    'id_produksi_sir20' => $id,
                    'bahan_bakar'       => $bb['nama'],
                    'digunakan'         => $this->cleanNumber($bb['jumlah']),
                ]);
            }
        }
    }

    private function triggerUpdateMaturasi($namaRuang, $tanggal, $berat, $aksi)
    {
        if ($berat <= 0) return;
        $maturasi = Maturasi::where('uraian', $namaRuang)->first();
        
        if ($maturasi) {
            // Log Harian
            $log = PengolahanMaturasi::firstOrCreate(
                ['id_maturasi' => $maturasi->id_maturasi, 'tgl_laporan' => $tanggal],
                ['masuk_hi' => 0, 'diolah' => 0, 'mutasi' => 0, 'keterangan' => 'Auto Produksi']
            );

            if ($aksi === 'tambah') {
                $log->increment('diolah', $berat);
                $maturasi->decrement('stok_akhir', $berat); 
            } else {
                if ($log->diolah >= $berat) $log->decrement('diolah', $berat);
                else $log->update(['diolah' => 0]);
                $maturasi->increment('stok_akhir', $berat); 
            }
            
            // Bersihkan stok minus / kecil
            if ($maturasi->stok_akhir <= 0.01) {
                $maturasi->update(['stok_akhir' => 0, 'keterangan' => 'KOSONG', 'asal_bokar' => null]);
            } else {
                $maturasi->touch();
            }
        }
    }

    private function cleanNumber($value)
    {
        if (empty($value)) return 0;
        if (is_numeric($value)) return (float) $value;
        return (float) str_replace(',', '.', str_replace('.', '', (string)$value));
    }

    // TAMBAHAN KHUSUS API: Mengambil Nomor Terakhir
    public function getLastNumber()
    {
        // Ambil data terakhir berdasarkan ID (descending)
        $last = ProduksiSir20::orderBy('id_produksi_sir20', 'desc')->first();
        
        // Jika ada data, ambil total_nomor_akhir. Jika tidak ada (data pertama), mulai dari 0.
        $num = $last ? $last->total_nomor_akhir : 0;

        // 🔥 PERBAIKAN: Gunakan key 'data' agar terbaca oleh ApiResponse.java di Android
        return response()->json([
            'success' => true, 
            'data' => $num,  // <-- Ubah 'last_number' menjadi 'data'
            'message' => 'Nomor terakhir berhasil diambil'
        ]);
    }
}