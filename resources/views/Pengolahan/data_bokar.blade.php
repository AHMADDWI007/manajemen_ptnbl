<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
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
                        <h1 class="m-0 text-success fw-bold">Data Pengolahan Bokar</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Daftar Pengolahan Bokar</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No. Kamar</th>
                                    <th>Berat Awal (Kg)</th>
                                    <th>Berat Truck (Kg)</th>
                                    <th>Berat Basah (Kg)</th>
                                    <th>K3 Lab (%)</th>
                                    <th>Berat Kering (Kg)</th>
                                    <th>Total DS</th>
                                    <th>Total PT</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Pastikan Controller mengirim variabel $data_basah --}}
                                @forelse ($data_basah as $item)
                                    <tr class="text-center">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->no_kamar ?? '-' }}</td>
                                        <td>{{ isset($item->berat_awal) ? number_format($item->berat_awal, 2, ',', '.') : '-' }}</td>
                                        <td>{{ isset($item->berat_truck) ? number_format($item->berat_truck, 2, ',', '.') : '-' }}</td>
                                        <td>{{ isset($item->berat_basah) ? number_format($item->berat_basah, 2, ',', '.') : '-' }}</td>
                                        <td>{{ $item->k3_lab ?? '-' }}</td>
                                        <td>{{ isset($item->berat_kering) ? number_format($item->berat_kering, 2, ',', '.') : '-' }}</td>
                                        <td>{{ isset($item->total_ds) ? number_format($item->total_ds, 2, ',', '.') : '-' }}</td>
                                        <td>{{ isset($item->total_pt) ? number_format($item->total_pt, 2, ',', '.') : '-' }}</td>
                                        <td>{{ isset($item->jumlah) ? number_format($item->jumlah, 2, ',', '.') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">Belum ada data.</td>
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
        $('#dataTable').DataTable();
    });
</script>

</body>
</html>

