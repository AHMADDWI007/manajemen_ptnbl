<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pengolahan Maturasi</title>
    <style>
        /* Pengaturan Halaman PDF */
        @page {
            margin: 1cm 1cm 1cm 1cm; /* Margin Kiri/Kanan/Atas/Bawah diperkecil */
        }

        body { 
            font-family: "Times New Roman", Times, serif; 
            font-size: 7pt; /* Font diperkecil agar muat Portrait */
            line-height: 1.2;
        }
        
        /* Header */
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 12pt; text-transform: uppercase; }
        .header h4 { margin: 2px 0; font-size: 10pt; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 8pt; }
        
        /* Tabel */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; /* Penting agar lebar kolom terkunci */
        }
        
        table, th, td { border: 0.5px solid black; }
        
        th, td { 
            padding: 2px 3px; /* Padding diperkecil */
            text-align: center; 
            vertical-align: middle; 
            overflow-wrap: break-word; /* Agar teks panjang turun ke bawah */
        }
        
        th { 
            background-color: #f0f0f0; 
            font-weight: bold; 
            text-transform: uppercase;
            font-size: 7pt;
        }
        
        /* Helper Classes */
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .bg-green { background-color: #92D050; }
        .bold { font-weight: bold; }
        
        /* --- PENGATURAN LEBAR KOLOM (Total 100%) --- */
        /* Total ada 14 Kolom Data */
        
        /* 1. No (Sangat Kecil) */
        .col-no { width: 3%; }
        
        /* 2. Uraian (Besar) */
        .col-uraian { width: 16%; }
        
        /* 3-5. Stock Awal (Kg, Tgl, Umur) */
        .col-sa-kg { width: 8%; }
        .col-sa-tgl { width: 8%; }
        .col-sa-umur { width: 4%; }
        
        /* 6-7. Diproses (Diolah, Mutasi) */
        .col-proc-diolah { width: 8%; }
        .col-proc-mutasi { width: 6%; }
        
        /* 8. Masuk (HI) */
        .col-masuk { width: 8%; }
        
        /* 9-11. Quality (K3, Po, PRI) - Kecil */
        .col-q-k3 { width: 5%; }
        .col-q-po { width: 4%; }
        .col-q-pri { width: 4%; }
        
        /* 12. Stock Akhir */
        .col-akhir { width: 8%; }
        
        /* 13. Asal Bokar */
        .col-asal { width: 8%; }
        
        /* 14. Keterangan (Sisa) */
        .col-ket { width: 10%; }

    </style>
</head>
<body>

    <div class="header">
        <h2>PT. NUSANTARA BATULICIN</h2>
        <h2>LAPORAN HARIAN PENGOLAHAN MATURASI</h2>
        <p>Periode: {{ $formatted_date }}</p>
    </div>

    <table>
        <thead>
            {{-- Header Baris 1 --}}
            <tr>
                <th rowspan="2" class="col-no">NO</th>
                <th rowspan="2" class="col-uraian">URAIAN PROSES</th>
                <th colspan="3">Stock Awal</th>
                <th colspan="2">Diproses HI</th>
                <th colspan="1">Masuk</th>
                <th colspan="3">Quality</th>
                <th rowspan="2" class="col-akhir">Stock Akhir</th>
                <th rowspan="2" class="col-asal">Asal Bokar</th>
                <th rowspan="2" class="col-ket">Keterangan</th>
            </tr>
            {{-- Header Baris 2 --}}
            <tr>
                {{-- Stock Awal --}}
                <th class="col-sa-kg">Kg KK</th>
                <th class="col-sa-tgl">Tgl</th>
                <th class="col-sa-umur">Umur</th>
                
                {{-- Diproses --}}
                <th class="col-proc-diolah">Diolah</th>
                <th class="col-proc-mutasi">Mutasi</th>
                
                {{-- Masuk --}}
                <th class="col-masuk">HI</th>
                
                {{-- Quality --}}
                <th class="col-q-k3">K3</th>
                <th class="col-q-po">Po</th>
                <th class="col-q-pri">PRI</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data_maturasi as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item->uraian }}</td>
                    <td class="text-center">{{ number_format($item->stok_awal, 0, ',', '.') }}</td>
                    <td>
                        @if($item->tgl_masuk)
                            {{ \Carbon\Carbon::parse($item->tgl_masuk)->format('d-m-Y') }}
                        @else - @endif
                    </td>
                    <td>{{ $item->umur ?? 0 }}</td>
                    <td class="text-center">{{ number_format($item->diolah, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($item->mutasi, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($item->masuk_hi, 0, ',', '.') }}</td>
                    
                    {{-- Quality --}}
                    <td>{{ number_format(($item->k3_olah > 0 ? $item->k3_olah : $item->k3_masuk) ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $item->po ?? '-' }}</td>
                    <td>{{ $item->pri ?? '-' }}</td>
                    
                    <td class="text-center bold">{{ number_format($item->stok_akhir, 0, ',', '.') }}</td>
                    <td>{{ $item->asal_bokar ?? '-' }}</td>
                    
                    {{-- Keterangan (rata kiri agar muat banyak) --}}
                    <td class="text-center" style="font-size: 6pt;">{{ $item->keterangan ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        
        {{-- FOOTER --}}
        <tfoot class="bold">
            {{-- Baris Jumlah Total --}}
            <tr>
                <td colspan="2" class="text-center">Jumlah</td>
                <td class="text-center">{{ number_format($footer_data['total_stok_awal'], 0, ',', '.') }}</td>
                <td></td> <td></td>
                <td class="text-center">{{ number_format($footer_data['total_diolah'], 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($footer_data['total_mutasi'], 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($footer_data['total_masuk_hi'], 0, ',', '.') }}</td>
                <td></td> <td></td> <td></td>
                <td class="text-center">{{ number_format($footer_data['total_stok_akhir'], 0, ',', '.') }}</td>
                <td></td> <td></td>
            </tr>

            {{-- Baris Maturasi Diolah --}}
            <tr>
                <td colspan="3" class="text-center">Maturasi Diolah</td>
                <td colspan="2" class="text-center">s/d Kemarin</td>
                <td colspan="2" class="bg-green text-center">{{ number_format($footer_data['maturasi_diolah_sd_kemarin'], 0, ',', '.') }}</td>
                <td colspan="1" class="text-center">Hari ini</td>
                <td colspan="2" class="text-center">{{ number_format($footer_data['maturasi_diolah_hari_ini'], 0, ',', '.') }}</td>
                <td colspan="2" class="text-center">s/d Hari ini</td>
                <td colspan="2" class="text-right">{{ number_format($footer_data['maturasi_diolah_sd_hari_ini'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="font-size: 8px; margin-top: 10px; text-align: left;">
        <i>Dicetak pada: {{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</i>
    </div>

</body>
</html>