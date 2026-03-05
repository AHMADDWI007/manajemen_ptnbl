<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Produksi Harian - {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}</title>
    <style>
        .main-table td,
        .main-table th {
            padding: 0px 3px !important;
            font-size: 10px !important;
            line-height: 1 !important;
        }
        .main-table tr {
            height: 14px !important;
        }
        @page {
            size: 215mm 330mm; 
            margin: 1cm; 
        }
        body { font-family: "Arial", sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        
        .container { 
            width: 100%; 
            max-width: 195mm; 
            margin: 0 auto; 
        }

        .main-table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
            margin-top: 5px;
        }
        
        .header-table { width: 100%; margin-bottom: 20px; }
        .box-iso { border: 1px solid #000; border-collapse: collapse; font-size: 10px; width: 220px; float: right; margin-top: -30px;}
        .box-iso td { border: 1px solid #000; padding: 2px 4px; }
        
        .main-table th, .main-table td { 
            border: 1px solid #000; 
            padding: 2px 4px; 
            vertical-align: middle; 
            word-wrap: break-word; 
        }
        .main-table th { text-align: center; font-weight: normal; }
        
        .no-border { border: none !important; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        /* Area Tanda Tangan */
        .ttd-tengah-sela {
            position: relative;
            left: 80%;
            transform: translateX(-50%);
            width: 100%;
        }
        .ttd-geser-tengah-dari-kanan {
            position: relative;
            left: -30%;
        }

        @media print {
            .btn-print { display: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

@php
    function fmt($num, $dec = 0) { return ($num > 0) ? number_format($num, $dec, ',', '.') : '-'; }
    function fmtB($num, $dec = 0) { 
        // Jika num tidak ada, null, atau <= 0, kembalikan kosong
        if (!$num || $num <= 0) {
            return ''; 
        }
        return number_format($num, $dec, ',', '.'); 
    }

    function fmtJam($waktu) { return $waktu ? date('H:i', strtotime($waktu)) : ''; }

    $s1 = isset($dataProduksi[1]) ? $dataProduksi[1] : null;
    $s2 = isset($dataProduksi[2]) ? $dataProduksi[2] : null;
    $s3 = isset($dataProduksi[3]) ? $dataProduksi[3] : null;

    // Persiapkan Array List Remahan (Kiri [0] dan Kanan [1])
    $ruang = [1 => ['', ''], 2 => ['', ''], 3 => ['', '']]; 
    $berat = [1 => ['', ''], 2 => ['', ''], 3 => ['', '']]; 
    $umur  = [1 => ['', ''], 2 => ['', ''], 3 => ['', '']];

    foreach ([1 => $s1, 2 => $s2, 3 => $s3] as $shiftNum => $dataShift) {
        if ($dataShift && $dataShift->remahan) {
            $tmpRuang = [];
            $tmpBerat = [];
            $tmpUmur  = [];
            foreach ($dataShift->remahan as $r) {
                // Ekstrak angkanya saja
                preg_match('/\d+/', $r->ruang_maturasi, $matches);
                $nomorBak = !empty($matches[0]) ? $matches[0] : str_replace(['Di Bak Maturasi-', 'Bak Maturasi-'], '', $r->ruang_maturasi);
                
                $tmpRuang[] = trim($nomorBak);
                $tmpBerat[] = fmt($r->berat);
                $tmpUmur[]  = $r->umur;
            }
            
            // Maksimal 2 angka di kolom kiri, sisanya ke kanan
            $batasKiri = 2; 
            
            $ruang[$shiftNum][0] = implode(', ', array_slice($tmpRuang, 0, $batasKiri));
            $ruang[$shiftNum][1] = implode(', ', array_slice($tmpRuang, $batasKiri));
            
            $berat[$shiftNum][0] = implode(', ', array_slice($tmpBerat, 0, $batasKiri));
            $berat[$shiftNum][1] = implode(', ', array_slice($tmpBerat, $batasKiri));
            
            $umur[$shiftNum][0]  = implode(', ', array_slice($tmpUmur, 0, $batasKiri));
            $umur[$shiftNum][1]  = implode(', ', array_slice($tmpUmur, $batasKiri));
        }
    }

    // 🔥 LOGIKA NOMOR PALLET BERSAMBUNG 🔥
    $nomor_start = '..........';
    if ($s1 && $s1->nomor_start) {
        $nomor_start = $s1->nomor_start;
    } elseif ($s2 && $s2->nomor_start) {
        $nomor_start = $s2->nomor_start;
    } elseif ($s3 && $s3->nomor_start) {
        $nomor_start = $s3->nomor_start;
    }

    $end_s1 = ($s1 && $s1->nomor_end) ? $s1->nomor_end : '..........';
    $end_s2 = ($s2 && $s2->nomor_end) ? $s2->nomor_end : '..........';
    $end_s3 = ($s3 && $s3->nomor_end) ? $s3->nomor_end : '..........';
@endphp

<div class="container">
    <button onclick="window.print()" class="btn-print" style="margin-bottom: 20px; padding: 10px 20px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 5px;">Print / Simpan PDF</button>

    <table style="width: 100%; border: none; margin-bottom: 10px;">
        <tr>
            <td style="width: 30%; border: none; vertical-align: top;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <img src="{{ asset('gambar/nb_icon.png') }}" style="width: 45px; height: auto;" alt="Logo NBL">
                    <div style="font-size: 14px; margin-top: 5px; white-space: nowrap;">
                        <strong>PT NUSANTARA BATULICIN</strong>
                    </div>
                </div>
            </td>
            <td style="width: 40%; border: none; text-align: center; vertical-align: top;">
                <div style="font-size: 18px; margin-top: 60px; white-space: nowrap;">
                    <strong>LAPORAN PRODUKSI HARIAN</strong>
                </div>
            </td>
            <td style="width: 30%; border: none; vertical-align: top;">
                <div style="border: 1px solid #000; padding: 4px 8px; font-size: 10px; width: 170px; float: right;">
                    <table style="width: 100%; border-collapse: collapse; border: none;">
                        <tr><td class="no-border" style="padding: 2px 0; width: 45%;">No. Dok</td><td class="no-border" style="padding: 2px 0;">: F-PROD-06</td></tr>
                        <tr><td class="no-border" style="padding: 2px 0;">Revisi</td><td class="no-border" style="padding: 2px 0;">: 0A</td></tr>
                        <tr><td class="no-border" style="padding: 2px 0;">Tgl. Berlaku</td><td class="no-border" style="padding: 2px 0;">: 27 April 2017</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: right; font-weight: bold; margin-bottom: 5px; margin-right: 20px;">
        Tanggal : &nbsp;&nbsp;&nbsp; {{ \Carbon\Carbon::parse($tanggal)->format('d - m - Y') }}
    </div>

    <table class="main-table">
        <colgroup>
            <col style="width: 26%;"> 
            <col style="width: 12.33%;"> 
            <col style="width: 12.33%;"> 
            <col style="width: 12.33%;"> 
            <col style="width: 12.33%;"> 
            <col style="width: 12.33%;"> 
            <col style="width: 12.33%;"> 
        </colgroup>
        <tr>
            <td colspan="7" class="no-border" style="font-weight: bold; padding-top: 5px; padding-bottom: 5px;">
                1. REMAHAN YANG DIPROSES
            </td>
        </tr>
        <tr>
            <td class="no-border" style="padding-left: 40px !important;">Dari :</td>
            <td colspan="2" class="no-border text-center">SHIFT 1</td>
            <td colspan="2" class="no-border text-center">SHIFT 2</td>
            <td colspan="2" class="no-border text-center">SHIFT 3</td>
        </tr>
        <tr>
            <td class="no-border" style="padding-left: 40px !important;">
                <span style="display: inline-block; width: 20px;">1.</span> Ruang Maturasi
            </td>
            <td class="text-center" style="border: 1px solid #000;">{{ $ruang[1][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $ruang[1][1] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $ruang[2][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $ruang[2][1] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $ruang[3][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $ruang[3][1] ?? '' }}</td>
        </tr>
        <tr>
            <td class="no-border" style="padding-left: 40px !important;">
                <span style="display: inline-block; width: 20px;">2.</span> Berat Remahan ( Kg )
            </td>
            <td class="text-center" style="border: 1px solid #000;">{{ $berat[1][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $berat[1][1] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $berat[2][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $berat[2][1] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $berat[3][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $berat[3][1] ?? '' }}</td>
        </tr>
        <tr>
            <td class="no-border" style="padding-left: 40px !important;">
                <span style="display: inline-block; width: 20px;">3.</span> Tanggal Masuk
            </td>
            <td class="text-center" style="border: 1px solid #000;"></td>
            <td class="text-center" style="border: 1px solid #000;"></td>
            <td class="text-center" style="border: 1px solid #000;"></td>
            <td class="text-center" style="border: 1px solid #000;"></td>
            <td class="text-center" style="border: 1px solid #000;"></td>
            <td class="text-center" style="border: 1px solid #000;"></td>
        </tr>
        <tr>
            <td class="no-border" style="padding-left: 40px !important;">
                <span style="display: inline-block; width: 20px;">4.</span> Umur (Hari)
            </td>
            <td class="text-center" style="border: 1px solid #000;">{{ $umur[1][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $umur[1][1] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $umur[2][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $umur[2][1] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $umur[3][0] ?? '' }}</td>
            <td class="text-center" style="border: 1px solid #000;">{{ $umur[3][1] ?? '' }}</td>
        </tr>
    </table>

    <table class="main-table">
        <colgroup>
            <col style="width: 26%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
        </colgroup>
        <tr>
            <td colspan="7" class="no-border" style="font-weight: bold; padding-top: 5px; padding-bottom: 2px;">
                2. PENGERINGAN
            </td>
        </tr>
        <tr>
            <td colspan="2" class="no-border"></td> 
            <td class="text-center">Shift 1</td> 
            <td class="text-center">Shift 2</td> 
            <td class="text-center">Shift 3</td> 
            <td class="text-center" style="font-size: 9px;">Jumlah rata-rata</td> 
            <td class="no-border"></td> 
        </tr>
       <tr>
            <td colspan="2" class="no-border" style="padding-left: 15px !important;">
                <span style="display: inline-block; width: 25px;">A.</span> JAM START DRYER
            </td>
            <td class="text-center">{{ $s1 ? fmtJam($s1->jam_start_dryer) : '' }}</td>
            <td class="text-center">{{ $s2 ? fmtJam($s2->jam_start_dryer) : '' }}</td>
            <td class="text-center">{{ $s3 ? fmtJam($s3->jam_start_dryer) : '' }}</td>
            <td class="text-center"></td>
            <td class="no-border"></td>
        </tr>
        <tr>
            <td colspan="2" class="no-border" style="padding-left: 15px !important;">
                <span style="display: inline-block; width: 25px;">B.</span> JUMLAH TROLLY DIISI/MASUK
            </td>
            <td class="text-center">{{ $s1 ? fmtB($s1->jumlah_trolly_masuk) : '' }}</td>
            <td class="text-center">{{ $s2 ? fmtB($s2->jumlah_trolly_masuk) : '' }}</td>
            <td class="text-center">{{ $s3 ? fmtB($s3->jumlah_trolly_masuk) : '' }}</td>
            <td class="text-center"></td>
            <td class="no-border" style="padding-left: 5px !important;">Trolly</td>
        </tr>
        <tr>
            <td colspan="2" class="no-border" style="padding-left: 15px !important;">
                <span style="display: inline-block; width: 25px;">C.</span> AKTUAL TEMPERATUR
            </td>
            </td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="no-border" style="padding-left: 5px !important;"></td>
        </tr>
        <tr>
            <td colspan="2" class="no-border text-right" style="padding-right: 20px !important;">BURNER 1</td>
            <td class="text-center">{{ $s1 ? ($temps[1]['b1_start']??'').' - '.($temps[1]['b1_end']??'') : '' }}</td>
            <td class="text-center">{{ $s2 ? ($temps[2]['b1_start']??'').' - '.($temps[2]['b1_end']??'') : '' }}</td>
            <td class="text-center">{{ $s3 ? ($temps[3]['b1_start']??'').' - '.($temps[3]['b1_end']??'') : '' }}</td>
            <td class="text-center"></td>
            <td class="no-border" style="padding-left: 5px !important;">°C</td>
        </tr>
        <tr>
            <td colspan="2" class="no-border text-right" style="padding-right: 20px !important;">BURNER 2</td>
            <td class="text-center">{{ $s1 ? ($temps[1]['b2_start']??'').' - '.($temps[1]['b2_end']??'') : '' }}</td>
            <td class="text-center">{{ $s2 ? ($temps[2]['b2_start']??'').' - '.($temps[2]['b2_end']??'') : '' }}</td>
            <td class="text-center">{{ $s3 ? ($temps[3]['b2_start']??'').' - '.($temps[3]['b2_end']??'') : '' }}</td>
            <td class="text-center"></td>
            <td class="no-border" style="padding-left: 5px !important;">°C</td>
        </tr>

        @php
            $rows = [
                ['D.', 'WAKTU SETIAP CYCLE', $s1 ? ($temps[1]['cycle_start']??'').' - '.($temps[1]['cycle_end']??'') : '', $s2 ? ($temps[2]['cycle_start']??'').' - '.($temps[2]['cycle_end']??'') : '', $s3 ? ($temps[3]['cycle_start']??'').' - '.($temps[3]['cycle_end']??'') : '', 'Minute'],
                ['E.(1)', 'BAHAN BAKAR : SOLAR', $s1 ? fmtB($bbs[1]['solar']??0) : '', $s2 ? fmtB($bbs[2]['solar']??0) : '', $s3 ? fmtB($bbs[3]['solar']??0) : '', 'Liter'],
                ['E.(2)', 'BAHAN BAKAR : BATUBARA', $s1 ? fmtB($bbs[1]['batubara']??0) : '', $s2 ? fmtB($bbs[2]['batubara']??0) : '', $s3 ? fmtB($bbs[3]['batubara']??0) : '', 'Kg'],
                ['E.(3)', 'BAHAN BAKAR : CANGKANG', $s1 ? fmtB($bbs[1]['cangkang']??0) : '', $s2 ? fmtB($bbs[2]['cangkang']??0) : '', $s3 ? fmtB($bbs[3]['cangkang']??0) : '', 'Kg'],
                ['F.', 'JUMLAH TROLLY KELUAR', 
                    $s1 ? fmtB($s1->jumlah_trolly_keluar) : '', 
                    $s2 ? fmtB($s2->jumlah_trolly_keluar) : '', 
                    $s3 ? fmtB($s3->jumlah_trolly_keluar) : '', 
                    'Trolly'],
                 ['G.', 'JAM STOP DRYER', $s1 ? fmtJam($s1->jam_stop_dryer) : '', $s2 ? fmtJam($s2->jam_stop_dryer) : '', $s3 ? fmtJam($s3->jam_stop_dryer) : '', ''],
                ['H.', 'JUMLAH JAM JALAN DRYER (G-A)', $s1 ? $s1->jumlah_jam_dryer : '', $s2 ? $s2->jumlah_jam_dryer : '', $s3 ? $s3->jumlah_jam_dryer : '', 'Jam'],
                ['I.', 'JUMLAH BALES YANG DI PRESS', $s1 ? fmtB($s1->jumlah_bales_dipress) : '', $s2 ? fmtB($s2->jumlah_bales_dipress) : '', $s3 ? fmtB($s3->jumlah_bales_dipress) : '', 'Bales'],
                ['J.', 'KG YANG DI PRESS (I x 35 Kg)', $s1 ? fmtB($s1->kg_yang_dipress) : '', $s2 ? fmtB($s2->kg_yang_dipress) : '', $s3 ? fmtB($s3->kg_yang_dipress) : '', 'Kg'],
                ['K.', 'CAPACITY PER JAM (J:H)', $s1 ? fmtB($s1->capacity_per_jam) : '', $s2 ? fmtB($s2->capacity_per_jam) : '', $s3 ? fmtB($s3->capacity_per_jam) : '', 'Kg/Jam'],
                ['L.', 'JAM KERJA', $s1 ? $s1->jam_kerja : '', $s2 ? $s2->jam_kerja : '', $s3 ? $s3->jam_kerja : '', 'Jam'],
                ['M.', 'PRODUKSTIVITAS (Kg : Jam Kerja)', $s1 ? fmtB($s1->produktivitas) : '', $s2 ? fmtB($s2->produktivitas) : '', $s3 ? fmtB($s3->produktivitas) : '', 'Kg'],
                ['N.', 'KG SIR 20 / Cake', 
                    $s1 ? fmtB($s1->kg_cake, 2) : '', 
                    $s2 ? fmtB($s2->kg_cake, 2) : '', 
                    $s3 ? fmtB($s3->kg_cake, 2) : '', 
                    'Kg'],
                ['O.', 'HASIL BALES ADA KONTAMINASI', $s1 ? fmtB($s1->bales_terkontaminasi) : '', $s2 ? fmtB($s2->bales_terkontaminasi) : '', $s3 ? fmtB($s3->bales_terkontaminasi) : '', 'Bales'],
                ['P.', 'BERAT KONTAMINAN', $s1 ? $s1->berat_kontaminan : '', $s2 ? $s2->berat_kontaminan : '', $s3 ? $s3->berat_kontaminan : '', 'Gram'],
                ['Q.', 'JAM OPERASIONAL GENSET', 
                    $s1 ? fmtB($s1->jam_operasional_genset, 2) : '', 
                    $s2 ? fmtB($s2->jam_operasional_genset, 2) : '', 
                    $s3 ? fmtB($s3->jam_operasional_genset, 2) : '', 
                    'Jam'],
                ['R.(1)', 'SOLAR / TON', 
                    ($s1 && $s1->kg_yang_dipress > 0) ? fmtB((float)($bbs[1]['solar']??0) / ($s1->kg_yang_dipress / 1000), 2) : '',
                    ($s2 && $s2->kg_yang_dipress > 0) ? fmtB((float)($bbs[2]['solar']??0) / ($s2->kg_yang_dipress / 1000), 2) : '',
                    ($s3 && $s3->kg_yang_dipress > 0) ? fmtB((float)($bbs[3]['solar']??0) / ($s3->kg_yang_dipress / 1000), 2) : '', 
                    'Liter'],
                ['R.(2)', 'BATUBARA / TON', 
                    ($s1 && $s1->kg_yang_dipress > 0) ? fmtB((float)($bbs[1]['batubara']??0) / ($s1->kg_yang_dipress / 1000), 2) : '',
                    ($s2 && $s2->kg_yang_dipress > 0) ? fmtB((float)($bbs[2]['batubara']??0) / ($s2->kg_yang_dipress / 1000), 2) : '',
                    ($s3 && $s3->kg_yang_dipress > 0) ? fmtB((float)($bbs[3]['batubara']??0) / ($s3->kg_yang_dipress / 1000), 2) : '', 
                    'Kg'],
                ['R.(3)', 'CANGKANG / TON', 
                    ($s1 && $s1->kg_yang_dipress > 0) ? fmtB((float)($bbs[1]['cangkang']??0) / ($s1->kg_yang_dipress / 1000), 2) : '', 
                    ($s2 && $s2->kg_yang_dipress > 0) ? fmtB((float)($bbs[2]['cangkang']??0) / ($s2->kg_yang_dipress / 1000), 2) : '', 
                    ($s3 && $s3->kg_yang_dipress > 0) ? fmtB((float)($bbs[3]['cangkang']??0) / ($s3->kg_yang_dipress / 1000), 2) : '', 
                    'Kg'],
            ];
        @endphp

        @foreach($rows as $row)
        <tr>
            <td colspan="2" class="no-border" style="padding-left: 15px !important;">
                <span style="display: inline-block; width: 25px;">{{ $row[0] }}</span> {{ $row[1] }}
            </td>
            <td class="text-center">{{ $row[2] }}</td>
            <td class="text-center">{{ $row[3] }}</td>
            <td class="text-center">{{ $row[4] }}</td>
            <td class="text-center"></td>
            <td class="no-border" style="padding-left: 5px !important;">{{ $row[5] }}</td>
        </tr>
        @endforeach
    </table>

    <table class="main-table">
        <colgroup>
            <col style="width: 26%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
            <col style="width: 12.33%;">
        </colgroup>
        <tr>
            <td colspan="7" class="no-border" style="font-weight: bold; padding-top: 10px; padding-bottom: 5px;">
                3. PACKING ROOM
            </td>
        </tr>
        <tr>
            <td colspan="2" class="no-border" style="padding-left: 15px !important;">
                <span style="display: inline-block; width: 25px;">A.</span> JUMLAH PALLET DIISI
            </td>
            <td class="text-center">{{ $s1 ? fmtB($s1->jumlah_pallet) : '' }}</td>
            <td class="text-center">{{ $s2 ? fmtB($s2->jumlah_pallet) : '' }}</td>
            <td class="text-center">{{ $s3 ? fmtB($s3->jumlah_pallet) : '' }}</td>
            <td class="text-center"></td>
            <td class="no-border" style="padding-left: 5px !important;">SW</td>
        </tr>
        <tr>
            <td colspan="2" class="no-border" style="vertical-align: top; padding-left: 45px !important; padding-top: 5px !important;">
                TOTAL NO
            </td>
            <td class="no-border"></td> 
            <td class="text-center no-border" style="font-size: 10px; padding-top: 3px !important;">SW</td> 
            <td colspan="3" class="no-border"></td>
        </tr>
        <tr>
            <td colspan="2" class="no-border"></td>
            <td colspan="5" class="no-border" style="padding-top: 5px !important;">
                : <span style="letter-spacing: 2px;">.....................................</span> WP
            </td>
        </tr>
        <tr>
            <td colspan="2" class="no-border"></td>
            <td colspan="5" class="no-border" style="padding-top: 3px !important;">
                : <span style="letter-spacing: 2px;">.....................................</span> MC
            </td>
        </tr>
        <tr>
            <td class="no-border" style="padding-top: 10px !important; padding-left: 15px !important;">
                <span style="display: inline-block; width: 30px;">B.</span> NOMOR
            </td>
            <td class="no-border text-right" style="padding-top: 10px !important; padding-right: 5px !important;">
                SW
            </td>
            <td colspan="5" class="no-border" style="padding-top: 10px !important;">
                : &nbsp; <span style="font-weight: bold;">{{ $nomor_start }}</span> 
                &nbsp;s/d&nbsp; <span style="font-weight: bold;">{{ $end_s1 }}</span> 
                &nbsp;&nbsp;&nbsp; s/d &nbsp;&nbsp; <span style="font-weight: bold;">{{ $end_s2 }}</span> 
                &nbsp;&nbsp;&nbsp; s/d &nbsp;&nbsp; <span style="font-weight: bold;">{{ $end_s3 }}</span>
            </td>
        </tr>
    </table>
        
    <table style="width: 100%; text-align: center; border: none; margin-top: 15px; table-layout: fixed;">
        <tr>
            @foreach([1, 2, 3] as $sh)
            <td style="width: 33.33%; vertical-align: top; border: none; padding: 0 10px;">
                <div style="margin-bottom: 60px;">Petugas Shift {{ $sh }},</div> 
                <div style="border-top: 1px dotted #000; border-bottom: 1px solid #000; width: 70%; height: 2px; margin: 0 auto;"></div>
                <div style="margin-top: 10px; margin-bottom: 60px;">Spv. Pengolahan</div>
                <div style="border-top: 1px dotted #000; border-bottom: 1px solid #000; width: 70%; height: 2px; margin: 0 auto;"></div>
            </td>
            @endforeach
        </tr>
    </table>

    <table style="width: 100%; text-align: center; border: none; margin-top: 15px;">
        <tr>
            <td style="width: 33.33%; vertical-align: top; border: none;">
                <div class="ttd-tengah-sela">
                    <span>Diperiksa Oleh,</span><br><span>Kadiv Pengolahan</span>
                    <div style="height: 60px;"></div>
                    <span style="font-weight: bold; text-decoration: underline;">Sri Winarno</span>
                </div>
            </td>
            <td style="width: 33.33%; border: none;"></td>
            <td style="width: 33.33%; vertical-align: top; border: none;">
                <div class="ttd-geser-tengah-dari-kanan">
                    <span>Diketahui Oleh,</span><br><span>Manager</span>
                    <div style="height: 60px;"></div>
                    <span style="font-weight: bold; text-decoration: underline;">Sri Winarno</span>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 4px;">
        Catatan:<br>
        @php
            $listCatatan = [];
            // Tarik data catatan tiap shift
            foreach([1, 2, 3] as $shift) {
                if(isset($dataProduksi[$shift]) && !empty(trim($dataProduksi[$shift]->keterangan)) && trim($dataProduksi[$shift]->keterangan) !== '-') {
                    $listCatatan[] = "Shift {$shift}: " . trim($dataProduksi[$shift]->keterangan);
                }
            }
            // Pastikan minimal selalu ada 6 baris (sesuai format asli Maswi)
            $totalBaris = max(6, count($listCatatan));
        @endphp

        @for($i=0; $i<$totalBaris; $i++) 
            <div style="border-bottom: 1px solid #000; height: 15px; width: 100%; box-sizing: border-box; padding-top: 1px; padding-left: 5px; font-size: 10px;">
                {{ isset($listCatatan[$i]) ? $listCatatan[$i] : '' }}
            </div> 
        @endfor
    </div>
</div>
<script>
        window.onload = function() {
            // Otomatis memunculkan popup print saat halaman selesai dimuat
            window.print();
        };

        // (Opsional) Otomatis menutup tab setelah selesai nge-print atau jika user menekan 'Cancel'
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>