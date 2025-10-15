<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <style>
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
        }
        .table thead th {
            vertical-align: middle;
            text-align: center;
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
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-success fw-bold">Pengadaan Bokar (Raw Material)</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Rekapitulasi Laporan Harian</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="bg-light">
                                {{-- PERUBAHAN 1: Header tabel disesuaikan dengan foto --}}
                                <tr>
                                    <th rowspan="2">No</th>
                                    <th rowspan="2">URAIAN</th>
                                    <th rowspan="2">Stock Awal (Kg)</th>
                                    <th colspan="2">Penerimaan Bokar (Kg KR)</th>
                                    <th rowspan="2">Jumlah Stock Bokar (Kg)</th>
                                    <th colspan="2">Bokar Diproses (Kg KK)</th>
                                    <th rowspan="2">Sisa Stock (Kg)</th>
                                </tr>
                                <tr>
                                    <th>Masuk Hari Ini</th>
                                    <th>S/D Hari Ini</th>
                                    <th>Hari Ini</th>
                                    <th>S/D Hari Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Pastikan controller mengirim variabel $data_basah atau $data_bokar --}}
                                @forelse ($data_basah as $item)
                                    <tr>
                                        {{-- PERUBAHAN 2: Isi tabel disesuaikan --}}
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ $item->uraian ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->stock_awal, 0, ',', '.') ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->penerimaan_harian, 0, ',', '.') ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->penerimaan_sd_hari_ini, 0, ',', '.') ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->jumlah_stock_bokar, 0, ',', '.') ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->bokar_diproses_harian, 0, ',', '.') ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->bokar_diproses_sd_hari_ini, 0, ',', '.') ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($item->sisa_stock, 0, ',', '.') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- PERUBAHAN 3: Colspan disesuaikan menjadi 9 --}}
                                        <td colspan="9" class="text-center text-muted">Belum ada data.</td>
                                    </tr>
                                @endforelse
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
        // Inisialisasi DataTable dengan opsi paging dan searching dinonaktifkan
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