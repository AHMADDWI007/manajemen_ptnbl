<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// 🔥 PASTIKAN SEMUA MODEL INI ADA
use App\Models\TransaksiApiBokar;
use App\Models\PengolahanBasah;
use App\Models\RektifikasiStok;
use App\Models\Maturasi;
use App\Models\PengolahanMaturasi;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\BahanProses;
use App\Models\ProduksiSir20; 
use App\Models\PenjualanSir20;
use App\Models\Lokasi;
use App\Models\Mutu;
use App\Models\Pallet;
use App\Models\LokasiPallet;
use App\Models\KondisiPallet;

class LaporanApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $tglInput = $request->query('date');
            $tanggal = $tglInput ? Carbon::parse($tglInput) : Carbon::today();

            // Panggil fungsi perhitungan (Logic sama persis dengan Web)
            $rekapBokar   = $this->getRekapBokar($tanggal);
            $dataMaturasi = $this->getDataMaturasi($tanggal);
            $dataWip      = $this->getDataWip($tanggal);
            $gudangMutu   = $this->getDataGudangMutu($tanggal);
            $dataPenjualan = $this->getDataPenjualan($tanggal);

            return response()->json([
                'success' => true,
                'data' => [
                    'tanggal'       => $tanggal->translatedFormat('d F Y'),
                    'rekapBokar'    => $rekapBokar,
                    'dataMaturasi'  => $dataMaturasi,
                    'dataWip'       => $dataWip,
                    'dataGudang'    => $gudangMutu['gudang'],
                    'dataMutu'      => $gudangMutu['mutu'],
                    'dataPenjualan' => $dataPenjualan
                ]
            ]);
        } catch (\Exception $e) {
            // Tangkap Error agar tidak muncul pesan html 500
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage() . ' on Line ' . $e->getLine()
            ], 500);
        }
    }

    // public function cetakPdf(Request $request)
    // {
    //     $tglInput = $request->query('date');
    //     $tanggal = $tglInput ? Carbon::parse($tglInput) : Carbon::today();

    //     $data = [
    //         'tanggal'       => $tanggal,
    //         'rekapBokar'    => $this->getRekapBokar($tanggal),
    //         'dataMaturasi'  => $this->getDataMaturasi($tanggal),
    //         'dataWip'       => $this->getDataWip($tanggal),
    //         $gm             = $this->getDataGudangMutu($tanggal),
    //         'dataGudang'    => $gm['gudang'],
    //         'dataMutu'      => $gm['mutu'],
    //         'dataPenjualan' => $this->getDataPenjualan($tanggal)
    //     ];

    //     $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('Cetak.cetak-laporan', $data);
    //     $pdf->setPaper('a4', 'landscape');
    //     return $pdf->stream('Laporan-Harian.pdf');
    // }

    // =========================================================================
    // PRIVATE HELPERS (LOGIC)
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
            $masuk_kemarin = TransaksiApiBokar::where('kode_api', $kodeApi)->where('tanggal', '<=', $yesterday)->sum('masuk_hi');
            $olah_kemarin  = PengolahanBasah::where('jenis', $jenis)->where('tanggal', '<=', $yesterday)->sum('netto_kering');
            $rektif_kemarin = RektifikasiStok::where('jenis', $jenis)->where('tanggal', '<=', $yesterday)->sum('berat');
            $stok_awal = max(0, $masuk_kemarin - $olah_kemarin + $rektif_kemarin);

            $masuk_hi = TransaksiApiBokar::where('kode_api', $kodeApi)->where('tanggal', $tglStr)->value('masuk_hi') ?? 0;
            $data_olah = PengolahanBasah::where('jenis', $jenis)->whereDate('tanggal', $tglStr)
                ->selectRaw('SUM(netto_basah) as basah, SUM(netto_kering) as kering')->first();
            
            $masuk_sd_kemarin = 0;
            if ($yesterday->gte($startOfMonth)) {
                $masuk_sd_kemarin = TransaksiApiBokar::where('kode_api', $kodeApi)
                    ->whereBetween('tanggal', [$startOfMonth, $yesterday])->sum('masuk_hi');
            }
            $olah_sdhi = PengolahanBasah::where('jenis', $jenis)->whereBetween('tanggal', [$startOfMonth, $tglStr])->sum('netto_kering');
            $rektif_today = $rektifRecords->has($jenis) ? (float)$rektifRecords[$jenis]->berat : 0;

            $rekapBokar[$jenis] = [
                'uraian'      => ($jenis=='DS'?'Petani (DS)': ($jenis=='PT'?'PTPN (PT)':'Inhutani')),
                'stok_awal'   => $stok_awal,
                'masuk_hi'    => $masuk_hi,
                'masuk_sdhi'  => $masuk_sd_kemarin + $masuk_hi,
                'kering_hi'   => $data_olah->kering ?? 0,
                'kering_sdhi' => $olah_sdhi,
                'rektif'      => $rektif_today
            ];
        }
        return $rekapBokar;
    }

    private function getDataMaturasi($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $data_maturasi_db = Maturasi::orderBy('id_maturasi')->get();
        $dataMaturasi = new Collection();

        foreach ($data_maturasi_db as $bak) {
            $hasLog = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', '<=', $tglStr)->exists();
            
            if (!$hasLog) {
                $row = $this->createMaturasiObj($bak, 0, 0, 0, 0, 0, null, 0, 'KOSONG');
                $row->asal_bokar = '-';
            } else {
                // 1. Hitung Stok Awal Snapshot
                $sums = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', '<', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                $stok_awal = $sums ? ($sums->m - $sums->d - $sums->u) : 0;

                $s = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                
                $trans_masuk = (float)($s->m ?? 0);
                $trans_diolah = (float)($s->d ?? 0);
                $trans_mutasi = (float)($s->u ?? 0);

                $masuk_hi_today = $trans_masuk;
                $stok_akhir = $stok_awal + $masuk_hi_today - $trans_diolah - $trans_mutasi;

                // 2. 🔥 LOGIKA UMUR (SINKRON 100% DENGAN WEB)
                $tgl_basis = null;
                $logsBeforeToday = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tgl_laporan', '<', $tglStr)
                    ->orderBy('tgl_laporan', 'asc')->get();

                $running_stock = 0;
                foreach($logsBeforeToday as $log) {
                    $logDate = Carbon::parse($log->tgl_laporan);
                    if ($log->masuk_hi > 0.01) {
                        $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)
                            ->whereDate('tanggal', '<=', $logDate->toDateString())->orderBy('tanggal', 'desc')->first();
                        $tgl_basis = $lab ? Carbon::parse($lab->tanggal) : $logDate;
                    } elseif ($running_stock <= 0.01 && $log->mutasi < -0.01) {
                        $tgl_basis = $logDate;
                    }
                    $running_stock += ($log->masuk_hi + ($log->mutasi < 0 ? abs($log->mutasi) : 0) - ($log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0)));
                    if ($running_stock <= 0.01) $tgl_basis = null;
                }

                if (!$tgl_basis && $stok_awal > 0.01) {
                    $tgl_basis = !empty($bak->tgl_masuk) ? Carbon::parse($bak->tgl_masuk) : Carbon::parse($bak->created_at);
                }

                $umur_visual = ($stok_awal > 0.01 && $tgl_basis) ? $tgl_basis->diffInDays($tanggal) : 0;
                $keterangan_visual = ($stok_akhir <= 0.01) ? 'KOSONG' : ($tgl_basis ? strtoupper($tgl_basis->format('d M Y')) : '-');

                $row = $this->createMaturasiObj($bak, $stok_awal, $trans_diolah, $trans_mutasi, $masuk_hi_today, $stok_akhir, ($tgl_basis ? $tgl_basis->toDateString() : null), $umur_visual, $keterangan_visual);
                
                // 3. SINKRONISASI LABEL ASAL BOKAR (CMP TRACING)
                $row->asal_bokar = $this->getDetailedAsalBokarStringLaporan($bak, $stok_akhir, $stok_awal, $tanggal);
            }
            
            $dataMaturasi->push($row);
        }
        return $dataMaturasi;
    }

    private function getDetailedAsalBokarStringLaporan($maturasi, float $stokAkhir, float $stokAwal, Carbon $filterDate)
    {
        if ($stokAkhir <= 0.01 && $stokAwal <= 0.01) return '-';

        try {
            $realBatchStartDate = $filterDate->copy();
            
            $logs = PengolahanMaturasi::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tgl_laporan', '<=', $filterDate)
                ->orderBy('tgl_laporan', 'desc')
                ->get();

            $currentTracingStock = ($stokAkhir > 0.01) ? $stokAkhir : ($stokAwal + 0.1);
            $akumulasiKeluar = 0; 

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0); 
                $mutasiMasuk = ($log->mutasi < 0) ? abs($log->mutasi) : 0;
                
                $akumulasiKeluar += $keluar; 
                $prevStock = $currentTracingStock - ($masuk + $mutasiMasuk) + $keluar;

                // LOGIKA RESET ULTIMATE (SINKRON DENGAN MATURASI CONTROLLER)
                if (($masuk + $mutasiMasuk) > 0.01 && $akumulasiKeluar > 0.01) {
                    break; 
                }

                if ($prevStock <= 0.01) break;
                $currentTracingStock = $prevStock;
            }

            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(function($v) { return strtoupper(trim($v)); })
                ->unique()->filter()->sort()->values()->toArray();

            if (!empty($jenisList)) {
                return count($jenisList) > 1 ? 'CMP (' . implode(', ', $jenisList) . ')' : $jenisList[0];
            }

            return $maturasi->asal_bokar ?? '-';

        } catch (\Exception $e) {
            return $maturasi->asal_bokar ?? '-';
        }
    }

    private function getDataWip($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $masterUraianWip = [
            'Lantai Umpan Kering', 'Di Blending Tank 4', 'Di Lump Breaker-2 (Di Blending Tank-4)',
            'Di Pre Breaker-2 (Di Blending Tank-5)', 'Di Hammer Mill-2 (Di Blending Tank-6)',
            'Di Blending Tank-7', 'Di Trolley', 'Di Dalam Dryer/Press Bale', 'Di Reproses Ex WS.'
        ];

        $maturasiToday = PengolahanMaturasi::whereDate('tgl_laporan', $tglStr)
            ->selectRaw('SUM(diolah) as total_diolah, SUM(mutasi) as total_mutasi')->first();
        $inputDariMaturasi = $maturasiToday ? ($maturasiToday->total_diolah - $maturasiToday->total_mutasi) : 0;
        $realProduction = ProduksiSir20::whereDate('tanggal_produksi', $tglStr)->sum('kg_yang_dipress');

        $dataWip = [];
        $prevWipKeluar = 0;

        foreach ($masterUraianWip as $index => $uraian) {
            $existingRow = BahanProses::whereDate('tanggal', $tglStr)->where('uraian', $uraian)->first();
            $rektifUser = $existingRow ? $existingRow->rekfif : 0;
            $ketUser = $existingRow ? $existingRow->keterangan : '-';

            $lastData = BahanProses::where('uraian', $uraian)
                ->whereDate('tanggal', '<', $tglStr)
                ->orderBy('tanggal', 'desc')->first();

            // 🔥 Sinkron dengan Web: Ambil saldo akhir kemarin, atau saldo awal manual jika data baru
            if ($lastData) {
                $saldoAwal = $lastData->saldo_akhir;
            } else {
                $saldoAwal = $existingRow ? $existingRow->saldo_awal : 0;
            }

            $saldoAwal = $lastData ? $lastData->saldo_akhir : 0;

            $wipMasuk = ($index === 0) ? $inputDariMaturasi : $prevWipKeluar;
            
            $produksi = 0; $wipKeluar = 0; $saldoAkhir = 0;

            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                $produksi = $realProduction;
                $wipKeluar = $produksi; 
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

            $dataWip[] = (object)[
                'uraian' => $uraian, 'stok_awal' => $saldoAwal, 'masuk' => $wipMasuk, 'keluar' => $wipKeluar,
                'produksi_sir20' => $produksi, 'rektif' => $rektifUser, 'stok_akhir' => $saldoAkhir, 'keterangan' => $ketUser
            ];

            if ($uraian == 'Di Dalam Dryer/Press Bale') {
                $prevWipKeluar = 0; 
            } else {
                $prevWipKeluar = $wipKeluar;
            }
        }
        return $dataWip;
    }

    private function getDataGudangMutu($tanggal)
    {
        $tglStr = $tanggal->format('Y-m-d');
        $startOfMonth = $tanggal->copy()->startOfMonth()->format('Y-m-d');

        $lokasiList = Lokasi::all();
        $mutuList   = Mutu::all();

        $activePallets = Pallet::whereDate('tanggal_produksi', '<=', $tglStr)
            ->where(function($q) use ($tglStr) {
                $q->whereNull('tanggal_penjualan')->orWhereDate('tanggal_penjualan', '>', $tglStr);
            })->get();

        $soldPalletsToday = Pallet::whereDate('tanggal_penjualan', $tglStr)->get();

        $dataGudang = [];
        foreach ($lokasiList as $lokasi) {
            $saldo_akhir = 0; $pengiriman = 0;

            foreach($activePallets as $p) {
                $lastLoc = LokasiPallet::where('id_pallet', $p->id_pallet)->whereDate('tanggal', '<=', $tglStr) 
                            ->orderBy('id_lokasi_pallet', 'desc')->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) $saldo_akhir += $p->berat;
            }

            foreach($soldPalletsToday as $sold) {
                $lastLoc = LokasiPallet::where('id_pallet', $sold->id_pallet)->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_lokasi_pallet', 'desc')->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) $pengiriman += $sold->berat;
            }

            $masuk = LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)->whereDate('tanggal', $tglStr)
                        ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')->sum('pallet.berat');

            $sd_hi = LokasiPallet::where('id_lokasi', $lokasi->id_lokasi)->whereBetween('tanggal', [$startOfMonth, $tglStr])
                        ->join('pallet', 'lokasi_pallet.id_pallet', '=', 'pallet.id_pallet')->sum('pallet.berat');

            $stok_awal = $saldo_akhir - $masuk + $pengiriman;

            $dataGudang[] = (object)[
                'uraian' => $lokasi->nama, 'stok_awal' => $stok_awal, 'prod_hi' => $masuk,
                'prod_sdhi' => $sd_hi, 'prod_bln_lalu' => $sd_hi - $masuk, 'pengiriman' => $pengiriman, 'stok_akhir' => $saldo_akhir
            ];
        }

        $dataMutu = [];
        foreach ($mutuList as $m) {
            $kg = 0; $palletCount = 0;
            foreach($activePallets as $p) {
                $lastMutu = KondisiPallet::where('id_pallet', $p->id_pallet)->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_kondisi_pallet', 'desc')->first();
                if ($lastMutu && $lastMutu->id_mutu == $m->id_mutu) {
                    $kg += $p->berat; $palletCount++;
                }
            }
            $dataMutu[] = [$m->uraian, $kg, $palletCount];
        }

        return ['gudang' => $dataGudang, 'mutu' => $dataMutu];
    }

    private function getDataPenjualan($tanggal)
    {
        $selectedDate = $tanggal->copy()->startOfDay();
        $dataDB = PenjualanSir20::whereDate('tanggal', $selectedDate)->where('is_summary', 1)->get()->keyBy('uraian');
        $dataKemarin = PenjualanSir20::whereDate('tanggal', $selectedDate->copy()->subDay())->where('is_summary', 1)->get()->keyBy('uraian');
        $dataBulanLalu = PenjualanSir20::whereDate('tanggal', $selectedDate->copy()->subMonth()->endOfMonth())->where('is_summary', 1)->get()->keyBy('uraian');

        $masterUraian = ['5.1' => 'SIR20 PTNBL', '5.2' => 'SIR20 PTPN4'];
        $tabelSummary = new Collection();

        foreach ($masterUraian as $no => $uraian) {
            $itemToday = $dataDB->get($uraian);
            $itemKemarin = $dataKemarin->get($uraian);
            $itemBulanLalu = $dataBulanLalu->get($uraian);

            $sd_bln_lalu = $itemToday ? $itemToday->sd_bulan_lalu : ($itemBulanLalu->total_sd_hari_ini ?? 0);
            
            if ($selectedDate->day == 1) {
                $bln_ini_lalu = 0; 
            } else {
                $bln_ini_lalu = $itemToday ? $itemToday->bln_ini_lalu : (($itemKemarin->bln_ini_lalu ?? 0) + ($itemKemarin->hari_ini ?? 0));
            }

            $hari_ini = $itemToday ? $itemToday->hari_ini : 0;
            $total_bln_ini = $bln_ini_lalu + $hari_ini;
            $total_sd_hari_ini = $sd_bln_lalu + $total_bln_ini;

            $tabelSummary->push((object)[
                'uraian' => $uraian, 'sd_bulan_lalu' => $sd_bln_lalu, 'bln_ini_lalu' => $bln_ini_lalu,
                'hari_ini' => $hari_ini, 'total_bln_ini' => $total_bln_ini, 'total_sd_hari_ini' => $total_sd_hari_ini,
                'keterangan' => $itemToday->keterangan ?? '-'
            ]);
        }
        return $tabelSummary;
    }

    // 🔥 INI HELPER YANG SERING KETINGGALAN
    private function createMaturasiObj($bak, $awal, $olah, $mutasi, $masuk, $akhir, $tgl, $umur, $ket) {
        return (object)[
            'uraian' => $bak->uraian, 
            'no_bak' => $bak->no_bak ?? $bak->uraian, 
            'tgl_isi' => $tgl, 
            'jenis' => $bak->jenis ?? $bak->asal_bokar, 
            'asal_bokar' => $bak->asal_bokar, // Tambahan
            'umur' => $umur, 
            'kering' => $awal, 
            'diolah' => $olah, 
            'mutasi' => $mutasi, 
            'masuk_hi' => $masuk, 
            'stok_akhir' => $akhir, 
            'keterangan' => $ket
        ];
    }
}