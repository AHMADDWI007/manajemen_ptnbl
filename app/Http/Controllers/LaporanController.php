<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// Import Model
use App\Models\TransaksiApiBokar;
use App\Models\PengolahanBasah;
use App\Models\RektifikasiStok;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\HasilUjiLabMaturasi;
use App\Models\BahanProses;
use App\Models\ProduksiSir; 
use App\Models\ProduksiSir20; 
use App\Models\PenjualanSir20;

// Import Export Excel
use App\Exports\LaporanHarianExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    // =========================================================================
    // 1. TAMPILAN DASHBOARD (INDEX)
    // =========================================================================
    public function index(Request $request)
    {
        $data = $this->getDataLaporan($request->input('tanggal'));
        return view('Laporan.laporan', $data);
    }

    // =========================================================================
    // 2. PREVIEW CETAK PDF (HTML)
    // =========================================================================
    public function previewCetak(Request $request)
    {
        // 1. Ambil data (Logika sama persis)
        $data = $this->getDataLaporan($request->input('tanggal'));

        // 2. Return View Cetak
        return view('Cetak.cetak-laporan', $data);
    }

    // =========================================================================
    // 3. EXPORT EXCEL (TERBARU) 🚀
    // =========================================================================
    public function exportExcel(Request $request)
    {
        // 1. Ambil Data
        $data = $this->getDataLaporan($request->input('tanggal'));
        
        // 2. Format Nama File
        $tglFile = $data['tanggal']->format('d-m-Y');
        $namaFile = "Laporan_Harian_Produksi_{$tglFile}.xlsx";

        // 🔥 MEMBERSIHKAN BUFFER (HARD RESET) 🔥
        // Hapus semua buffer yang ada supaya file excel murni
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        // Jaga-jaga kalau ada multiple buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 3. Download
        return Excel::download(new LaporanHarianExport($data), $namaFile);
    }

    // =========================================================================
    // 🔥 CORE LOGIC: PENGAMBILAN DATA (PUSAT DATA)
    // =========================================================================
    private function getDataLaporan($tglInput)
    {
        $tanggal = $tglInput ? Carbon::parse($tglInput) : Carbon::today();
        
        // Panggil fungsi-fungsi kecil untuk setiap bagian laporan
        $rekapBokar   = $this->getRekapBokar($tanggal);
        $dataMaturasi = $this->getDataMaturasi($tanggal);
        $dataWip      = $this->getDataWip($tanggal);
        $gudangMutu   = $this->getDataGudangMutu($tanggal); // Return array [gudang, mutu]
        $dataPenjualan = $this->getDataPenjualan($tanggal);

        // Satukan hasilnya dalam satu array
        return [
            'tanggal'       => $tanggal,
            'rekapBokar'    => $rekapBokar,
            'dataMaturasi'  => $dataMaturasi,
            'dataWip'       => $dataWip,
            'dataGudang'    => $gudangMutu['gudang'],
            'dataMutu'      => $gudangMutu['mutu'],
            'dataPenjualan' => $dataPenjualan
        ];
    }

    // =========================================================================
    // A. LOGIKA BOKAR (TABEL I)
    // =========================================================================
    private function getRekapBokar($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $startOfMonth = $tanggal->copy()->startOfMonth();
        $yesterday = $tanggal->copy()->subDay();

        $rekapBokar = [];
        $mapBokar = ['PT' => 'ptpn', 'DS' => 'petani', 'INHUT' => 'inhut'];
        $rektifRecords = RektifikasiStok::where('tanggal', $tglStr)->get()->keyBy('jenis');

        foreach ($mapBokar as $jenis => $kodeApi) {
            // Hitung Stok Awal
            $masuk_kemarin = TransaksiApiBokar::where('kode_api', $kodeApi)->where('tanggal', '<=', $yesterday)->sum('masuk_hi');
            $olah_kemarin  = PengolahanBasah::where('jenis', $jenis)->where('tanggal', '<=', $yesterday)->sum('netto_kering');
            $rektif_kemarin = RektifikasiStok::where('jenis', $jenis)->where('tanggal', '<=', $yesterday)->sum('berat');
            $stok_awal = max(0, $masuk_kemarin - $olah_kemarin + $rektif_kemarin);

            // Data Hari Ini
            $masuk_hi = TransaksiApiBokar::where('kode_api', $kodeApi)->where('tanggal', $tglStr)->value('masuk_hi') ?? 0;
            $data_olah = PengolahanBasah::where('jenis', $jenis)->whereDate('tanggal', $tglStr)
                ->selectRaw('SUM(netto_basah) as basah, SUM(netto_kering) as kering')->first();
            
            // Data S/D Hari Ini
            $masuk_sd_kemarin = 0;
            if ($yesterday->gte($startOfMonth)) {
                $masuk_sd_kemarin = TransaksiApiBokar::where('kode_api', $kodeApi)
                    ->whereBetween('tanggal', [$startOfMonth, $yesterday])->sum('masuk_hi');
            }
            $olah_sdhi = PengolahanBasah::where('jenis', $jenis)->whereBetween('tanggal', [$startOfMonth, $tglStr])->sum('netto_kering');
            $rektif_today = $rektifRecords->has($jenis) ? (float)$rektifRecords[$jenis]->berat : 0;

            $rekapBokar[$jenis] = [
                'stok_awal'   => $stok_awal,
                'basah_hi'    => $data_olah->basah ?? 0,
                'kering_hi'   => $data_olah->kering ?? 0,
                'basah_sdhi'  => $masuk_sd_kemarin, 
                'masuk_hi'    => $masuk_hi,
                'masuk_sdhi'  => $masuk_sd_kemarin + $masuk_hi,
                'kering_sdhi' => $olah_sdhi,
                'rektif'      => $rektif_today
            ];
        }
        return $rekapBokar;
    }

    // =========================================================================
    // B. LOGIKA MATURASI (TABEL II)
    // =========================================================================
    private function getDataMaturasi($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
        $dataMaturasi = new Collection();

        foreach ($data_maturasi_db as $bak) {
            $hasLog = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', '<=', $tglStr)->exists();
            
            if (!$hasLog) {
                $row = $this->createMaturasiObj($bak, 0, 0, 0, 0, 0, null, 0, '-');
            } else {
                // Logic Snapshot Stok Awal
                $sums = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', '<', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                $stok_awal = $sums ? ($sums->m - $sums->d - $sums->u) : 0;

                // Transaksi Hari Ini
                $s = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                
                $trans_masuk = (float)($s->m ?? 0);
                $trans_diolah = (float)($s->d ?? 0);
                $trans_mutasi = (float)($s->u ?? 0);

                // Cek Masuk dari Uji Bokar (Backup)
                $masuk_from_uji = (float) HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', $tglStr)->sum('netto_kering');
                $masuk_hi_today = max($trans_masuk, $masuk_from_uji);
                
                $stok_akhir = $stok_awal + $masuk_hi_today - $trans_diolah - $trans_mutasi;

                // 🔥 PERBAIKAN LOGIKA UMUR DISINI AGAR SINKRON 🔥
                $tgl_masuk = null; $umur = 0; $keterangan = '-';
                
                if ($stok_akhir > 0) {
                    // Cek history masuk sebelum hari ini dulu (Prioritas 1)
                    $log = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', '<', $tglStr)
                            ->where('masuk_hi', '>', 0)->orderBy('tgl_laporan', 'desc')->first();

                    if ($log) {
                        // Jika ada history lama, gunakan tanggal itu agar umur lanjut
                        $lastDate = Carbon::parse($log->tgl_laporan);
                        $tgl_masuk = $lastDate->toDateString();
                        $umur = $lastDate->diffInDays($tanggal);
                        $keterangan = strtoupper($lastDate->format('d M Y'));

                    } elseif ($masuk_hi_today > 0) {
                        // Jika tidak ada history, tapi hari ini masuk -> Umur 0 (Batch Baru)
                        $tgl_masuk = $tglStr;
                        $umur = 0;
                        $keterangan = strtoupper($tanggal->format('d M Y'));

                    } elseif ($bak->tgl_masuk) {
                        // Fallback ke data master manual
                         $candidate = Carbon::parse($bak->tgl_masuk);
                         if ($candidate->lte($tanggal)) {
                            $tgl_masuk = $candidate->toDateString();
                            $umur = $candidate->diffInDays($tanggal);
                            $keterangan = strtoupper($candidate->format('d M Y'));
                         }
                    }
                }
                
                $row = $this->createMaturasiObj($bak, $stok_awal, $trans_diolah, $trans_mutasi, $masuk_hi_today, $stok_akhir, $tgl_masuk, $umur, $keterangan);
            }
            
            // Quality Data
            $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', '<=', $tglStr)->orderBy('tanggal', 'desc')->first();
            $row->k3_olah = $ujiLab->k3 ?? 0;
            $row->po      = $ujiLab->po ?? '-';
            $row->pri     = $ujiLab->pri ?? '-';
            $row->asal_bokar = ($row->stok_akhir <= 0) ? '-' : ($bak->asal_bokar ?? '-');

            $dataMaturasi->push($row);
        }
        return $dataMaturasi;
    }

    // =========================================================================
    // C. LOGIKA WIP (TABEL III)
    // =========================================================================
    private function getDataWip($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $masterUraianWip = [
            'Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)',
            'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)',
            'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'
        ];

        // Ambil Data Pendukung
        $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $tglStr)
            ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
        $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;
        $realProduction = ProduksiSir20::whereDate('tanggal_produksi', $tglStr)->sum('kg_yang_dipress');

        $dataWip = [];
        $prevWipKeluar = 0;

        foreach ($masterUraianWip as $index => $uraian) {
            
            // 1. Ambil Data Existing
            $existingRow = BahanProses::whereDate('tanggal', $tglStr)->where('uraian', $uraian)->first();
            $rektifUser = $existingRow ? $existingRow->rekfif : 0;
            $ketUser = $existingRow ? $existingRow->keterangan : '-';

            // 2. Ambil Saldo Awal
            $lastData = BahanProses::where('uraian', $uraian)->whereDate('tanggal', '<', $tglStr)->orderBy('tanggal', 'desc')->first();
            $saldoAwal = $lastData ? $lastData->saldo_akhir : 0;

            // 3. Tentukan Masuk (Estafet)
            $wipMasuk = ($index === 0) ? $inputDariMaturasi : $prevWipKeluar;
            
            $produksi = 0; 
            $wipKeluar = 0; 
            $saldoAkhir = 0;

            // 4. Logika Hitung (Sama seperti BahanProsesController)
            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                $produksi = $realProduction;
                $wipKeluar = $produksi; // Keluar dianggap produksi
                $saldoAkhir = $saldoAwal + $wipMasuk - $produksi + $rektifUser;
            } else {
                $targetSaldoAkhir = $saldoAwal + $rektifUser;
                if ($existingRow) {
                    $saldoAkhir = $existingRow->saldo_akhir;
                    $wipKeluar = $existingRow->wip_keluar;
                } else {
                    $saldoAkhir = max(0, $targetSaldoAkhir);
                    $wipKeluar = max(0, ($saldoAwal + $wipMasuk) - $saldoAkhir);
                }
            }

            // 5. Masukkan ke Array Hasil
            $dataWip[] = (object)[
                'uraian' => $uraian, 
                'stok_awal' => $saldoAwal, 
                'masuk' => $wipMasuk, 
                'keluar' => $wipKeluar,
                'produksi_sir20' => $produksi, 
                'rektif' => $rektifUser, 
                'stok_akhir' => $saldoAkhir, 
                'keterangan' => $ketUser
            ];

            // ======================================================
            // 🔥🔥🔥 DISINI POSISI LOGIKA CUT-OFF NYA BANG 🔥🔥🔥
            // ======================================================
            // Logika ini menentukan nilai $prevWipKeluar untuk proses selanjutnya
            
            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                // Jika ini Dryer, jangan oper ke 'Reproses'. Putus aliran.
                $prevWipKeluar = 0; 
            } else {
                // Selain Dryer, hasil keluar dioper ke proses berikutnya
                $prevWipKeluar = $wipKeluar;
            }

        } // <--- Akhir Loop Foreach

        return $dataWip;
    }   

    // =========================================================================
    // D & F. LOGIKA GUDANG & MUTU (TABEL IV & VI)
    // =========================================================================
    private function getDataGudangMutu($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $startOfMonth = $tanggal->copy()->startOfMonth()->format('Y-m-d');

        // 1. Ambil Master Data
        $lokasiList = \App\Models\Lokasi::all();
        $mutuList   = \App\Models\Mutu::all();

        // 2. Ambil Pallet yang EXIST pada tanggal laporan
        // Syarat: Tgl Produksi <= Tgl Laporan DAN (Belum dijual ATAU Dijual setelah Tgl Laporan)
        // Ini dipakai untuk menghitung Saldo Akhir (Stok Fisik)
        $activePallets = \App\Models\Pallet::whereDate('tanggal_produksi', '<=', $tglStr)
            ->where(function($q) use ($tglStr) {
                $q->whereNull('tanggal_penjualan')
                  ->orWhereDate('tanggal_penjualan', '>', $tglStr);
            })->get();

        // 🔥 3. Ambil Pallet yang TERJUAL PADA TANGGAL LAPORAN (Untuk Kolom Pengiriman) 🔥
        $soldPalletsToday = \App\Models\Pallet::whereDate('tanggal_penjualan', $tglStr)->get();

        // ---------------------------------------------------------------------
        // BAGIAN 1: GUDANG (TABEL IV)
        // ---------------------------------------------------------------------
        $dataGudang = [];

        foreach ($lokasiList as $lokasi) {
            $saldo_akhir = 0;
            $pengiriman = 0;

            // A. Hitung Saldo Akhir (Posisi pada Tanggal Laporan)
            foreach($activePallets as $p) {
                // Cari lokasi terakhir pallet ini PADA SAAT TANGGAL LAPORAN
                $lastLoc = \App\Models\LokasiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr) 
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $saldo_akhir += $p->berat;
                }
            }

            // 🔥 B. Hitung Pengiriman (Barang Keluar Hari Ini) 🔥
            foreach($soldPalletsToday as $sold) {
                // Cek lokasi terakhir pallet sebelum dijual
                $lastLoc = \App\Models\LokasiPallet::where('id_pallet', $sold->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();

                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $pengiriman += $sold->berat;
                }
            }

            // C. Masuk Hari Ini (Sesuai Tanggal Laporan)
            $masuk = \App\Models\LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)
                        ->whereDate('tanggal', $tglStr)
                        ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')
                        ->sum('pallet.berat');

            // D. Produksi s/d HI (Akumulasi Bulan Laporan)
            $sd_hi = \App\Models\LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)
                        ->whereBetween('tanggal', [$startOfMonth, $tglStr])
                        ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')
                        ->sum('pallet.berat');

            // E. Hitung Mundur
            $prod_lalu  = $sd_hi - $masuk;
            
            // Saldo Awal = Akhir - Masuk + Keluar
            $stok_awal = $saldo_akhir - $masuk + $pengiriman;

            $dataGudang[] = (object)[
                'uraian'        => $lokasi->nama,
                'stok_awal'     => $stok_awal,
                'prod_hi'       => $masuk,
                'prod_sdhi'     => $sd_hi,
                'prod_bln_lalu' => $prod_lalu,
                'pengiriman'    => $pengiriman, // 🔥 Sekarang variabel ini sudah terisi
                'stok_akhir'    => $saldo_akhir
            ];
        }

        // ---------------------------------------------------------------------
        // BAGIAN 2: MUTU (TABEL VI) - (Tetap Sama)
        // ---------------------------------------------------------------------
        $dataMutu = [];

        foreach ($mutuList as $m) {
            $kg = 0;
            $palletCount = 0;

            foreach($activePallets as $p) {
                // Cari kondisi mutu terakhir PADA SAAT TANGGAL LAPORAN
                $lastMutu = \App\Models\KondisiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_kondisi_pallet', 'desc')
                            ->first();
                
                if ($lastMutu && $lastMutu->id_mutu == $m->id_mutu) {
                    $kg += $p->berat;
                    $palletCount++;
                }
            }

            // Format array: [Uraian, Kg, Jumlah Pallet]
            $dataMutu[] = [$m->uraian, $kg, $palletCount];
        }

        return ['gudang' => $dataGudang, 'mutu' => $dataMutu];
    }

    // =========================================================================
    // E. LOGIKA PENJUALAN (TABEL V) - 🔥 SINKRON DENGAN CONTROLLER PENJUALAN
    // =========================================================================
    private function getDataPenjualan($tanggal)
    {
        // 1. Inisialisasi Tanggal
        $selectedDate = $tanggal->copy()->startOfDay();

        // 2. Ambil Data Snapshot (Hari Ini, Kemarin, Akhir Bulan Lalu)
        // Kita butuh data historis ini untuk mengisi saldo awal jika hari ini belum ada transaksi
        
        // Data Hari Ini
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)
            ->where('is_summary', 1)
            ->get()
            ->keyBy('uraian');

        // Data Kemarin (Untuk menghitung 'Bulan Ini s/d Kemarin')
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $selectedDate->copy()->subDay())
            ->where('is_summary', 1)
            ->get()
            ->keyBy('uraian');

        // Data Akhir Bulan Lalu (Untuk menghitung 's/d Bulan Lalu')
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $selectedDate->copy()->subMonth()->endOfMonth())
            ->where('is_summary', 1)
            ->get()
            ->keyBy('uraian');

        // 3. Define Master Uraian (Agar baris tetap muncul walau data kosong)
        $masterUraian = [
            '5.1' => 'SIR20 PTNBL', 
            '5.2' => 'SIR20 PTPN4'
        ];
        
        $tabelSummary = new Collection();

        foreach ($masterUraian as $no => $uraian) {
            $itemToday     = $dataDB->get($uraian);
            $itemKemarin   = $dataKemarin->get($uraian);
            $itemBulanLalu = $dataBulanLalu->get($uraian);

            // A. Logika: s/d Bulan Lalu
            // Jika hari ini ada record, ambil dari record tsb.
            // Jika tidak, ambil dari Total s/d Hari Ini pada penutupan bulan lalu.
            $sd_bln_lalu = $itemToday 
                ? $itemToday->sd_bulan_lalu 
                : ($itemBulanLalu->total_sd_hari_ini ?? 0);

            // B. Logika: Bulan Ini s/d Kemarin
            if ($selectedDate->day == 1) {
                // Jika tanggal 1, pasti 0 karena bulan baru
                $bln_ini_lalu = 0; 
            } else {
                // Jika hari lain, ambil dari record hari ini.
                // Jika record hari ini belum ada, ambil (Bln Ini Lalu + Hari Ini) dari data Kemarin.
                $bln_ini_lalu = $itemToday 
                    ? $itemToday->bln_ini_lalu 
                    : (($itemKemarin->bln_ini_lalu ?? 0) + ($itemKemarin->hari_ini ?? 0));
            }

            // C. Penjualan Hari Ini
            $hari_ini = $itemToday ? $itemToday->hari_ini : 0;

            // D. Hitung Total (Kalkulasi ulang untuk memastikan akurasi)
            $total_bln_ini     = $bln_ini_lalu + $hari_ini;
            $total_sd_hari_ini = $sd_bln_lalu + $total_bln_ini;

            // E. Masukkan ke Collection
            $tabelSummary->push((object)[
                'no'                => $no,
                'uraian'            => $uraian,
                'sd_bulan_lalu'     => $sd_bln_lalu,
                'bln_ini_lalu'      => $bln_ini_lalu,
                'hari_ini'          => $hari_ini,
                'total_bln_ini'     => $total_bln_ini,
                'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan'        => $itemToday->keterangan ?? '-',
            ]);
        }

        return $tabelSummary;
    }

    // Helper Object Creator untuk Maturasi
    private function createMaturasiObj($bak, $awal, $olah, $mutasi, $masuk, $akhir, $tgl, $umur, $ket) {
        return (object)[
            'no_bak' => $bak->no_bak ?? $bak->uraian, 'tgl_isi' => $tgl, 'jenis' => $bak->jenis ?? $bak->asal_bokar, 
            'umur' => $umur, 'kering' => $awal, 'diolah' => $olah, 'mutasi' => $mutasi, 'masuk_hi' => $masuk, 'stok_akhir' => $akhir, 'keterangan' => $ket
        ];
    }
}