<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        * { font-family: 'Arial Narrow', Arial, sans-serif !important; font-size: 12pt !important; color: #000000 !important; }
        table { width: 100%; border-collapse: collapse; border: 1px solid #000000; }
        th, td { border: 1px solid #000000; padding: 3px; vertical-align: middle; }
        .no-border { border: none !important; }
    </style>
</head>
<body>

@php
    // 🔥 FUNGSI FORMAT ANGKA ANTI-EXCEL (100% DIJAMIN NOL TIDAK HILANG) 🔥
    if (!function_exists('formatAngka')) { 
        function formatAngka($nilai) { 
            if (is_null($nilai) || $nilai === '' || $nilai === false) return '0';
            
            $floatVal = (float) str_replace(',', '', $nilai);
            if ($floatVal == 0) return '0';
            
            $formatted = number_format(abs($floatVal), 0, ',', '.');
            if ($floatVal < 0) {
                $formatted = '(' . $formatted . ')';
            }
            
            // 🔥 TRIK RAHASIA: Karakter tidak terlihat ini memaksa Excel membacanya murni sebagai teks string.
            // Angka 33.600 dijamin UTUH, Excel tidak akan bisa mengubahnya jadi 33,6.
            return html_entity_decode('&#8203;') . $formatted; 
        } 
    }
    
    $grandTotalSaldoAkhir = collect($rekapBokar)->sum(function($i){ return ($i['stok_awal'] ?? 0)+($i['masuk_hi'] ?? 0)-($i['kering_hi'] ?? 0)+($i['rektif'] ?? 0); }) 
                          + collect($dataMaturasi)->sum('stok_akhir')
                          + collect($dataWip)->sum('stok_akhir')
                          + collect($dataGudang)->sum('stok_akhir');

    // 🔥 PENGUNCI FORMAT CSS 🔥
    $sty = "border: 1px solid #000000; font-family: 'Arial Narrow', Arial, sans-serif; font-size: 12pt; color: #000000; vertical-align: middle; padding: 3px;";
    $styNoB = "border: none; font-family: 'Arial Narrow', Arial, sans-serif; font-size: 12pt; color: #000000; vertical-align: middle; padding: 3px;";
    $styHead = $sty . " text-align: center; font-weight: bold; background-color: #ffffff;";
    
    $styNum = $sty . " text-align: right;";
    $styNumCenter = $sty . " text-align: center;";
@endphp

<table border="1" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; border: 1px solid #000000;">
    
    {{-- ================= GRID 16 KOLOM SUPER PRESISI ================= --}}
    <colgroup>
        <col width="40">  {{-- Col 1: Romawi --}}
        <col width="50">  {{-- Col 2: Angka --}}
        <col width="230"> {{-- Col 3: Uraian --}}
        <col width="100"> {{-- Col 4: Stock Awal --}}
        <col width="100"> {{-- Col 5: S/d kemarin --}}
        <col width="100"> {{-- Col 6: Masuk HI --}}
        <col width="100"> {{-- Col 7: S/D HI --}}
        <col width="110"> {{-- Col 8: Jumlah Stock Bokar --}}
        <col width="100"> {{-- Col 9: Hari Ini --}}
        <col width="80">  {{-- Col 10: K3 --}}
        <col width="80">  {{-- Col 11: Po --}}
        <col width="80">  {{-- Col 12: PRI --}}
        <col width="90">  {{-- Col 13: Rektif --}}
        <col width="110"> {{-- Col 14: Stock Akhir --}}
        <col width="110"> {{-- Col 15: Asal Bokar --}}
        <col width="150"> {{-- Col 16: Keterangan --}}
    </colgroup>

    {{-- ================= HEADER HALAMAN ================= --}}
    <thead>
        <tr><th colspan="16" style="{{ $styNoB }} text-align: center; font-weight: bold;">REKAPITULASI LAPORAN HARIAN</th></tr>
        <tr><th colspan="16" style="{{ $styNoB }} text-align: center; font-weight: bold;">PENERIMAAN BOKAR , PROSES PENGOLAHAN &amp; PRODUKSI SIR-20</th></tr>
        <tr><th colspan="16" style="{{ $styNoB }} height: 15px;"></th></tr>
        <tr>
            <td colspan="2" style="{{ $styNoB }} text-align: left; font-weight: bold; white-space: nowrap;">PKR</td>
            <td colspan="14" style="{{ $styNoB }} text-align: left; font-weight: bold;">: KARANG BINTANG PT.NBL</td>
        </tr>
        <tr>
            <td colspan="2" style="{{ $styNoB }} text-align: left; font-weight: bold; white-space: nowrap;">BULAN</td>
            <td colspan="14" style="{{ $styNoB }} text-align: left; font-weight: bold;">: {{ $tanggal->locale('id')->isoFormat('MMMM Y') }}</td>
        </tr>
        <tr>
            <td colspan="2" style="{{ $styNoB }} text-align: left; font-weight: bold; white-space: nowrap;">HARI/TGL</td>
            <td colspan="14" style="{{ $styNoB }} text-align: left; font-weight: bold;">: {{ $tanggal->locale('id')->isoFormat('dddd, DD MMMM Y') }}</td>
        </tr>
        <tr><td colspan="16" style="{{ $styNoB }} height: 10px;"></td></tr>
    </thead>

    {{-- ================= BODY DATA ================= --}}
    <tbody>
        {{-- ================= TABEL 1: BOKAR ================= --}}
        <tr>
            <th rowspan="2" colspan="2" style="{{ $styHead }}">NO.</th>
            <th rowspan="2" style="{{ $styHead }}">URAIAN</th>
            <th rowspan="2" style="{{ $styHead }}">Stock Awal</th>
            <th colspan="3" style="{{ $styHead }}">Penerimaan Bokar (Kg KK)</th>
            <th rowspan="2" style="{{ $styHead }}">Jumlah Stock Bokar</th> 
            <th colspan="4" style="{{ $styHead }}">Bokar Diproses (Kg KK)</th>
            <th rowspan="2" style="{{ $styHead }}">Rektif</th>
            <th rowspan="2" style="{{ $styHead }}">Stock Akhir</th>
            <th rowspan="2" colspan="2" style="{{ $styHead }}">Keterangan</th>
        </tr>
        <tr>
            <th style="{{ $styHead }}">S/d kemarin</th>
            <th style="{{ $styHead }}">Masuk HI</th>
            <th style="{{ $styHead }}">S/D HI</th>
            <th style="{{ $styHead }}">Hari Ini</th>
            <th colspan="3" style="{{ $styHead }}">S/d HI</th>
        </tr>
        
        <tr><td colspan="2" style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td colspan="3" style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td colspan="2" style="{{ $sty }}"></td></tr>

        <tr>
            <td style="{{ $sty }} text-align: center; font-weight: normal;">I.</td><td style="{{ $sty }}"></td> 
            <td style="{{ $sty }} font-weight: bold; white-space: nowrap;">PENGADAAN BOKAR / Raw Material</td>
            <td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td colspan="3" style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td colspan="2" style="{{ $sty }}"></td>
        </tr>

        @php $no = 1; $labels = ['DS' => 'Pembelian Bokar Rakyat / Petani', 'PT' => 'Pembelian Bokar PT.PN Kebun Batulicin', 'INHUT' => 'Pembelian Bokar PT. INHUTANI 1']; @endphp
        @foreach($labels as $key => $label)
            @php 
                $d = $rekapBokar[$key] ?? []; 
                $jmlStock = ($d['stok_awal'] ?? 0) + ($d['masuk_hi'] ?? 0); 
                $stockAkhir = $jmlStock - ($d['kering_hi'] ?? 0) + ($d['rektif'] ?? 0); 
            @endphp
            <tr>
                <td style="{{ $sty }}"></td>
                <td style="{{ $sty }} text-align: center; font-weight: normal;">I.{{ $no++ }}.</td>
                <td style="{{ $sty }}">{{ $label }}</td>
                <td style="{{ $styNum }} background-color: #92d050;">{{ formatAngka($d['stok_awal'] ?? 0) }}</td>
                <td style="{{ $styNum }} background-color: #92d050;">{{ formatAngka($d['basah_sdhi'] ?? 0) }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($d['masuk_hi'] ?? 0) }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($d['masuk_sdhi'] ?? 0) }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($jmlStock) }}</td>
                <td style="{{ $styNum }} background-color: #92d050;">{{ formatAngka($d['kering_hi'] ?? 0) }}</td>
                <td colspan="3" style="{{ $styNum }} background-color: #92d050;">{{ formatAngka($d['kering_sdhi'] ?? 0) }}</td>
                <td style="{{ $styNumCenter }}">{{ formatAngka($d['rektif'] ?? 0) }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($stockAkhir) }}</td>
                <td colspan="2" style="{{ $sty }} text-align: center;">-</td>
            </tr>
        @endforeach
        
        <tr>
            <td colspan="3" style="{{ $sty }} text-align: center; font-weight: bold;">Total</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum('stok_awal')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum('basah_sdhi')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum('masuk_hi')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum('masuk_sdhi')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum(function($i){ return ($i['stok_awal'] ?? 0)+($i['masuk_hi'] ?? 0); })) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum('kering_hi')) }}</td>
            <td colspan="3" style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum('kering_sdhi')) }}</td>
            <td style="{{ $styNumCenter }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum('rektif')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($rekapBokar)->sum(function($i){ return ($i['stok_awal'] ?? 0)+($i['masuk_hi'] ?? 0)-($i['kering_hi'] ?? 0)+($i['rektif'] ?? 0); })) }}</td>
            <td colspan="2" style="{{ $sty }}"></td>
        </tr>

        <tr><td colspan="16" style="{{ $styNoB }} height: 15px;"></td></tr>

        ================= TABEL 2: MATURASI =================
        <tr>
            <th rowspan="2" colspan="2" style="{{ $styHead }}">NO.</th>
            <th rowspan="2" style="{{ $styHead }}">URAIAN PROSES</th>
            <th colspan="3" style="{{ $styHead }}">Stock Awal</th> 
            <th colspan="2" style="{{ $styHead }}">Diproses HI</th>
            <th rowspan="1" style="{{ $styHead }}">Masuk</th>
            <th colspan="3" style="{{ $styHead }} background-color: #e0e0e0;">Quality</th> 
            <th rowspan="2" style="{{ $styHead }}">Rektif</th>
            <th rowspan="2" style="{{ $styHead }}">Stock Akhir</th>
            <th rowspan="2" style="{{ $styHead }}">Asal Bokar</th>
            <th rowspan="2" style="{{ $styHead }}">Keterangan</th>
        </tr>
        <tr>
            <th style="{{ $styHead }}">Kg KK</th>
            <th style="{{ $styHead }} white-space: nowrap;">Tgl</th>
            <th style="{{ $styHead }}">Umur</th>
            <th style="{{ $styHead }}">Diolah</th>
            <th style="{{ $styHead }}">Mutasi</th>
            <th style="{{ $styHead }}">HI</th>
            <th style="{{ $styHead }} background-color: #e0e0e0;">K3</th>
            <th style="{{ $styHead }} background-color: #e0e0e0;">Po</th>
            <th style="{{ $styHead }} background-color: #e0e0e0;">PRI</th>
        </tr>
        
        <tr>
            <td style="{{ $sty }} text-align: center; font-weight: normal;">II.</td>
            <td style="{{ $sty }} text-align: center; font-weight: normal;">2</td>
            <td style="{{ $sty }} font-weight: bold; text-align: left;">Di Bangsal Maturasi :</td>
            <td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td>
        </tr>

        @foreach($dataMaturasi as $index => $m)
            @if($index == 7 || $index == 25)
                <tr><td colspan="16" style="{{ $sty }} background-color: #f8f805; text-align: center; font-weight: bold;">JALAN</td></tr>
            @endif
            <tr>
                <td style="{{ $sty }}"></td>
                <td style="{{ $sty }} text-align: center; font-weight: normal;">2.{{ $index+1 }}</td>
                <td style="{{ $sty }}">{{ $m->no_bak ?? '-' }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($m->kering ?? 0) }}</td>
                <td style="{{ $sty }} text-align: center; white-space: nowrap;">{{ !empty($m->tgl_isi) ? \Carbon\Carbon::parse($m->tgl_isi)->format('d-M-y') : 'KOSONG' }}</td>
                <td style="{{ $sty }} text-align: center;">{{ ($m->umur ?? 0) . ' hari' }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($m->diolah ?? 0) }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($m->mutasi ?? 0) }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($m->masuk_hi ?? 0) }}</td>
                <td style="{{ $sty }} text-align: center;">{{ ($m->k3_olah ?? 0) > 0 ? number_format($m->k3_olah, 2, ',', '.') : '' }}</td>
                <td style="{{ $sty }} text-align: center;">{{ ($m->po ?? '-') != '-' && ($m->po ?? 0) != 0 ? $m->po : '' }}</td>
                <td style="{{ $sty }} text-align: center;">{{ ($m->pri ?? '-') != '-' && ($m->pri ?? 0) != 0 ? $m->pri : '' }}</td>
                <td style="{{ $styNumCenter }}">{{ formatAngka($m->rektif ?? 0) }}</td>
                <td style="{{ $styNum }}">{{ formatAngka($m->stok_akhir ?? 0) }}</td>
                <td style="{{ $sty }} text-align: center;">{{ $m->jenis ?? '-' }}</td>
                <td style="{{ $sty }} text-align: center;">{{ $m->keterangan ?? 'KOSONG' }}</td>
            </tr>
        @endforeach

        <tr>
            <td style="{{ $sty }}"></td><td style="{{ $sty }}"></td>
            <td style="{{ $sty }} font-weight: bold; text-align: center;">Jumlah 2.1 - 2.{{ count($dataMaturasi) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataMaturasi)->sum('kering')) }}</td>
            <td style="{{ $sty }}"></td><td style="{{ $sty }}"></td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataMaturasi)->sum('diolah')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataMaturasi)->sum('mutasi')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataMaturasi)->sum('masuk_hi')) }}</td>
            <td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td>
            <td style="{{ $styNumCenter }} font-weight: bold;">0</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataMaturasi)->sum('stok_akhir')) }}</td>
            <td style="{{ $styNoB }}"></td><td style="{{ $styNoB }}"></td>
        </tr>
        
        <tr>
            <td colspan="3" style="{{ $sty }} text-align: right; font-weight: bold;">Maturasi Diolah</td> 
            <td style="{{ $sty }} text-align: center; font-weight: bold;">s/d Kemarin</td> 
            <td style="{{ $styNum }} background-color: #92d050; font-weight: bold;">
                {{ formatAngka(\App\Models\PengolahanMaturasi::whereDate('tgl_laporan', '<', $tanggal->format('Y-m-d'))->sum('diolah')) }}
            </td> 
            <td style="{{ $sty }} text-align: center; font-weight: bold;">Hari ini</td> 
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataMaturasi)->sum('diolah')) }}</td> 
            <td colspan="5" style="{{ $sty }} text-align: right; font-weight: bold;">s/d Hari ini</td> 
            <td style="{{ $sty }}"></td> 
            <td style="{{ $styNum }} font-weight: bold;">
                {{ formatAngka(\App\Models\PengolahanMaturasi::whereDate('tgl_laporan', '<=', $tanggal->format('Y-m-d'))->sum('diolah')) }}
            </td> 
            <td style="{{ $styNoB }}"></td><td style="{{ $styNoB }}"></td> 
        </tr>

        <tr><td colspan="16" style="{{ $styNoB }} font-weight: bold; text-align: left; padding-left: 20px;">LIHAT DIBALIKNYA</td></tr>
        <tr><td colspan="16" style="{{ $styNoB }} height: 30px;"></td></tr>

        {{-- ================= HEADER HALAMAN KEDUA ================= --}}
        <tr><th colspan="16" style="{{ $styNoB }} text-align: center; font-weight: bold; font-size: 14pt;">REKAPITULASI LAPORAN HARIAN</th></tr>
        <tr><th colspan="16" style="{{ $styNoB }} text-align: center; font-weight: bold; font-size: 14pt;">PENERIMAAN BOKAR , PROSES PENGOLAHAN &amp; PRODUKSI SIR-20</th></tr>
        <tr><th colspan="16" style="{{ $styNoB }} height: 15px;"></th></tr>
        <tr><td colspan="2" style="{{ $styNoB }} text-align: left; font-weight: bold;">PKR</td><td colspan="14" style="{{ $styNoB }} text-align: left; font-weight: bold;">: KARANG BINTANG PT.NBL</td></tr>
        <tr><td colspan="2" style="{{ $styNoB }} text-align: left; font-weight: bold;">BULAN</td><td colspan="14" style="{{ $styNoB }} text-align: left; font-weight: bold;">: {{ $tanggal->locale('id')->isoFormat('MMMM Y') }}</td></tr>
        <tr><td colspan="2" style="{{ $styNoB }} text-align: left; font-weight: bold;">HARI/TGL</td><td colspan="14" style="{{ $styNoB }} text-align: left; font-weight: bold;">: {{ $tanggal->locale('id')->isoFormat('dddd, DD MMMM Y') }}</td></tr>
        <tr><td colspan="16" style="{{ $styNoB }} height: 10px;"></td></tr>

        {{-- ================= TABEL 3: WIP (SUDAH DIPERBAIKI) ================= --}}
        <tr>
            <td rowspan="2" colspan="2" style="{{ $styHead }}">III.</td>
            <td rowspan="2" style="{{ $styHead }}">Uraian</td>
            <td rowspan="2" style="{{ $styHead }}">Saldo Awal</td>
            <td colspan="2" style="{{ $styHead }}">WIP</td>
            
            {{-- 🔥 Produksi SIR20 di Col 7, 8, 9 (colspan 3) 🔥 --}}
            <td rowspan="2" colspan="3" style="{{ $styHead }}">Produksi SIR20</td>
            
            {{-- 🔥 Rektif di Col 10 (K3) 🔥 --}}
            <td rowspan="2" style="{{ $styHead }}">Rektif</td>
            
            {{-- 🔥 Saldo Akhir di Col 11 (Po) 🔥 --}}
            <td rowspan="2" style="{{ $styHead }}">Saldo Akhir</td>
            
            {{-- 🔥 KETERANGAN DARI COL 12 (PRI) SAMPAI UJUNG COL 16 (KET) = COLSPAN 5 🔥 --}}
            <td rowspan="2" colspan="5" style="{{ $styHead }}">Keterangan</td>
        </tr>
        <tr>
            <td style="{{ $styHead }}">Masuk</td>
            <td style="{{ $styHead }}">Keluar</td>
        </tr>
        
        @foreach($dataWip as $index => $w)
        <tr>
            <td style="{{ $sty }}"></td>
            <td style="{{ $sty }} text-align: center;">3.{{ $index+1 }}</td>
            <td style="{{ $sty }}">{{ $w->uraian ?? '-' }}</td>
            <td style="{{ $styNum }} {{ $index > 0 ? 'background-color: #92d050;' : '' }}">{{ formatAngka($w->stok_awal ?? 0) }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($w->masuk ?? 0) }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($w->keluar ?? 0) }}</td>
            <td colspan="3" style="{{ $styNum }} {{ $index == 7 ? 'background-color: #92d050;' : '' }}">{{ formatAngka($w->produksi_sir20 ?? 0) }}</td>
            <td style="{{ $styNumCenter }}">{{ formatAngka($w->rektif ?? 0) }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($w->stok_akhir ?? 0) }}</td>
            <td colspan="5" style="{{ $sty }} text-align: center;">{{ $w->keterangan ?? '-' }}</td>
        </tr>
        @endforeach
        
        <tr><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td colspan="3" style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td colspan="5" style="{{ $sty }}"></td></tr>
        
        <tr>
            <td style="{{ $sty }}"></td><td style="{{ $sty }}"></td>
            <td style="{{ $sty }} font-weight: bold; text-align: center;">Jumlah 3.1 - 3.{{ count($dataWip) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataWip)->sum('stok_awal')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataWip)->sum('masuk')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataWip)->sum('keluar')) }}</td>
            <td colspan="3" style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataWip)->sum('produksi_sir20')) }}</td>
            <td style="{{ $styNumCenter }} font-weight: bold;">{{ formatAngka(collect($dataWip)->sum('rektif')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataWip)->sum('stok_akhir')) }}</td>
            <td colspan="5" style="{{ $sty }}"></td>
        </tr>

        {{-- 🔥 GRAND TOTAL 199.563 PERSIS DI BAWAH KOLOM KETERANGAN 🔥 --}}
        <tr>
            <td colspan="11" style="{{ $sty }} border-right: none;"></td>
            <td colspan="5" style="{{ $styNum }} font-weight: bold; border-left: none;">{{ formatAngka($grandTotalKeterangan ?? 0) }}</td> 
        </tr>

        <tr><td colspan="16" style="{{ $styNoB }} height: 10px;"></td></tr>

        {{-- ================= TABEL 4: GUDANG ================= --}}
        <tr><td colspan="9" style="{{ $styNoB }}"></td><td colspan="7" style="{{ $styNoB }} text-align: left;">LTC JANUARI 1 LOT BGS</td></tr>
        <tr>
            <td rowspan="2" colspan="2" style="{{ $styHead }}">IV.</td>
            <td rowspan="2" style="{{ $styHead }}">Stock Dalam Gudang SIR</td>
            <td rowspan="2" style="{{ $styHead }}">Saldo Awal</td>
            <td rowspan="2" style="{{ $styHead }}">Masuk</td>
            <td rowspan="2" style="{{ $styHead }}">Total</td>
            <td colspan="2" style="{{ $styHead }}">Produksi Bulan Ini</td> 
            <td rowspan="2" colspan="3" style="{{ $styHead }}">Pengiriman</td>
            <td rowspan="2" style="{{ $styHead }}">Rektif</td>
            <td rowspan="2" style="{{ $styHead }}">Saldo Akhir</td>
            <td rowspan="2" style="{{ $styHead }}">PTNB</td>
            <td rowspan="2" colspan="2" style="{{ $styHead }} background-color: #ffff00;">TOTAL I SD IV</td>
        </tr>
        <tr>
            <td style="{{ $styHead }}">Yg lalu</td>
            <td style="{{ $styHead }}">s/d HI</td>
        </tr>
        
        @foreach($dataGudang as $index => $g)
        <tr>
            <td style="{{ $sty }}"></td><td style="{{ $sty }} text-align: center;">4.{{ $index+1 }}</td>
            <td style="{{ $sty }}">{{ $g->uraian ?? '-' }}</td>
            <td style="{{ $styNum }} {{ $loop->first ? 'background-color: #92d050;' : '' }}">{{ formatAngka($g->stok_awal ?? 0) }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($g->prod_hi ?? 0) }}</td>
            <td style="{{ $styNum }}">{{ formatAngka(($g->stok_awal ?? 0) + ($g->prod_hi ?? 0)) }}</td>
            <td style="{{ $styNum }} {{ $loop->first ? 'background-color: #92d050;' : '' }}">{{ formatAngka($g->prod_bln_lalu ?? 0) }}</td> 
            <td style="{{ $styNum }}">{{ formatAngka($g->prod_sdhi ?? 0) }}</td>
            <td colspan="3" style="{{ $styNum }}">{{ formatAngka($g->pengiriman ?? 0) }}</td>
            <td style="{{ $sty }} text-align: center;">-</td>
            <td style="{{ $styNum }}">{{ formatAngka($g->stok_akhir ?? 0) }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($loop->last ? ($g->stok_akhir ?? 0) : 0) }}</td>
            <td colspan="2" style="{{ $styNum }}">{{ formatAngka($loop->last ? $grandTotalSaldoAkhir : 0) }}</td>
        </tr>
        @endforeach
        
        <tr>
            <td colspan="2" style="{{ $sty }}"></td>
            <td style="{{ $sty }} text-align: center; font-weight: bold;">Jumlah 4.1 - 4.{{ count($dataGudang) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum('stok_awal')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum('prod_hi')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum(function($q){ return ($q->stok_awal ?? 0) + ($q->prod_hi ?? 0); })) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum('prod_bln_lalu')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum('prod_sdhi')) }}</td>
            <td colspan="3" style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum('pengiriman')) }}</td>
            <td style="{{ $sty }}"></td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum('stok_akhir')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataGudang)->sum('stok_akhir')) }}</td>
            <td colspan="2" style="{{ $styNum }} font-weight: bold; background-color: #ffff00;">{{ formatAngka($grandTotalSaldoAkhir) }}</td>
        </tr>

        <tr><td colspan="16" style="{{ $styNoB }} height: 15px;"></td></tr>

        {{-- ================= TABEL 5: PENJUALAN ================= --}}
        <tr>
            <td rowspan="2" colspan="2" style="{{ $styHead }}">V.</td>
            <td rowspan="2" style="{{ $styHead }}">Telah Dijual (KG SIR-20)</td>
            <td rowspan="2" style="{{ $styHead }}">s/d <br> {{ $tanggal->copy()->subMonth()->locale('id')->isoFormat('MMMM Y') }}</td>
            <td colspan="2" style="{{ $styHead }}">Penjualan Bulan Ini</td>
            
            {{-- 🔥 TOTAL BULAN INI SEJAJAR COL 7-8 (s/d HI) -> COLSPAN 2 🔥 --}}
            <td rowspan="2" colspan="2" style="{{ $styHead }}">Total Bulan Ini</td>
            
            {{-- 🔥 TOTAL PENJUALAN SEJAJAR COL 9-13 (Pengiriman sd Saldo Akhir) -> COLSPAN 5 🔥 --}}
            <td rowspan="2" colspan="5" style="{{ $styHead }}">Total Penjualan <br> s/d Hari ini</td>
            
            <td rowspan="2" colspan="3" style="{{ $styHead }}">Keterangan</td>
        </tr>
        <tr>
            <td style="{{ $styHead }}">Yg lalu</td>
            <td style="{{ $styHead }}">Hari Ini</td>
        </tr>
        
        @foreach($dataPenjualan as $index => $p)
        <tr>
            <td style="{{ $sty }}"></td><td style="{{ $sty }} text-align: center;">5.{{ $index+1 }}</td>
            <td style="{{ $sty }}">{{ $p->uraian ?? '-' }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($p->sd_bulan_lalu ?? 0) }}</td>
            <td style="{{ $styNum }} {{ $loop->first ? 'background-color: #92d050;' : '' }}">{{ formatAngka($p->bln_ini_lalu ?? 0) }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($p->hari_ini ?? 0) }}</td>
            <td colspan="2" style="{{ $styNum }}">{{ formatAngka($p->total_bln_ini ?? 0) }}</td>
            <td colspan="5" style="{{ $styNum }}">{{ formatAngka($p->total_sd_hari_ini ?? 0) }}</td>
            <td colspan="3" style="{{ $sty }} text-align: center;">{{ $p->keterangan ?? '-' }}</td>
        </tr>
        @endforeach
        
        <tr>
            <td colspan="2" style="{{ $sty }}"></td>
            <td style="{{ $sty }} text-align: center; font-weight: bold;">JUMLAH 5.1 - 5.{{ count($dataPenjualan) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataPenjualan)->sum('sd_bulan_lalu')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataPenjualan)->sum('bln_ini_lalu')) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataPenjualan)->sum('hari_ini')) }}</td>
            <td colspan="2" style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataPenjualan)->sum('total_bln_ini')) }}</td>
            <td colspan="5" style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataPenjualan)->sum('total_sd_hari_ini')) }}</td>
            <td colspan="3" style="{{ $sty }} text-align: center;">-</td>
        </tr>
        
        <tr><td colspan="2" style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td style="{{ $sty }}"></td><td colspan="2" style="{{ $sty }}"></td><td colspan="5" style="{{ $sty }}"></td><td colspan="3" style="{{ $sty }}"></td></tr>

        <tr><td colspan="16" style="{{ $styNoB }} height: 15px;"></td></tr>

        {{-- ================= TABEL 6: MUTU ================= --}}
        <tr>
            <td colspan="2" style="{{ $styHead }}">VI.</td>
            <td style="{{ $styHead }}">Uraian</td> 
            <td style="{{ $styHead }}">Kg</td> 
            <td style="{{ $styHead }}">Pallet</td> 
            <td colspan="11" style="{{ $styHead }}">Keterangan</td> 
        </tr>
        @foreach($dataMutu as $idx => $r)
        <tr>
            <td style="{{ $sty }}"></td>
            <td style="{{ $sty }} text-align: center;">6.{{ $idx+1 }}</td>
            <td style="{{ $sty }}">{{ $r[0] ?? '-' }}</td>
            <td style="{{ $styNum }}">{{ formatAngka($r[1] ?? 0) }}</td>
            <td style="{{ $styNumCenter }}">{{ formatAngka($r[2] ?? 0) }}</td>
            <td colspan="11" style="{{ $sty }} text-align: center;">-</td>
        </tr>
        @endforeach
        
        <tr>
            <td colspan="2" style="{{ $sty }}"></td>
            <td style="{{ $sty }} text-align: center; font-weight: bold;">JUMLAH 6.1 - 6.{{ count($dataMutu) }}</td>
            <td style="{{ $styNum }} font-weight: bold;">{{ formatAngka(collect($dataMutu)->sum(function($r){ return $r[1] ?? 0; })) }}</td>
            <td style="{{ $styNumCenter }} font-weight: bold;">{{ formatAngka(collect($dataMutu)->sum(function($r){ return $r[2] ?? 0; })) }}</td>
            <td colspan="11" style="{{ $sty }} text-align: center;">-</td>
        </tr>

        {{-- ================= TANDA TANGAN ================= --}}
        <tr><td colspan="16" style="{{ $styNoB }} height: 15px;"></td></tr>

        <tr>
            <td colspan="16" style="{{ $styNoB }} text-align: center; font-weight: bold;">PT. NUSANTARA BATULICIN</td>
        </tr>

        @for($i=0; $i<9; $i++)
            <tr><td colspan="16" style="{{ $styNoB }} height: 15px;"></td></tr>
        @endfor

        <tr>
            <td colspan="5" style="{{ $styNoB }} text-align: center; font-weight: bold;">
                <span style="text-decoration: underline;">Sri Winarno</span>
            </td>
            <td colspan="6" style="{{ $styNoB }}"></td>
            <td colspan="5" style="{{ $styNoB }} text-align: center; font-weight: bold;">
                <span style="text-decoration: underline;">Sri Winarno</span>
            </td>
        </tr>
        
        <tr>
            <td colspan="5" style="{{ $styNoB }} text-align: center; font-weight: bold;">
                Kadiv Pengolahan
            </td>
            <td colspan="6" style="{{ $styNoB }}"></td>
            <td colspan="5" style="{{ $styNoB }} text-align: center; font-weight: bold;">
                Manager
            </td>
        </tr>
    </tbody>
</table>

</body>
</html>