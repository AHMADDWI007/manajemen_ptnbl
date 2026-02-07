<!DOCTYPE html>
<html lang="en">
<head>
    {{-- 🔥 META TAG INI WAJIB ADA AGAR EXCEL TIDAK BINGUNG 🔥 --}}
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* Paksa semua teks jadi hitam agar terbaca */
        body { font-family: sans-serif; color: #000000; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000000; padding: 3px; vertical-align: middle; }
    </style>
</head>
<body>

@php
    if (!function_exists('ribuan')) {
        function ribuan($nilai) {
            return ($nilai > 0) ? number_format($nilai, 0, ',', '.') : '-';
        }
    }
    
    // Hitung Grand Total Saldo Akhir di sini biar view bersih
    $grandTotalSaldoAkhir = collect($rekapBokar)->sum(function($i){ return ($i['stok_awal']+$i['masuk_hi'])-$i['kering_hi']+$i['rektif']; }) 
                          + collect($dataMaturasi)->sum('stok_akhir')
                          + collect($dataWip)->sum('stok_akhir')
                          + collect($dataGudang)->sum('stok_akhir');
@endphp

<table>
    {{-- HEADER --}}
    <thead>
        <tr>
            <th colspan="14" style="font-weight: bold; font-size: 14px; text-align: center; height: 40px; vertical-align: middle;">
                REKAPITULASI LAPORAN HARIAN<br/>
                PENERIMAAN BOKAR, PROSES PENGOLAHAN &amp; PRODUKSI SIR-20
            </th>
        </tr>
        <tr>
            <td colspan="14" style="border: none;">
                <table>
                    <tr>
                        <td colspan="2" style="font-weight: bold; border: none;">PKR</td>
                        <td style="text-align: center; font-weight: bold; border: none;">:</td>
                        <td colspan="11" style="font-weight: bold; border: none;">KARANG BINTANG PT.NBL</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight: bold; border: none;">BULAN</td>
                        <td style="text-align: center; font-weight: bold; border: none;">:</td>
                        <td colspan="11" style="font-weight: bold; border: none;">{{ $tanggal->translatedFormat('F Y') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-weight: bold; border: none;">HARI/TGL</td>
                        <td style="text-align: center; font-weight: bold; border: none;">:</td>
                        <td colspan="11" style="font-weight: bold; border: none;">{{ $tanggal->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </thead>

    {{-- BODY --}}
    <tbody>
        {{-- SPACER --}}
        <tr><td colspan="14" style="height: 10px; border: none;"></td></tr>

        {{-- TABEL 1: BOKAR --}}
        <tr>
            <th rowspan="2" style="background-color: #e0e0e0;">NO.</th>
            <th rowspan="2" style="background-color: #e0e0e0;">URAIAN</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Stock Awal</th>
            <th colspan="3" style="background-color: #e0e0e0;">Penerimaan Bokar (Kg KK)</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Jumlah Stock Bokar</th>
            <th colspan="3" style="background-color: #e0e0e0;">Bokar Diproses (Kg KK)</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Rektif</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Stock Akhir</th>
            <th rowspan="2" colspan="2" style="background-color: #e0e0e0;">Keterangan</th>
        </tr>
        <tr>
            <th style="background-color: #e0e0e0;">S/d kemarin</th>
            <th style="background-color: #e0e0e0;">Masuk HI</th>
            <th style="background-color: #e0e0e0;">S/D HI</th>
            <th style="background-color: #e0e0e0;">Hari Ini</th>
            <th colspan="2" style="background-color: #e0e0e0;">S/d HI</th>
        </tr>
        
        @php $no = 1; $labels = ['DS' => 'Pembelian Bokar Rakyat', 'PT' => 'Pembelian Bokar PTPN', 'INHUT' => 'Pembelian Bokar INHUTANI']; @endphp
        @foreach($labels as $key => $label)
            @php $d = $rekapBokar[$key]; $jmlStock = $d['stok_awal'] + $d['masuk_hi']; $stockAkhir = $jmlStock - $d['kering_hi'] + $d['rektif']; @endphp
            <tr>
                <td style="text-align: center;">I.{{ $no++ }}</td>
                <td>{{ $label }}</td>
                <td style="text-align: right; background-color: #33d033;">{{ ribuan($d['stok_awal']) }}</td>
                <td style="text-align: right; background-color: #33d033;">{{ ribuan($d['basah_sdhi']) }}</td>
                <td style="text-align: right;">{{ ribuan($d['masuk_hi']) }}</td>
                <td style="text-align: right;">{{ ribuan($d['masuk_sdhi']) }}</td>
                <td style="text-align: right;">{{ ribuan($jmlStock) }}</td>
                <td style="text-align: right;">{{ ribuan($d['kering_hi']) }}</td>
                <td colspan="2" style="text-align: right; background-color: #33d033;">{{ ribuan($d['kering_sdhi']) }}</td>
                <td style="text-align: center;">{{ ribuan($d['rektif']) }}</td>
                <td style="text-align: right;">{{ ribuan($stockAkhir) }}</td>
                <td colspan="2" style="text-align: center;">-</td>
            </tr>
        @endforeach
        
        <tr>
            <td colspan="2" style="text-align: center; font-weight: bold;">TOTAL</td>
            <td style="text-align: right;">{{ number_format(collect($rekapBokar)->sum('stok_awal'),0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format(collect($rekapBokar)->sum('basah_sdhi'),0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format(collect($rekapBokar)->sum('masuk_hi'),0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format(collect($rekapBokar)->sum('masuk_sdhi'),0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format(collect($rekapBokar)->sum(function($i){ return $i['stok_awal']+$i['masuk_hi']; }),0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format(collect($rekapBokar)->sum('kering_hi'),0,',','.') }}</td>
            <td colspan="2" style="text-align: right;">{{ number_format(collect($rekapBokar)->sum('kering_sdhi'),0,',','.') }}</td>
            <td style="text-align: center;">{{ number_format(collect($rekapBokar)->sum('rektif'),0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format(collect($rekapBokar)->sum(function($i){ return ($i['stok_awal']+$i['masuk_hi'])-$i['kering_hi']+$i['rektif']; }),0,',','.') }}</td>
            <td colspan="2"></td>
        </tr>

        {{-- SPACER --}}
        <tr><td colspan="14" style="height: 10px; border: none;"></td></tr>

        {{-- TABEL 2: MATURASI --}}
        <tr>
            <th rowspan="2" style="background-color: #e0e0e0;">NO.</th>
            <th rowspan="2" style="background-color: #e0e0e0;">URAIAN PROSES</th>
            <th colspan="3" style="background-color: #e0e0e0;">Stock Awal</th> 
            <th colspan="2" style="background-color: #e0e0e0;">Diproses HI</th>
            <th rowspan="1" style="background-color: #e0e0e0;">Masuk</th>
            <th colspan="3" style="background-color: #e0e0e0;">Quality</th> 
            <th rowspan="2" style="background-color: #e0e0e0;">Stock Akhir</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Asal</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Keterangan</th>
        </tr>
        <tr>
            <th style="background-color: #e0e0e0;">Kg</th>
            <th style="background-color: #e0e0e0;">Tgl</th>
            <th style="background-color: #e0e0e0;">Umur</th>
            <th style="background-color: #e0e0e0;">Diolah</th>
            <th style="background-color: #e0e0e0;">Mutasi</th>
            <th style="background-color: #e0e0e0;">HI</th>
            <th style="background-color: #e0e0e0;">K3</th>
            <th style="background-color: #e0e0e0;">Po</th>
            <th style="background-color: #e0e0e0;">PRI</th>
        </tr>
        @foreach($dataMaturasi as $index => $m)
            @if($index == 7 || $index == 25)
                <tr><td colspan="14" style="text-align: center; font-weight: bold; background-color: #f8f805;">JALAN</td></tr>
            @endif
            <tr>
                <td style="text-align: center;">2.{{ $index+1 }}</td>
                <td>{{ $m->no_bak }}</td>
                <td style="text-align: right;">{{ number_format($m->kering ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: center;">{{ $m->tgl_isi ? \Carbon\Carbon::parse($m->tgl_isi)->format('d-M-y') : 'KOSONG' }}</td>
                <td style="text-align: center;">{{ ($m->umur ?? 0) . ' hari' }}</td>
                <td style="text-align: right;">{{ $m->diolah > 0 ? number_format($m->diolah, 0, ',', '.') : '' }}</td>
                <td style="text-align: right;">{{ $m->mutasi > 0 ? number_format($m->mutasi, 0, ',', '.') : '' }}</td>
                <td style="text-align: right;">{{ $m->masuk_hi > 0 ? number_format($m->masuk_hi, 0, ',', '.') : '' }}</td>
                <td style="text-align: center; background-color: #e0e0e0;">{{ $m->k3_olah > 0 ? number_format($m->k3_olah, 2, ',', '.') : '' }}</td>
                <td style="text-align: center; background-color: #e0e0e0;">{{ ($m->po && $m->po != '-' && $m->po != 0) ? $m->po : '' }}</td>
                <td style="text-align: center; background-color: #e0e0e0;">{{ ($m->pri && $m->pri != '-' && $m->pri != 0) ? $m->pri : '' }}</td>
                <td style="text-align: right;">{{ number_format($m->stok_akhir ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: center;">{{ $m->jenis ?? '-' }}</td>
                <td style="text-align: center; font-size: 7px;">{{ $m->keterangan ?? 'KOSONG' }}</td>
            </tr>
        @endforeach

        {{-- Spacer --}}
        <tr><td colspan="14" style="height: 10px; border: none;"></td></tr>

        {{-- TABEL 3: WIP --}}
        <tr>
            <th rowspan="2" style="background-color: #e0e0e0;">NO.</th> 
            <th rowspan="2" colspan="2" style="background-color: #e0e0e0;">URAIAN</th> 
            <th rowspan="2" style="background-color: #e0e0e0;">Saldo Awal</th> 
            <th colspan="2" style="background-color: #e0e0e0;">WIP</th> 
            <th rowspan="2" style="background-color: #e0e0e0;">Produksi SIR20</th> 
            <th rowspan="2" style="background-color: #e0e0e0;">Rektif</th> 
            <th rowspan="2" colspan="2" style="background-color: #e0e0e0;">Saldo Akhir</th> 
            <th rowspan="2" colspan="4" style="background-color: #e0e0e0;">Keterangan</th> 
        </tr>
        <tr>
            <th style="background-color: #e0e0e0;">Masuk</th> 
            <th style="background-color: #e0e0e0;">Keluar</th> 
        </tr>
        @foreach($dataWip as $index => $w)
        <tr>
            <td style="text-align: center;">3.{{ $index+1 }}</td>
            <td colspan="2">{{ $w->uraian }}</td>
            <td style="text-align: right; background-color: #33d033;">{{ ribuan($w->stok_awal) }}</td>
            <td style="text-align: right;">{{ ribuan($w->masuk) }}</td>
            <td style="text-align: right;">{{ ribuan($w->keluar) }}</td>
            <td style="text-align: right; {{ ($index+1) == 7 ? 'background-color: #33d033;' : '' }}">{{ number_format($w->produksi_sir20,0,',','.') }}</td>
            <td style="text-align: center;">{{ number_format($w->rektif,0,',','.') }}</td>
            <td colspan="2" style="text-align: right;">{{ number_format($w->stok_akhir,0,',','.') }}</td>
            <td colspan="4" style="text-align: center;">{{ $w->keterangan }}</td>
        </tr>
        @endforeach

        {{-- Spacer --}}
        <tr><td colspan="14" style="height: 10px; border: none;"></td></tr>

        {{-- TABEL 4: GUDANG --}}
        <tr>
            <th rowspan="2" style="background-color: #e0e0e0;">NO.</th>
            <th rowspan="2" colspan="2" style="background-color: #e0e0e0;">Stock Dalam Gudang SIR</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Saldo Awal</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Masuk</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Total</th>
            <th colspan="2" style="background-color: #e0e0e0;">Produksi Bulan Ini</th> 
            <th rowspan="2" colspan="3" style="background-color: #e0e0e0;">Pengiriman</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Saldo Akhir</th>
            <th rowspan="2" style="background-color: #e0e0e0;">PTNB</th>
            <th rowspan="2" style="background-color: #e0e0e0;">Total</th>
        </tr>
        <tr>
            <th style="background-color: #e0e0e0;">Yg lalu</th>
            <th style="background-color: #e0e0e0;">s/d HI</th>
        </tr>
        @foreach($dataGudang as $index => $g)
        <tr>
            <td style="text-align: center;">4.{{ $index+1 }}</td>
            <td colspan="2">{{ $g->uraian }}</td>
            <td style="text-align: right; {{ $loop->first ? 'background-color: #33d033;' : '' }}">{{ ribuan($g->stok_awal) }}</td>
            <td style="text-align: right;">{{ ribuan($g->prod_hi) }}</td>
            <td style="text-align: right;">{{ number_format($g->stok_awal + $g->prod_hi,0,',','.') }}</td>
            <td style="text-align: right; {{ $loop->first ? 'background-color: #33d033;' : '' }}">{{ number_format($g->prod_bln_lalu,0,',','.') }}</td> 
            <td style="text-align: right;">{{ number_format($g->prod_sdhi,0,',','.') }}</td>
            <td colspan="3" style="text-align: right;">{{ number_format($g->pengiriman,0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format($g->stok_akhir,0,',','.') }}</td>
            @if($loop->last)
                <td style="text-align: right; font-weight: bold;">{{ number_format($g->stok_akhir,0,',','.') }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($grandTotalSaldoAkhir,0,',','.') }}</td>
            @else
                <td style="text-align: right;">0</td><td style="text-align: right;">0</td>
            @endif
        </tr>
        @endforeach

        {{-- Spacer --}}
        <tr><td colspan="14" style="height: 10px; border: none;"></td></tr>

        {{-- TABEL 5: PENJUALAN --}}
        <tr>
            <th rowspan="2" style="background-color: #e0e0e0;">NO.</th>
            <th rowspan="2" colspan="2" style="background-color: #e0e0e0;">PENJUALAN (SIR-20)</th>
            <th rowspan="2" style="background-color: #e0e0e0;">s/d {{ $tanggal->subMonth()->format('M Y') }}</th>
            <th colspan="2" style="background-color: #e0e0e0;">Penjualan Bulan Ini</th>
            <th rowspan="2" colspan="3" style="background-color: #e0e0e0;">Total Bulan Ini</th>
            <th rowspan="2" colspan="3" style="background-color: #e0e0e0;">Total Penjualan s/d Hari Ini</th>
            <th rowspan="2" colspan="2" style="background-color: #e0e0e0;">Keterangan</th>
        </tr>
        <tr>
            <th style="background-color: #e0e0e0;">Yg Lalu</th>
            <th style="background-color: #e0e0e0;">Hari Ini</th>
        </tr>
        @foreach($dataPenjualan as $index => $p)
        <tr>
            <td style="text-align: center;">5.{{ $index+1 }}</td>
            <td colspan="2">{{ $p->uraian }}</td>
            <td style="text-align: right;">{{ number_format($p->sd_bulan_lalu,0,',','.') }}</td>
            <td style="text-align: right; {{ $loop->first ? 'background-color: #33d033;' : '' }}">{{ number_format($p->bln_ini_lalu,0,',','.') }}</td>
            <td style="text-align: right;">{{ number_format($p->hari_ini,0,',','.') }}</td>
            <td colspan="3" style="text-align: right;">{{ number_format($p->total_bln_ini,0,',','.') }}</td>
            <td colspan="3" style="text-align: right;">{{ number_format($p->total_sd_hari_ini,0,',','.') }}</td>
            <td colspan="2" style="text-align: center;">{{ $p->keterangan }}</td>
        </tr>
        @endforeach

        {{-- Spacer --}}
        <tr><td colspan="14" style="height: 10px; border: none;"></td></tr>

        {{-- TABEL 6: MUTU --}}
        <tr>
            <th style="background-color: #e0e0e0;">NO.</th>
            <th colspan="2" style="background-color: #e0e0e0;">Uraian</th> 
            <th style="background-color: #e0e0e0;">Kg</th> 
            <th style="background-color: #e0e0e0;">Pallet</th> 
            <th colspan="9" style="background-color: #e0e0e0;">Keterangan</th> 
        </tr>
        @foreach($dataMutu as $idx => $r)
        <tr>
            <td style="text-align: center;">6.{{ $idx+1 }}</td>
            <td colspan="2">{{ $r[0] }}</td>
            <td style="text-align: right;">{{ ribuan($r[1]) }}</td>
            <td style="text-align: right;">{{ ribuan($r[2]) }}</td>
            <td colspan="9" style="text-align: center;">-</td>
        </tr>
        @endforeach

        {{-- FOOTER --}}
        <tr><td colspan="14" style="height: 20px; border: none;"></td></tr>
        <tr>
            <td colspan="4" style="text-align: center; height: 100px; border: none;">
                <br/><br/><br/><b><u>Sri Winarno</u></b><br/>Kadiv Pengolahan
            </td>
            <td colspan="4" style="text-align: center; border: none;"><b>PT. NUSANTARA BATULICIN</b></td>
            <td colspan="6" style="text-align: center; border: none;">
                <br/><br/><br/><b><u>Sri Winarno</u></b><br/>Manager
            </td>
        </tr>
    </tbody>
</table>

</body>
</html>