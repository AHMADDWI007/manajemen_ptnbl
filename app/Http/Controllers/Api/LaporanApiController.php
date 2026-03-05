<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BahanProses;
use App\Models\HasilUjiLabBokarDiolah;
use App\Models\HasilUjiLabMaturasi;
use App\Models\Maturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use App\Models\PenjualanSir20;
use App\Models\ProduksiSir20; 
use App\Models\RektifikasiStok;
use App\Models\TransaksiApiBokar;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
                $row->k3_olah = 0; $row->po = '-'; $row->pri = '-';
            } else {
                $sumsAwal = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', '<', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                $stok_awal = $sumsAwal ? ($sumsAwal->m - $sumsAwal->d - $sumsAwal->u) : 0;

                $s = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tgl_laporan', $tglStr)
                    ->selectRaw('COALESCE(SUM(masuk_hi),0) as m, COALESCE(SUM(diolah),0) as d, COALESCE(SUM(mutasi),0) as u')->first();
                
                $trans_masuk = (float)($s->m ?? 0);
                $trans_diolah = (float)($s->d ?? 0);
                $trans_mutasi = (float)($s->u ?? 0);

                $masuk_from_uji = (float) HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', $tglStr)->sum('netto_kering');
                $masuk_hi_today = max($trans_masuk, $masuk_from_uji);
                
                $stok_akhir = round($stok_awal + $masuk_hi_today - $trans_diolah - $trans_mutasi, 2);

                $tgl_basis = null;
                $logsBeforeToday = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tgl_laporan', '<', $tglStr)
                    ->orderBy('tgl_laporan', 'asc')->get();

                $running_stock = 0;
                foreach($logsBeforeToday as $log) {
                    $logDate = Carbon::parse($log->tgl_laporan);
                    $in_fresh = $log->masuk_hi;
                    $mutasi_in = $log->mutasi < -0.01 ? abs($log->mutasi) : 0;
                    $out = $log->diolah + ($log->mutasi > 0.01 ? $log->mutasi : 0);

                    if ($in_fresh > 0.01) {
                        $lab = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)
                            ->whereDate('tanggal', '<=', $logDate->toDateString())->orderBy('tanggal', 'desc')->first();
                        $tgl_basis = $lab ? Carbon::parse($lab->tanggal) : $logDate;
                    } 
                    elseif (preg_match('/Asal: (\d{4}-\d{2}-\d{2})/', $log->keterangan, $matches)) {
                        $tgl_basis = Carbon::parse($matches[1]);
                    } 
                    elseif ($mutasi_in > 0.01 && !$tgl_basis) {
                        $tgl_basis = $logDate;
                    }

                    $running_stock = $running_stock + $in_fresh + $mutasi_in - $out;
                    if ($running_stock <= 0.01) $tgl_basis = null;
                }

                $logToday = PengolahanMaturasi::where('id_maturasi', $bak->id_maturasi)
                    ->whereDate('tgl_laporan', $tglStr)->first();
                    
                $adaPenerimaanMutasi = false;
                $tgl_mutasi_hari_ini = null;

                if ($logToday && preg_match('/Asal: (\d{4}-\d{2}-\d{2})/', $logToday->keterangan, $matches)) {
                    $adaPenerimaanMutasi = true;
                    $tgl_mutasi_hari_ini = Carbon::parse($matches[1]);
                    
                    if ($stok_awal <= 0.01) {
                        $tgl_basis = $tgl_mutasi_hari_ini;
                    }
                }

                if (!$tgl_basis && $stok_awal > 0.01) {
                    $tgl_basis = !empty($bak->tgl_masuk) ? Carbon::parse($bak->tgl_masuk) : Carbon::parse($bak->created_at);
                }

                $tgl_masuk_visual = ($stok_awal > 0.01 && $tgl_basis) ? $tgl_basis->toDateString() : null;
                $umur_visual = ($stok_awal > 0.01 && $tgl_basis) ? $tgl_basis->diffInDays($tanggal) : 0;

                $keterangan_visual = 'KOSONG';
                if ($stok_akhir > 0.01) {
                    if ($masuk_hi_today > 0.01) {
                        $labToday = HasilUjiLabBokarDiolah::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', $tglStr)->first();
                        $keterangan_date = $labToday ? Carbon::parse($labToday->tanggal) : $tanggal;
                        $keterangan_visual = strtoupper($keterangan_date->format('d M Y'));
                    } elseif ($adaPenerimaanMutasi) { 
                        $keterangan_visual = strtoupper($tgl_mutasi_hari_ini->format('d M Y'));
                    } else {
                        $keterangan_visual = $tgl_basis ? strtoupper($tgl_basis->format('d M Y')) : '-';
                    }
                }

                $row = $this->createMaturasiObj($bak, $stok_awal, $trans_diolah, $trans_mutasi, $masuk_hi_today, $stok_akhir, $tgl_masuk_visual, $umur_visual, $keterangan_visual);
                
                $row->asal_bokar = $this->getDetailedAsalBokarStringLaporan($bak, $stok_akhir, $stok_awal, $tanggal);
                if ($stok_akhir <= 0.01) {
                    $row->asal_bokar = '-';
                    $row->keterangan = 'KOSONG';
                }

                // Masukkan data K3 agar API komplit
                $ujiLab = HasilUjiLabMaturasi::where('id_maturasi', $bak->id_maturasi)->whereDate('tanggal', '<=', $tglStr)->orderBy('tanggal', 'desc')->first();
                $row->k3_olah = $ujiLab->k3 ?? 0;
                $row->po      = $ujiLab->po ?? '-';
                $row->pri     = $ujiLab->pri ?? '-';
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
            $mutasiTerimaLog = null; 

            foreach ($logs as $log) {
                $realBatchStartDate = Carbon::parse($log->tgl_laporan);
                
                $masuk  = $log->masuk_hi;
                $keluar = $log->diolah + ($log->mutasi > 0 ? $log->mutasi : 0); 
                $mutasiMasuk = ($log->mutasi < 0) ? abs($log->mutasi) : 0;
                
                $akumulasiKeluar += $keluar; 
                $prevStock = $currentTracingStock - ($masuk + $mutasiMasuk) + $keluar;

                // 🔥 SINKRONISASI: Pakai Regex Anti-Jebol & Deteksi dari teks (bukan cuma angka)
                $adaTeksJenis = preg_match('/Jenis:\s*(.*?)(?=\)\s*(?:\||$))/', $log->keterangan);

                if (($mutasiMasuk > 0.01 || $adaTeksJenis) && !$mutasiTerimaLog) {
                    $mutasiTerimaLog = $log;
                }

                if (($masuk + $mutasiMasuk > 0.01 || $adaTeksJenis) && $akumulasiKeluar > 0.01) {
                    break; 
                }

                if ($prevStock <= 0.01) break;
                $currentTracingStock = $prevStock;
            }

            // PRIORITAS 1: Ambil murni dari PENGOLAHAN BASAH / LAB BOKAR
            $jenisList = HasilUjiLabBokarDiolah::where('id_maturasi', $maturasi->id_maturasi)
                ->whereDate('tanggal', '>=', $realBatchStartDate)
                ->whereDate('tanggal', '<=', $filterDate)
                ->pluck('jenis')
                ->map(function($v) { return strtoupper(trim($v)); })
                ->filter(function($v) { return $v !== 'PENDING' && $v !== ''; })
                ->unique()->sort()->values()->toArray();

            if (!empty($jenisList)) {
                return count($jenisList) > 1 ? 'CMP (' . implode(', ', $jenisList) . ')' : $jenisList[0];
            }

            // PRIORITAS 2: Jika kosong, baca dari LOG MUTASI pakai Regex Super
            if ($mutasiTerimaLog && preg_match('/Jenis:\s*(.*?)(?=\)\s*(?:\||$))/', $mutasiTerimaLog->keterangan, $matches)) {
                return trim($matches[1]);
            }
            
            // Fallback (Edge case): Cek log hari ini jika mutasi terjadi persis di hari filter
            $logToday = $logs->firstWhere('tgl_laporan', $filterDate->toDateString());
            if ($logToday && preg_match('/Jenis:\s*(.*?)(?=\)\s*(?:\||$))/', $logToday->keterangan, $matches)) {
                return trim($matches[1]);
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
        $dateAkhirBulanLalu = $tanggal->copy()->startOfMonth()->subDay()->format('Y-m-d');

        $lokasiList = \App\Models\Lokasi::all();
        $mutuList   = \App\Models\Mutu::all();

        $activePallets = \App\Models\Pallet::where(function($q) use ($tglStr) {
                $q->whereNull('tanggal_penjualan')
                  ->orWhereDate('tanggal_penjualan', '>=', $tglStr);
            })->get();

        $soldPalletsToday = \App\Models\Pallet::whereDate('tanggal_penjualan', $tglStr)->get();
        $soldPalletsSdKemarin = \App\Models\Pallet::whereBetween(DB::raw('DATE(tanggal_penjualan)'), [
            $startOfMonth, 
            $tanggal->copy()->subDay()->format('Y-m-d')
        ])->get();

        $subQueryKemarin = DB::table('lokasi_pallet')
            ->select('id_pallet', DB::raw('MAX(id_lokasi_pallet) as last_id'))
            ->whereDate('tanggal', '<', $tglStr)
            ->groupBy('id_pallet');

        $saldoAwalList = DB::table('lokasi_pallet as lp')
            ->joinSub($subQueryKemarin, 'latest', function ($join) {
                $join->on('lp.id_lokasi_pallet', '=', 'latest.last_id');
            })
            ->join('pallet as p', 'lp.id_pallet', '=', 'p.id_pallet')
            ->where(function($q) use ($tglStr) {
                $q->whereNull('p.tanggal_penjualan')
                  ->orWhereDate('p.tanggal_penjualan', '>=', $tglStr);
            })
            ->select('lp.id_lokasi', DB::raw('SUM(p.berat) as total_berat'))
            ->groupBy('lp.id_lokasi')
            ->pluck('total_berat', 'id_lokasi');

        $subQueryAwalBulan = DB::table('lokasi_pallet')
            ->select('id_pallet', DB::raw('MAX(id_lokasi_pallet) as last_id'))
            ->whereDate('tanggal', '<=', $dateAkhirBulanLalu)
            ->groupBy('id_pallet');

        $saldoAwalBulanList = DB::table('lokasi_pallet as lp')
            ->joinSub($subQueryAwalBulan, 'latest', function ($join) {
                $join->on('lp.id_lokasi_pallet', '=', 'latest.last_id');
            })
            ->join('pallet as p', 'lp.id_pallet', '=', 'p.id_pallet')
            ->where(function($q) use ($startOfMonth) {
                $q->whereNull('p.tanggal_penjualan')
                  ->orWhereDate('p.tanggal_penjualan', '>=', $startOfMonth);
            })
            ->select('lp.id_lokasi', DB::raw('SUM(p.berat) as total_berat'))
            ->groupBy('lp.id_lokasi')
            ->pluck('total_berat', 'id_lokasi');

        $dataGudang = []; // Format Array agar aman ditarik JSON
        $lainnya_saldo_awal = 0;
        $lainnya_masuk = 0;
        $lainnya_sd_hi = 0;
        $lainnya_yg_lalu = 0;
        $lainnya_keluar = 0;
        $lainnya_saldo_akhir = 0;

        foreach ($lokasiList as $lokasi) {
            $saldo_awal_kg = $saldoAwalList->get($lokasi->id_lokasi, 0);
            $saldo_awal_bulan_kg = $saldoAwalBulanList->get($lokasi->id_lokasi, 0);

            $saldo_akhir_kg = 0;
            foreach($activePallets as $p) {
                $lastLoc = \App\Models\LokasiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $saldo_akhir_kg += $p->berat;
                }
            }

            $keluar_kg = 0; 
            foreach($soldPalletsToday as $sold) {
                $lastLoc = \App\Models\LokasiPallet::where('id_pallet', $sold->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $keluar_kg += $sold->berat;
                }
            }

            $pengiriman_sd_kemarin_kg = 0;
            foreach($soldPalletsSdKemarin as $sold) {
                $lastLoc = \App\Models\LokasiPallet::where('id_pallet', $sold->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)
                            ->orderBy('id_lokasi_pallet', 'desc')
                            ->first();
                if ($lastLoc && $lastLoc->id_lokasi == $lokasi->id_lokasi) {
                    $pengiriman_sd_kemarin_kg += $sold->berat;
                }
            }

            $net_masuk_kg = $saldo_akhir_kg - $saldo_awal_kg + $keluar_kg;
            $masuk_kg = $net_masuk_kg > 0 ? $net_masuk_kg : 0;

            $net_yg_lalu_kg = $saldo_awal_kg - $saldo_awal_bulan_kg + $pengiriman_sd_kemarin_kg;
            $yg_lalu_kg = $net_yg_lalu_kg > 0 ? $net_yg_lalu_kg : 0;

            $sd_hi_kg = $yg_lalu_kg + $masuk_kg;

            if (in_array($lokasi->id_lokasi, [1, 2])) {
                $dataGudang[] = (object)[
                    'uraian'        => $lokasi->nama,
                    'stok_awal'     => $saldo_awal_kg,
                    'prod_hi'       => $masuk_kg,
                    'prod_sdhi'     => $sd_hi_kg,
                    'prod_bln_lalu' => $yg_lalu_kg,
                    'pengiriman'    => $keluar_kg,
                    'stok_akhir'    => $saldo_akhir_kg
                ];
            } else {
                $lainnya_saldo_awal += $saldo_awal_kg;
                $lainnya_masuk += $masuk_kg;
                $lainnya_yg_lalu += $yg_lalu_kg;
                $lainnya_sd_hi += $sd_hi_kg;
                $lainnya_keluar += $keluar_kg;
                $lainnya_saldo_akhir += $saldo_akhir_kg;
            }
        }

        if (count($lokasiList) > 2) {
            $dataGudang[] = (object)[
                'uraian'        => 'Lainnya',
                'stok_awal'     => $lainnya_saldo_awal,
                'prod_hi'       => $lainnya_masuk,
                'prod_sdhi'     => $lainnya_sd_hi,
                'prod_bln_lalu' => $lainnya_yg_lalu,
                'pengiriman'    => $lainnya_keluar,
                'stok_akhir'    => $lainnya_saldo_akhir
            ];
        }

        $dataMutu = [];
        foreach ($mutuList as $m) {
            $kg = 0; $palletCount = 0;
            foreach($activePallets as $p) {
                $lastMutu = \App\Models\KondisiPallet::where('id_pallet', $p->id_pallet)
                            ->whereDate('tanggal', '<=', $tglStr)->orderBy('id_kondisi_pallet', 'desc')->first();
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