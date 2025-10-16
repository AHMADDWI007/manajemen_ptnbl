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
                <h3 class="m-0 text-success fw-bold">Produksi SIR 20</h3>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Stock Dalam Gudang SIR</h5>
                    </div>
                    <div class="card-body table-responsive">
                        @php
                            // Daftar uraian yang akan selalu ditampilkan
                            $uraian_list = [
                                'Di Gudang SIR',
                                'Di Areal Press Bale',
                                'Di Gudang TOH 1',
                                'Di Gudang TOH 2',
                            ];
                        @endphp
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th colspan="2" rowspan="2">IV.</th>
                                    <th rowspan="2">Stock Dalam Gudang SIR</th>
                                    <th rowspan="2">Saldo Awal</th>
                                    <th rowspan="2">Masuk</th>
                                    <th rowspan="2">Total</th>
                                    <th colspan="2">Produksi Bulan Ini</th>
                                    <th rowspan="2">Pengiriman</th>
                                    <th rowspan="2">Saldo Akhir</th>
                                    <th rowspan="2">PTNB</th>
                                    <th rowspan="2">TOTAL I SD IV</th>
                                </tr>
                                <tr>
                                    <th>Yg lalu</th>
                                    <th>s/d HI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($uraian_list as $index => $uraian)
                                    @php
                                        // Cari data yang cocok dari controller
                                        $item = $data_produksi->get($uraian);
                                    @endphp
                                    <tr>
                                        <td></td>
                                        <td class="text-center">4.{{ $index + 1 }}</td>
                                        <td class="text-start">{{ $uraian }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->saldo_awal, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->masuk, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->total, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->produksi_bulan_lalu, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->produksi_sd_hi, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->pengiriman, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->saldo_akhir, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->pt_nb, 0, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->total_100_persen, 0, ',', '.') : '-' }}</td>
                                    </tr>
                                @endforeach
                                {{-- Baris Jumlah/Total --}}
                                @if(isset($totals))
                                <tr class="fw-bold bg-light">
                                    <td colspan="4" class="text-center">Jumlah 4.1 - 4.4</td>
                                    <td class="text-center">{{ number_format($totals['masuk'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($totals['total'], 0, ',', '.') }}</td>
                                    <td></td>
                                    <td class="text-center">{{ number_format($totals['produksi_sd_hi'], 0, ',', '.') }}</td>
                                    <td></td>
                                    <td class="text-center">{{ number_format($totals['saldo_akhir'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($totals['pt_nb'], 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($totals['total_100_persen'], 0, ',', '.') }}</td>
                                </tr>
                                @endif
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

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "paging": false,
            "searching": false,
            "info": false,
            "ordering": false
        });
    });
</script>

</body>
</html>
