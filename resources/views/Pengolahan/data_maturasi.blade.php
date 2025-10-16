<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <style>
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: nowrap; /* Mencegah teks turun baris */
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
                <h3 class="mb-4 text-success fw-bold">Pengolahan Maturasi</h3>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Data Maturasi</strong>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light">
                                {{-- PERUBAHAN 1: Header NO. dipecah menjadi dua kolom --}}
                                <tr>
                                    <th colspan="2" rowspan="3">NO.</th>
                                    <th rowspan="3">URAIAN PROSES</th>
                                    <th colspan="3">Stock Awal</th>
                                    <th colspan="2">Diproses HI</th>
                                    <th rowspan="2">Masuk</th>
                                    <th colspan="4">Quality</th>
                                    <th rowspan="3">Stock Akhir</th>
                                    <th rowspan="3">Asal Bokar</th>
                                    <th rowspan="3">Keterangan</th>
                                </tr>
                                <tr>
                                    <th rowspan="2">Kg KK</th>
                                    <th rowspan="2">Tgl</th>
                                    <th rowspan="2">Umur</th>
                                    <th rowspan="2">Diolah</th>
                                    <th rowspan="2">Mutasi</th>
                                    <th rowspan="2">K3</th>
                                    <th rowspan="2">Po</th>
                                    <th rowspan="2">Pa</th>
                                    <th rowspan="2">PRI</th>
                                </tr>
                                <tr>
                                    <th>HI</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- PERUBAHAN 2: Baris sub-judul disesuaikan menjadi dua kolom --}}
                                <tr class="fw-bold bg-light">
                                    <td class="text-center">II.</td>
                                    <td class="text-center">2</td>
                                    <td class="text-start">Di Bangsal Maturasi:</td>
                                    <td colspan="12"></td>
                                </tr>
                                @for ($i = 1; $i <= 49; $i++)
                                    @php
                                        $uraian = "D Bak Maturasi-" . $i;
                                        $item = $data_maturasi->get($uraian);
                                    @endphp
                                    <tr>
                                        {{-- PERUBAHAN 3: Kolom NO. di data dipecah menjadi dua --}}
                                        <td></td>
                                        <td class="text-center">2.{{ $i }}</td>
                                        <td class="text-start">{{ $uraian }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->kg_kk, 0, ',', '.') : '0' }}</td>
                                        <td class="text-center">{{ $item && $item->tgl ? \Carbon\Carbon::parse($item->tgl)->format('d-M-y') : '-' }}</td>
                                        <td class="text-center">{{ $item->umur ?? '0 hari' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->diolah, 0, ',', '.') : '0' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->mutasi, 0, ',', '.') : '0' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->masuk_hi, 0, ',', '.') : '0' }}</td>
                                        <td class="text-center">{{ $item->k3 ?? '-' }}</td>
                                        <td class="text-center">{{ $item->po ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pa ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pri ?? '-' }}</td>
                                        <td class="text-center">{{ $item ? number_format($item->stock_akhir, 0, ',', '.') : '0' }}</td>
                                        <td class="text-center">{{ $item->asal_bokar ?? '-' }}</td>
                                        <td class="text-center">{{ $item->keterangan ?? '-' }}</td>
                                    </tr>
                                @endfor
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

{{-- Menambahkan link CDN jQuery untuk memastikan library dimuat --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

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

