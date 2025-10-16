<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <style>
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <h3 class="m-0 text-success fw-bold">Penjualan SIR 20</h3>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                {{-- TABEL V: TELAH DIJUAL (KG SIR-20) --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">V. Telah Dijual (Kg SIR-20)</h5>
                    </div>
                    <div class="card-body table-responsive">
                        @php
                            $uraian_penjualan = ['SIR20 PT.NBL', 'SIR20 PTPN4'];
                        @endphp
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th colspan="2">V.</th>
                                    <th rowspan="2">Telah Dijual (Kg SIR-20)</th>
                                    <th rowspan="2">s/d {{ \Carbon\Carbon::now()->subMonth()->translatedFormat('F Y') }}</th>
                                    <th colspan="2">Penjualan Bulan Ini</th>
                                    <th rowspan="2">Total Bulan Ini</th>
                                    <th rowspan="2">Total Penjualan s/d Hari Ini</th>
                                    <th rowspan="2">Keterangan</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th>Yg lalu</th>
                                    <th>Hari Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($uraian_penjualan as $index => $uraian)
                                    @php $item = $data_penjualan->get($uraian); @endphp
                                    <tr>
                                        <td></td>
                                        <td class="text-center">5.{{ $index + 1 }}</td>
                                        <td class="text-start">{{ $uraian }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->sd_bulan_lalu, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->penjualan_bulan_ini_yg_lalu, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->penjualan_bulan_ini_hari_ini, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->total_bulan_ini, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->total_penjualan_sd_hari_ini, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item->keterangan ?? '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold bg-light">
                                    <td colspan="4" class="text-center">JUMLAH 5.1 - 5.2</td>
                                    <td class="text-center">{{ number_format($totals_penjualan['penjualan_bulan_ini_yg_lalu'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($totals_penjualan['penjualan_bulan_ini_hari_ini'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($totals_penjualan['total_bulan_ini'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($totals_penjualan['total_penjualan_sd_hari_ini'], 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TABEL VI: MUTU PRIMA (SIAP JUAL) --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">VI. Uraian Mutu Prima (Siap Jual)</h5>
                    </div>
                    <div class="card-body table-responsive">
                         @php
                            $uraian_mutu = ['Mutu Prima (siap jual)', 'PO / PRI Low', 'WhiteSpot (WS)', 'Kontaminasi', 'Repacking On Hold'];
                        @endphp
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th colspan="2">VI.</th>
                                    <th>Uraian</th>
                                    <th>Kg</th>
                                    <th>Pallet</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($uraian_mutu as $index => $uraian)
                                    @php $item = $data_mutu->get($uraian); @endphp
                                     <tr>
                                        <td></td>
                                        <td class="text-center">6.{{ $index + 1 }}</td>
                                        <td class="text-start">{{ $uraian }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->kg, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->pallet, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item->keterangan ?? '-' }}</td>
                                    </tr>
                                @endforeach
                                <tr class="fw-bold bg-light">
                                    <td colspan="3" class="text-center">JUMLAH 6.1 - 6.5</td>
                                    <td class="text-center">{{ number_format($totals_mutu['kg'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($totals_mutu['pallet'], 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <footer class="main-footer">
        @include('template.footer')
    </footer>

</div>

@include('template.script')

</body>
</html>
