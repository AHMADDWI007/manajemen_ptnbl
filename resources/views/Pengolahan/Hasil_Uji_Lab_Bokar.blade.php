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
                        <h1 class="m-0 text-success fw-bold">Hasil Uji Lab Bokar</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Hasil Uji Lab Bokar</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                {{-- Tabel Data --}}
                <div class="card shadow-sm">
                    {{-- PERUBAHAN 1: Tombol "Tambah Data" dihapus dari header --}}
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Hasil Uji Lab Bokar</strong>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>No Sampel</th>
                                    <th>K3 (%)</th>
                                    <th>Dirt (%)</th>
                                    <th>Ask (%)</th>
                                    {{-- PERUBAHAN 2: Kolom "Aksi" dihapus --}}
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_lab as $item)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->suplier }}</td>
                                        <td>{{ $item->no_sampel }}</td>
                                        <td class="text-center">{{ $item->k3 ?? '-' }}</td>
                                        <td class="text-center">{{ $item->dirt ?? '-' }}</td>
                                        <td class="text-center">{{ $item->ask ?? '-' }}</td>
                                        {{-- PERUBAHAN 3: Kolom yang berisi tombol Edit dan Hapus dihapus --}}
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- PERUBAHAN 4: Colspan disesuaikan menjadi 7 karena kolom Aksi hilang --}}
                                        <td colspan="7" class="text-center text-muted">Belum ada data hasil uji lab.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PERUBAHAN 5: Seluruh bagian Modal Form dihapus karena tidak lagi diperlukan --}}

    <footer class="main-footer">
        @include('template.footer')
    </footer>

</div>

@include('template.script')

{{-- PERUBAHAN 6: JavaScript disederhanakan, hanya menyisakan inisialisasi DataTable --}}
<script>
    // Inisialisasi DataTable untuk fitur pencarian, paginasi, dll.
    $(document).ready(function() {
        $('#dataTable').DataTable();
    });
</script>

</body>
</html>