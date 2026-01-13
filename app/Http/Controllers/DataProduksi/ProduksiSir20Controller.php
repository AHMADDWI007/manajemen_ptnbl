<?php

namespace App\Http\Controllers\DataProduksi;

use Carbon\Carbon;
use App\Models\Maturasi;
use App\Models\RemahanSir20;
use Illuminate\Http\Request;
use App\Models\ProduksiSir20;
use App\Models\BahanBakarSir20;
use App\Models\PengolahanMaturasi;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AktualTemperatureSir20;

class ProduksiSir20Controller extends Controller
{
    public function index()
    {
        $history = ProduksiSir20::with(['remahan'])->orderBy('tanggal_produksi', 'desc')->get();
        
        $bak_aktif = Maturasi::where('stok_akhir', '>', 0)
                             ->orderBy('uraian', 'asc')
                             ->get();

        $hari_ini = Carbon::today()->startOfDay();

        foreach ($bak_aktif as $bak) {
            // --- LOGIKA HITUNG UMUR YANG LEBIH CERDAS (MIRIP MATURASI CONTROLLER) ---
            
            // 1. Cek apakah ada log masuk (masuk_hi > 0) sebelum atau sama dengan hari ini?
            // Kita cari tanggal terakhir stok masuk ke bak ini
            $lastLog = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                ->where('masuk_hi', '>', 0)
                ->whereDate('tgl_laporan', '<=', $hari_ini)
                ->orderBy('tgl_laporan', 'desc')
                ->first();

            $tgl_acuan = null;

            if ($lastLog) {
                // Jika ada history masuk, pakai tanggal history itu
                $tgl_acuan = Carbon::parse($lastLog->tgl_laporan)->startOfDay();
            } elseif (!empty($bak->tgl_masuk)) {
                // Fallback ke master jika log tidak ketemu (jarang terjadi jika data bersih)
                $tgl_acuan = Carbon::parse($bak->tgl_masuk)->startOfDay();
            }

            // 2. Hitung Umur
            if ($tgl_acuan) {
                // Selisih hari dari Tgl Masuk Terakhir s/d Hari Ini
                $bak->umur_real = abs($tgl_acuan->diffInDays($hari_ini));
            } else {
                $bak->umur_real = 0;
            }
        }
        
        return view('DataProduksi.produksi-sir20', compact('history', 'bak_aktif'));
    }

    public function show($id)
    {
        // 🔥 [PERBAIKAN] find($id) otomatis cari di PK 'id_produksi_sir20'
        $data = ProduksiSir20::with(['remahan', 'aktualTemperature', 'bahanBakar'])
                ->findOrFail($id);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_produksi' => 'required|date',
            'shift_kerja'      => 'required',
        ]);

        DB::beginTransaction(); 

        try {
            // 1. SIMPAN HEADER PRODUKSI
            $produksi = ProduksiSir20::create([
                'tanggal_produksi'      => $request->tanggal_produksi,
                'shift_kerja'           => $request->shift_kerja,
                
                'jam_start_dryer'       => $request->jam_start_dryer,
                'jumlah_trolly_masuk'   => $this->cleanNumber($request->trolly_masuk),
                'jumlah_trolly_keluar'  => $this->cleanNumber($request->trolly_keluar),
                'jam_stop_dryer'        => $request->jam_stop_dryer,
                'jumlah_jam_dryer'      => $this->cleanNumber($request->jam_jalan_dryer),

                'jumlah_bales_dipress'  => $this->cleanNumber($request->jumlah_bales),
                'kg_yang_dipress'       => $this->cleanNumber($request->kg_press),
                'capacity_per_jam'      => $this->cleanNumber($request->input('capacity_per_jam', 0)),
                'jam_kerja'             => $this->cleanNumber($request->jam_kerja),
                'produktivitas'         => $this->cleanNumber($request->input('produktivitas', 0)),

                'kg_cake'               => $this->cleanNumber($request->kg_sir20),
                'bales_terkontaminasi'  => $this->cleanNumber($request->bales_kontamin),
                'berat_kontaminan'      => $this->cleanNumber($request->berat_kontaminan),
                'jam_operasional_genset'=> $this->cleanNumber($request->jam_genset),
                'pemakaian_listrik_pln' => $this->cleanNumber($request->pln_kwh),

                'jumlah_pallet'         => $this->cleanNumber($request->jml_pallet),
                'total_nomor'           => $this->cleanNumber($request->total_nomor),
                'mc_val'                => $this->cleanNumber($request->mc_val),
                'nomor_start'           => $request->nomor_start,
                'nomor_end'             => $request->nomor_end,
                'total_nomor_akhir'     => $this->cleanNumber($request->total_nomor_akhir),
            ]);

            // 2. SIMPAN REMAHAN
            if ($request->has('maturasi')) {
                foreach ($request->maturasi as $item) {
                    if (!empty($item['ruang']) || !empty($item['berat'])) {
                        RemahanSir20::create([
                            'id_produksi_sir20' => $produksi->id_produksi_sir20, // 🔥 [PERBAIKAN PK]
                            'ruang_maturasi'    => $item['ruang'],
                            'berat'             => $this->cleanNumber($item['berat']),
                            'umur'              => $this->cleanNumber($item['umur']),
                        ]);
                    }
                }
            }

            // 3. SIMPAN AKTUAL TEMPERATURE
            $tempData = [
                ['jenis' => 'Burner 1',   'start' => $request->temp_b1_start, 'end' => $request->temp_b1_end],
                ['jenis' => 'Burner 2',   'start' => $request->temp_b2_start, 'end' => $request->temp_b2_end],
                ['jenis' => 'Cycle Time', 'start' => $request->cycle_start,   'end' => $request->cycle_end],
            ];

            foreach ($tempData as $temp) {
                if ($temp['start'] || $temp['end']) {
                    AktualTemperatureSir20::create([
                        'id_produksi_sir20' => $produksi->id_produksi_sir20, // 🔥 [PERBAIKAN PK]
                        'jenis'             => $temp['jenis'],
                        'nilai_start'       => $this->cleanNumber($temp['start']),
                        'nilai_end'         => $this->cleanNumber($temp['end']),
                    ]);
                }
            }

            // 4. SIMPAN BAHAN BAKAR
            $bbData = [
                ['nama' => 'Solar',     'jumlah' => $request->bb_solar],
                ['nama' => 'Batu Bara', 'jumlah' => $request->bb_batubara],
                ['nama' => 'Cangkang',  'jumlah' => $request->bb_cangkang],
            ];

            foreach ($bbData as $bb) {
                $cleanJumlah = $this->cleanNumber($bb['jumlah']);
                if ($cleanJumlah > 0) {
                    BahanBakarSir20::create([
                        'id_produksi_sir20' => $produksi->id_produksi_sir20, // 🔥 [PERBAIKAN PK]
                        'bahan_bakar'       => $bb['nama'],
                        'digunakan'         => $cleanJumlah,
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data Produksi Berhasil Disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    private function cleanNumber($value)
    {
        if (empty($value)) return 0;
        $string = (string) $value;
        $string = str_replace('.', '', $string);
        $string = str_replace(',', '.', $string);
        return (float) $string;
    }
}