<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian Produksi - {{ $tanggal->format('d-m-Y') }}</title>
    
    <style>
    /* ==========================================================================
       1. RESET & GAYA DASAR HALAMAN
       ========================================================================== */
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background-color: #ffffff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ==========================================================================
       2. KONTAINER UTAMA (KERTAS A4)
       ========================================================================== */
    .table-container {
        background-color: white;
        width: 210mm;
        margin: 0 auto;
        padding: 2mm 10mm 10mm 10mm;
        box-sizing: border-box;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        page-break-inside: avoid;
        overflow: hidden;
    }

    /* ==========================================================================
       3. GAYA TABEL UTAMA (STRUKTUR DATA)
       ========================================================================== */
    table.main-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 7.5px;
    }

    /* 🔥 PERBAIKAN: Definisi Lebar Kolom (Total 15 Kolom) 🔥 */
    .main-table col:nth-child(1) { width: 3%; }   
    .main-table col:nth-child(2) { width: 15%; }  
    .main-table col:nth-child(3) { width: 7%; }   
    .main-table col:nth-child(4) { width: 7%; }   
    .main-table col:nth-child(5) { width: 5%; }   
    .main-table col:nth-child(6) { width: 6%; }   
    .main-table col:nth-child(7) { width: 5%; }   
    .main-table col:nth-child(8) { width: 5%; }   
    .main-table col:nth-child(9) { width: 4%; }   
    .main-table col:nth-child(10) { width: 4%; }  
    .main-table col:nth-child(11) { width: 4%; }  
    .main-table col:nth-child(12) { width: 6.5%; }  
    .main-table col:nth-child(13) { width: 7%; }  
    .main-table col:nth-child(14) { width: 9%; }  
    .main-table col:nth-child(15) { width: 9%; } 

    .main-table th, .main-table td {
        border: 0.1px solid #000;
        padding: 1px 2px;
        vertical-align: middle;
        word-wrap: break-word;
        height: 11px;
        line-height: 1.1;
    }

    .main-table th {
        background-color: #ffffff !important;
        text-align: center;
        font-weight: bold;
        height: 7px;
    }

    /* ==========================================================================
       4. TABEL INFO (HEADER LAPORAN)
       ========================================================================== */
    .info-table { width: 100%; border: none; margin-bottom: 2px; }
    .info-table td { border: none !important; padding: 0 2px !important; text-align: left; font-size: 8px; font-weight: bold; }
    .info-table td:nth-child(1) { width: 80px; } 
    .info-table td:nth-child(2) { width: 10px; } 
    .info-table td:nth-child(3) { width: auto; } 

    /* ==========================================================================
       5. KELAS UTILITAS (BANTUAN)
       ========================================================================== */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .no-border { border: none !important; }
    .bg-yellow { background-color: #FFFF00 !important; }
    .bg-green  { background-color: #92D050 !important; } 

    table.main-table th.bg-grey, table.main-table td.bg-grey { 
        background-color: #e0e0e0 !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact;
    }
    
    .footer-note {
        border-top: 2px solid #000 !important;
        border-left: none !important;
        border-right: none !important;
        border-bottom: none !important;
        font-style: italic;
        font-weight: bold;
        text-align: right;
        padding: 2px 5px;
        font-size: 8px;
    }

    /* ==========================================================================
       6. PEMISAH HALAMAN (PAGE BREAK)
       ========================================================================== */
    .page-break {
        display: block;
        border-top: 2px dashed #999;
        margin: 30px 0;
        height: 20px;
        position: relative;
        visibility: visible;
    }
    
    .page-break::after {
        content: "--- BATAS HALAMAN ---";
        position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
        background: #f4f4f4; padding: 0 10px; color: #555; font-size: 10px;
    }

    @media screen {
        .table-container { margin: 10px auto; min-height: 297mm; }
    }

    /* ==========================================================================
       7. MODIFIKASI KHUSUS PDF
       ========================================================================== */
    body.pdf-mode .page-break { height: 0 !important; margin: 0 !important; padding: 0 !important; border: none !important; visibility: hidden !important; }
    body.pdf-mode .page-break::after { content: none !important; display: none !important; }
    body.pdf-mode .table-container {
        margin: 0 !important; width: 100% !important; box-shadow: none !important;
        padding: 2mm 10mm 10mm 10mm !important; min-height: auto !important;
    }
    @media print {
        .page-break { display: none !important; }
        .table-container { box-shadow: none; border: none; }
    }
    </style>
</head>
<body>

@php
    if (!function_exists('ribuan')) {
        function ribuan($nilai) {
            return ($nilai > 0) ? number_format($nilai, 0, ',', '.') : '-';
        }
    }
@endphp

<div id="print-area">
    <div class="table-container">
        <table class="main-table">
            {{-- 🔥 PERBAIKAN: 15 tag <col> untuk 15 Kolom 🔥 --}}
            <colgroup><col><col><col><col><col><col><col><col><col><col><col><col><col><col><col></colgroup>

            <thead>
                <tr>
                    {{-- colspan 14 diganti jadi 15 --}}
                    <td colspan="15" class="no-border text-center" style="font-size:8px; font-weight:bold; line-height: 1.1; padding-top: 30px; padding-bottom: 10px;">
                        REKAPITULASI LAPORAN HARIAN<br>
                        PENERIMAAN BOKAR, PROSES PENGOLAHAN & PRODUKSI SIR-20
                    </td>
                </tr>
                <tr class="no-border">
                    <td colspan="15" class="no-border">
                        <table class="info-table">
                            <colgroup><col style="width: 50px;"><col style="width: 1%;"><col style="width: auto;"></colgroup>
                            <tr><td>PKR</td><td class="text-center">:</td><td>KARANG BINTANG PT.NBL</td></tr>
                            <tr><td>BULAN</td><td class="text-center">:</td><td>{{ $tanggal->translatedFormat('F Y') }}</td></tr>
                            <tr><td>HARI/TGL</td><td class="text-center">:</td><td>{{ $tanggal->translatedFormat('d F Y') }}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr class="no-border"><td colspan="15" class="no-border" style="height:2px;"></td></tr>
            </thead>

            <tbody>
                {{-- TABEL I: BOKAR --}}
                <tr>
                    <th rowspan="2">NO.</th>
                    <th rowspan="2">URAIAN</th>
                    <th rowspan="2">Stock Awal</th>
                    <th colspan="3">Penerimaan Bokar (Kg KK)</th>
                    <th rowspan="2">Jumlah Stock Bokar</th>
                    <th colspan="3">Bokar Diproses (Kg KK)</th>
                    <th rowspan="2" colspan="2">Rektif</th>
                    <th rowspan="2">Stock Akhir</th>
                    <th rowspan="2" colspan="2">Keterangan</th>
                </tr>
                <tr>
                    <th>S/d kemarin</th><th>Masuk HI</th><th>S/D HI</th>
                    <th>Hari Ini</th><th colspan="2">S/d HI</th>
                </tr>
                
                @php 
                    $no = 1; 
                    $labels = ['DS' => 'Pembelian Bokar Rakyat', 'PT' => 'Pembelian Bokar PTPN', 'INHUT' => 'Pembelian Bokar INHUTANI'];
                @endphp

                @foreach($labels as $key => $label)
                    @php 
                        $d = $rekapBokar[$key]; 
                        $jmlStock = $d['stok_awal'] + $d['masuk_hi'];
                        $stockAkhir = $jmlStock - $d['kering_hi'] + $d['rektif'];
                    @endphp
                    <tr>
                        <td class="text-center">I.{{ $no++ }}</td>
                        <td>{{ $label }}</td>
                        <td class="text-right bg-green">{{ ribuan($d['stok_awal']) }}</td>
                        <td class="text-right bg-green">{{ ribuan($d['basah_sdhi']) }}</td>
                        <td class="text-right">{{ ribuan($d['masuk_hi']) }}</td>
                        <td class="text-right">{{ ribuan($d['masuk_sdhi']) }}</td>
                        <td class="text-right">{{ ribuan($jmlStock) }}</td>
                        <td class="text-right">{{ ribuan($d['kering_hi']) }}</td>
                        <td class="text-right bg-green" colspan="2">{{ ribuan($d['kering_sdhi']) }}</td>
                        <td class="text-center" colspan="2">{{ ribuan($d['rektif']) }}</td>
                        <td class="text-right">{{ ribuan($stockAkhir) }}</td>
                        <td colspan="2" class="text-center">-</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="2" class="text-center">TOTAL</td>
                    <td class="text-right">{{ number_format(collect($rekapBokar)->sum('stok_awal'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($rekapBokar)->sum('basah_sdhi'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($rekapBokar)->sum('masuk_hi'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($rekapBokar)->sum('masuk_sdhi'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($rekapBokar)->sum(function($i){ return $i['stok_awal']+$i['masuk_hi']; }),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($rekapBokar)->sum('kering_hi'),0,',','.') }}</td>
                    <td class="text-right" colspan="2">{{ number_format(collect($rekapBokar)->sum('kering_sdhi'),0,',','.') }}</td>
                    <td class="text-center" colspan="2">{{ number_format(collect($rekapBokar)->sum('rektif'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($rekapBokar)->sum(function($i){ return ($i['stok_awal']+$i['masuk_hi'])-$i['kering_hi']+$i['rektif']; }),0,',','.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>

            <tbody><tr><td colspan="15" class="no-border" style="height:10px;"></td></tr></tbody>

            {{-- 🔥 TABEL II: MATURASI (SUDAH DITAMBAH REKTIF) 🔥 --}}
            <tbody>
                <tr>
                    <th rowspan="2">NO.</th>
                    <th rowspan="2">URAIAN PROSES</th>
                    <th colspan="3">Stock Awal</th> <th colspan="2">Diproses HI</th>
                    <th rowspan="1">Masuk</th>
                    <th colspan="3" class="bg-grey">Quality</th> 
                    <th rowspan="2">Rektif</th> {{-- 🔥 KOLOM BARU 🔥 --}}
                    <th rowspan="2">Stock Akhir</th>
                    <th rowspan="2">Asal</th>
                    <th rowspan="2">Keterangan</th>
                </tr>
                <tr>
                    <th>Kg</th><th>Tgl</th><th>Umur</th>
                    <th>Diolah</th><th>Mutasi</th>
                    <th>HI</th>
                    <th class="bg-grey">K3</th><th class="bg-grey">Po</th><th class="bg-grey">PRI</th>
                </tr>

                @foreach($dataMaturasi as $index => $m)
                    @if($index == 7 || $index == 25)
                        <tr class="bg-yellow" style="font-weight:bold;">
                            <td colspan="15" class="text-center">JALAN</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-center">2.{{ $index+1 }}</td>
                        <td>{{ $m->no_bak }}</td>
                        <td class="text-right">{{ number_format($m->kering ?? 0, 0, ',', '.') }}</td>
                        <td class="text-center font-weight-bold">{{ $m->tgl_isi ? strtoupper(\Carbon\Carbon::parse($m->tgl_isi)->translatedFormat('d M Y')) : 'KOSONG' }}</td>
                        <td class="text-center">{{ ($m->umur ?? 0) . ' hari' }}</td>
                        <td class="text-right">{{ $m->diolah > 0 ? number_format($m->diolah, 0, ',', '.') : '' }}</td>
                        <td class="text-right">
                            @if($m->mutasi > 0)
                                <span>({{ number_format($m->mutasi, 0, ',', '.') }})</span>
                            @elseif($m->mutasi < 0)
                                <span>{{ number_format(abs($m->mutasi), 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ $m->masuk_hi > 0 ? number_format($m->masuk_hi, 0, ',', '.') : '' }}</td>
                        <td class="text-center">{{ $m->k3_olah > 0 ? number_format($m->k3_olah, 2, ',', '.') : '' }}</td>
                        <td class="text-center">{{ ($m->po && $m->po != '-' && $m->po != 0) ? $m->po : '' }}</td>
                        <td class="text-center">{{ ($m->pri && $m->pri != '-' && $m->pri != 0) ? $m->pri : '' }}</td>
                        
                        {{-- 🔥 DATA REKTIF MATURASI 🔥 --}}
                        <td class="text-right">
                            @if(($m->rektif ?? 0) > 0)
                                {{ number_format($m->rektif, 0, ',', '.') }}
                            @elseif(($m->rektif ?? 0) < 0)
                                ({{ number_format(abs($m->rektif), 0, ',', '.') }})
                            @else
                                -
                            @endif
                        </td>
                        
                        <td class="text-right">{{ number_format($m->stok_akhir ?? 0, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $m->jenis ?? '-' }}</td>
                        <td class="text-center" style="font-size:7px;">{{ $m->keterangan ?? 'KOSONG' }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="2" class="text-center">Jumlah 2.1 - 2.{{ count($dataMaturasi) }}</td>
                    <td class="text-right">{{ number_format(collect($dataMaturasi)->sum('kering'),0,',','.') }}</td>
                    <td></td><td></td>
                    <td class="text-right">{{ number_format(collect($dataMaturasi)->sum('diolah'),0,',','.') }}</td>
                    <td class="text-right font-weight-bold">
                        @php $totMutasi = collect($dataMaturasi)->sum('mutasi'); @endphp
                        @if($totMutasi > 0.1)
                            <span>({{ number_format($totMutasi, 0, ',', '.') }})</span>
                        @elseif($totMutasi < -0.1)
                            <span>{{ number_format(abs($totMutasi), 0, ',', '.') }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format(collect($dataMaturasi)->sum('masuk_hi'),0,',','.') }}</td>
                    <td colspan="3"></td> 
                    
                    {{-- TOTAL REKTIF --}}
                    <td class="text-right font-weight-bold">
                        @php $totRektif = collect($dataMaturasi)->sum('rektif'); @endphp
                        @if($totRektif > 0.1)
                            {{ number_format($totRektif, 0, ',', '.') }}
                        @elseif($totRektif < -0.1)
                            ({{ number_format(abs($totRektif), 0, ',', '.') }})
                        @else
                            -
                        @endif
                    </td>
                    
                    <td class="text-right">{{ number_format(collect($dataMaturasi)->sum('stok_akhir'),0,',','.') }}</td>
                    <td colspan="2"></td>
                </tr>
                
                <tr>
                    <td colspan="2" class="text-center bold">Maturasi Diolah</td>
                    <td class="text-right bold">s/d Kemarin</td> <td class="text-right bold bg-green">{{ ribuan(\App\Models\PengolahanMaturasi::whereDate('tgl_laporan','<',$tanggal)->sum('diolah')) }}</td> 
                    <td class="text-right bold">Hari ini</td> <td class="text-right bold">{{ ribuan(collect($dataMaturasi)->sum('diolah')) }}</td> 
                    <td colspan="6" class="text-right bold">s/d Hari ini</td> {{-- colspan diganti dari 5 jadi 6 --}}
                    <td class="text-right bold">{{ ribuan(\App\Models\PengolahanMaturasi::whereDate('tgl_laporan','<=',$tanggal)->sum('diolah')) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>

            <tfoot><tr><td colspan="15" class="footer-note">-- LIHAT DIBALIKNYA --</td></tr></tfoot>
        </table>
    </div>

    <div class="page-break"></div>

    <div class="table-container">
        <table class="main-table">
            {{-- 🔥 PERBAIKAN: 15 tag <col> untuk 15 Kolom di HALAMAN 2 🔥 --}}
            <colgroup><col><col><col><col><col><col><col><col><col><col><col><col><col><col><col></colgroup>

            <thead>
                <tr>
                    <td colspan="15" class="no-border text-center" style="font-size:8px; font-weight:bold; line-height: 1.1; padding-top: 30px; padding-bottom: 10px;">
                        REKAPITULASI LAPORAN HARIAN<br>
                        PENERIMAAN BOKAR, PROSES PENGOLAHAN & PRODUKSI SIR-20
                    </td>
                </tr>
                <tr class="no-border">
                    <td colspan="15" class="no-border">
                        <table class="info-table">
                            <colgroup><col style="width: 50px;"><col style="width: 1%;"><col style="width: auto;"></colgroup>
                            <tr><td>PKR</td><td class="text-center">:</td><td>KARANG BINTANG PT.NBL</td></tr>
                            <tr><td>BULAN</td><td class="text-center">:</td><td>{{ $tanggal->translatedFormat('F Y') }}</td></tr>
                            <tr><td>HARI/TGL</td><td class="text-center">:</td><td>{{ $tanggal->translatedFormat('d F Y') }}</td></tr>
                        </table>
                    </td>
                </tr>
                <tr class="no-border"><td colspan="15" class="no-border" style="height:2px;"></td></tr>
            </thead>
            
            <tbody>
                {{-- TABEL III: WIP --}}
                <tr>
                    <th rowspan="2">NO.</th> 
                    <th rowspan="2" colspan="2">URAIAN</th> 
                    <th rowspan="2">Saldo Awal</th> 
                    <th colspan="2">WIP</th> 
                    <th rowspan="2">Produksi SIR20</th> 
                    <th rowspan="2">Rektif</th> 
                    <th rowspan="2" colspan="2">Saldo Akhir</th> 
                    <th rowspan="2" colspan="5">Keterangan</th>
                </tr>
                <tr><th>Masuk</th><th>Keluar</th></tr>

                @foreach($dataWip as $index => $w)
                <tr>
                    <td class="text-center">3.{{ $index+1 }}</td>
                    <td colspan="2">{{ $w->uraian }}</td>
                    <td class="text-right bg-green">{{ ribuan($w->stok_awal) }}</td>
                    <td class="text-right">{{ ribuan($w->masuk) }}</td>
                    <td class="text-right">{{ ribuan($w->keluar) }}</td>
                    <td class="text-right {{ ($index+1) == 8 ? 'bg-green' : '' }}">
                        @if($w->uraian != 'Di Reproses Ex WS.') {{ number_format($w->produksi_sir20, 0, ',', '.') }} @endif
                    </td>
                    <td class="text-center">
                        @if($w->uraian != 'Di Reproses Ex WS.') {{ number_format($w->rektif, 0, ',', '.') }} @else - @endif
                    </td>
                    <td class="text-right" colspan="2">
                        @if($w->uraian != 'Di Reproses Ex WS.') {{ number_format($w->stok_akhir, 0, ',', '.') }} @endif
                    </td>
                    <td class="text-center" colspan="5">{{ $w->keterangan }}</td>
                </tr>
                @endforeach

                <tr>
                    <td colspan="3" class="text-center fw-bold">Jumlah 3.1 - 3.{{ count($dataWip) }}</td>
                    <td class="text-right fw-bold">{{ number_format(collect($dataWip)->sum('stok_awal'),0,',','.') }}</td>
                    
                    <td class="text-right"></td> 
                    <td class="text-right"></td>
                    <td class="text-right"></td>
                    <td class="text-right"></td> 
                    
                    <td class="text-right fw-bold" colspan="2">{{ number_format(collect($dataWip)->sum('stok_akhir'),0,',','.') }}</td>
                    
                    <td class="text-right fw-bold" colspan="5">
                        {{ number_format($grandTotalKeterangan, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>

            <tbody><tr><td colspan="15" class="no-border" style="height:10px;"></td></tr></tbody>

            <tbody>
                {{-- TABEL IV: GUDANG SIR --}}
                {{-- 🔥 EKSTRAK KETERANGAN KONTRAK --}}
                @php
                    $keteranganKontrak = '';
                    foreach($dataGudang as $g) {
                        if(isset($g->keterangan) && $g->keterangan != '-') {
                            $keteranganKontrak = $g->keterangan;
                            break;
                        }
                    }
                @endphp
                
                {{-- 🔥 TAMPILKAN BARIS KONTRAK DI ATAS PENGIRIMAN JIKA ADA --}}
                @if($keteranganKontrak != '')
                <tr>
                    <td colspan="8" class="no-border"></td>
                    <td colspan="7" class="text-center no-border" style="font-size: 8px; padding-bottom: 2px;">
                        {{ $keteranganKontrak }}
                    </td>
                </tr>
                @endif
                
                <tr>
                    <th rowspan="2">NO.</th>
                    <th rowspan="2" colspan="2">Stock Dalam Gudang SIR</th>
                    <th rowspan="2">Saldo Awal</th>
                    <th rowspan="2">Masuk</th>
                    <th rowspan="2">Total</th>
                    <th colspan="2">Produksi Bulan Ini</th> 
                    <th rowspan="2" colspan="2">Pengiriman</th>
                    <th rowspan="2">Rektif</th>
                    <th rowspan="2">Saldo Akhir</th>
                    <th rowspan="2">PTNB</th>
                    <th rowspan="2" class="bg-yellow" colspan="2">TOTAL I SD IV</th>
                </tr>
                <tr><th>Yg lalu</th><th>s/d HI</th></tr>

                @php
                    $grandTotalSaldoAkhir = collect($rekapBokar)->sum(function($i){ return ($i['stok_awal']+$i['masuk_hi'])-$i['kering_hi']+($i['rektif']??0); }) 
                                          + collect($dataMaturasi)->sum('stok_akhir')
                                          + collect($dataWip)->sum('stok_akhir')
                                          + collect($dataGudang)->sum('stok_akhir');
                @endphp

                @foreach($dataGudang as $index => $g)
                <tr>
                    <td class="text-center">4.{{ $index+1 }}</td>
                    <td colspan="2">{{ $g->uraian }}</td>
                    <td class="text-right {{ $loop->first ? 'bg-green' : '' }}">{{ ribuan($g->stok_awal) }}</td>
                    <td class="text-right">{{ ribuan($g->prod_hi) }}</td>
                    <td class="text-right">{{ number_format($g->stok_awal + $g->prod_hi,0,',','.') }}</td>
                    <td class="text-right {{ $loop->first ? 'bg-green' : '' }}">{{ number_format($g->prod_bln_lalu ?? 0,0,',','.') }}</td> 
                    <td class="text-right">{{ number_format($g->prod_sdhi,0,',','.') }}</td>
                    <td class="text-right" colspan="2">{{ ribuan($g->pengiriman) }}</td>
                    <td class="text-center">-</td>
                    <td class="text-right font-weight-bold">{{ number_format($g->stok_akhir,0,',','.') }}</td>
                    @if($loop->first) 
                        <td class="text-right bold">{{ number_format($g->stok_akhir,0,',','.') }}</td>
                    @else
                        <td class="text-center">-</td>
                    @endif
                    <td class="text-center" colspan="2">-</td>
                </tr>
                @endforeach

                <tr class="bg-light font-weight-bold">
                    <td colspan="3" class="text-center">Jumlah 4.1 - 4.{{ count($dataGudang) }}</td>
                    <td class="text-right">{{ number_format(collect($dataGudang)->sum('stok_awal'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($dataGudang)->sum('prod_hi'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($dataGudang)->sum(function($i){ return $i->stok_awal + $i->prod_hi; }),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($dataGudang)->sum('prod_bln_lalu'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($dataGudang)->sum('prod_sdhi'),0,',','.') }}</td>
                    <td class="text-right" colspan="2">{{ number_format(collect($dataGudang)->sum('pengiriman'),0,',','.') }}</td>
                    <td class="text-center">-</td>
                    <td class="text-right">{{ number_format(collect($dataGudang)->sum('stok_akhir'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($dataGudang)->sum('stok_akhir'),0,',','.') }}</td>
                    <td class="text-right bg-yellow" colspan="2">{{ number_format($grandTotalSaldoAkhir,0,',','.') }}</td>
                </tr>
            </tbody>

            <tbody><tr><td colspan="15" class="no-border" style="height:10px;"></td></tr></tbody>

            <tbody>
                {{-- TABEL V: PENJUALAN --}}
                <tr>
                    <th rowspan="2">NO.</th>
                    <th rowspan="2" colspan="2">PENJUALAN (SIR-20)</th>
                    <th rowspan="2">s/d {{ $tanggal->subMonth()->format('M Y') }}</th>
                    <th colspan="2">Penjualan Bulan Ini</th>
                    <th rowspan="2" colspan="3">Total Bulan Ini</th>
                    <th rowspan="2" colspan="3">Total Penjualan s/d Hari Ini</th>
                    <th rowspan="2" colspan="3">Keterangan</th> {{-- colspan dari 2 jadi 3 --}}
                </tr>
                <tr><th>Yg Lalu</th><th>Hari Ini</th></tr>

                @foreach($dataPenjualan as $index => $p)
                <tr>
                    <td class="text-center">5.{{ $index+1 }}</td>
                    <td colspan="2">{{ $p->uraian }}</td>
                    <td class="text-right">{{ number_format($p->sd_bulan_lalu,0,',','.') }}</td>
                    <td class="text-right {{ $loop->first ? 'bg-green' : '' }}">{{ number_format($p->bln_ini_lalu,0,',','.') }}</td>
                    <td class="text-right">{{ number_format($p->hari_ini,0,',','.') }}</td>
                    <td class="text-right" colspan="3">{{ number_format($p->total_bln_ini,0,',','.') }}</td>
                    <td class="text-right" colspan="3">{{ number_format($p->total_sd_hari_ini,0,',','.') }}</td>
                    <td class="text-center" colspan="3">{{ $p->keterangan }}</td> {{-- colspan dari 2 jadi 3 --}}
                </tr>
                @endforeach

                <tr>
                    <td colspan="3" class="text-center">JUMLAH 5.1 - 5.{{ count($dataPenjualan) }}</td>
                    <td class="text-right">{{ number_format(collect($dataPenjualan)->sum('sd_bulan_lalu'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($dataPenjualan)->sum('bln_ini_lalu'),0,',','.') }}</td>
                    <td class="text-right">{{ number_format(collect($dataPenjualan)->sum('hari_ini'),0,',','.') }}</td>
                    <td class="text-right" colspan="3">{{ number_format(collect($dataPenjualan)->sum('total_bln_ini'),0,',','.') }}</td>
                    <td class="text-right" colspan="3">{{ number_format(collect($dataPenjualan)->sum('total_sd_hari_ini'),0,',','.') }}</td>
                    <td colspan="3"></td> {{-- colspan dari 2 jadi 3 --}}
                </tr>
            </tbody>

            <tbody><tr><td colspan="15" class="no-border" style="height:10px;"></td></tr></tbody>

            <tbody>
                {{-- TABEL VI: MUTU --}}
                <tr>
                    <th>NO.</th>
                    <th colspan="2">Uraian</th> <th>Kg</th> <th>Pallet</th> <th colspan="10">Keterangan</th> {{-- colspan dari 9 jadi 10 --}}
                </tr>

                @foreach($dataMutu as $idx => $r)
                <tr>
                    <td class="text-center">6.{{ $idx+1 }}</td>
                    <td colspan="2">{{ $r[0] }}</td>
                    <td class="text-right">{{ ribuan($r[1]) }}</td>
                    <td class="text-right">{{ ribuan($r[2]) }}</td>
                    <td colspan="10" class="text-center">-</td> {{-- colspan dari 9 jadi 10 --}}
                </tr>
                @endforeach

                <tr>
                    <td colspan="3" class="text-center">JUMLAH 6.1 - 6.{{ count($dataMutu) }}</td>
                    <td class="text-right">{{ number_format(collect($dataMutu)->sum('1'),0,',','.') }}</td>
                    <td class="text-right">{{ collect($dataMutu)->sum('2') }}</td>
                    <td colspan="10"></td> {{-- colspan dari 9 jadi 10 --}}
                </tr>
            </tbody>

            <tbody>
                <tr><td colspan="15" class="no-border" style="height:2px;"></td></tr>
                
                <tr class="no-border">
                    <td colspan="4" class="text-center no-border" style="vertical-align: top;">
                        <br><br><br><br><br><br><br><br> 
                        <b><u>{{ $ttd_kiri_nama }}</u></b><br>
                        {{ $ttd_kiri_jabatan }}
                    </td>

                    {{-- 🔥 TTD TENGAH: colspan diganti dari 4 menjadi 5 🔥 --}}
                    <td colspan="5" class="text-center no-border" style="vertical-align: top; padding-top: 0;">
                        <b>PT. NUSANTARA BATULICIN</b>
                    </td>

                    <td colspan="6" class="text-center no-border" style="vertical-align: top;">
                        <br><br><br><br><br><br><br><br> 
                        <b><u>{{ $ttd_kanan_nama }}</u></b><br>
                        {{ $ttd_kanan_jabatan }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        window.generatePDF = function() {
            var element = document.getElementById('print-area');
            document.body.classList.add('pdf-mode');
            document.body.style.cursor = 'wait';

            var opt = {
                margin:       [0, 0, 0, 0], 
                filename:     'Laporan_Harian_Produksi_{{ $tanggal->format("d-m-Y") }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, scrollY: 0 }, 
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: ['css', 'legacy'], avoid: 'tr' } 
            };

            html2pdf().set(opt).from(element).save()
            .then(function(){
                document.body.classList.remove('pdf-mode');
                document.body.style.cursor = 'default';
            }, function(err) {
                document.body.classList.remove('pdf-mode');
                console.error(err);
                document.body.style.cursor = 'default';
            });
        };
    });
</script>
</body>
</html>