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
                <h3 class="mb-4 text-success fw-bold">Hasil Uji Troli</h3>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                {{-- Notifikasi (jika ada) --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Tabel Data --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Hasil Uji Troli</strong>
                    </div>
                    <div class="card-body table-responsive">
                        {{-- ID tabel diubah agar lebih konsisten jika Anda mau --}}
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light">
                                {{-- PERUBAHAN 1: Header tabel disesuaikan --}}
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No. Trolly</th>
                                    <th>K3</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Jam Sample</th>
                                    <th>Lama Pengeringan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Pastikan controller mengirim variabel bernama $data_troli --}}
                                @forelse ($data_troli as $item)
                                    <tr>
                                        {{-- PERUBAHAN 2: Isi data tabel disesuaikan --}}
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $item->no_trolly }}</td>
                                        <td class="text-center">{{ $item->k3 ?? '-' }}</td>
                                        <td class="text-center">{{ $item->po ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pa ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pri ?? '-' }}</td>
                                        {{-- Format jam agar hanya menampilkan Jam:Menit --}}
                                        <td class="text-center">{{ $item->jam_sample ? \Carbon\Carbon::parse($item->jam_sample)->format('H:i') : '-' }}</td>
                                        <td class="text-center">{{ $item->lama_pengeringan ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- PERUBAHAN 3: Colspan disesuaikan menjadi 9 --}}
                                        <td colspan="9" class="text-center text-muted">Belum ada data hasil uji troli.</td>
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

{{-- Script untuk DataTable (pencarian, paginasi) --}}
<script>
    $(document).ready(function () {
        $('#dataTable').DataTable({
            "responsive": true,
            "autoWidth": false,
        });
    });
</script>

</body>
</html>