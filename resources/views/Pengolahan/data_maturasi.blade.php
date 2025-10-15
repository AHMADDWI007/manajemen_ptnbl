<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Navbar -->
    @include('template.navbar')

    <!-- Sidebar -->
    @include('template.sidebar')

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <h3 class="mb-4 text-success fw-bold">Pengolahan Maturasi</h3>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                {{-- Notifikasi sukses --}}
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                {{-- Tabel Data --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Data Maturasi</strong>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th>No</th>
                                    <th>Berat Penuh (kg)</th>
                                    <th>Berat Truk (kg)</th>
                                    <th>Berat Muatan (kg)</th>
                                    <th>Hasil Lab</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data_maturasi as $index => $maturasi)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ number_format($maturasi->berat_penuh, 0, ',', '.') }}</td>
                                        <td>{{ number_format($maturasi->berat_truk, 0, ',', '.') }}</td>
                                        <td>{{ number_format($maturasi->berat_muatan, 0, ',', '.') }}</td>
                                        <td>
                                            @if($maturasi->hasil_lab)
                                                {{ $maturasi->hasil_lab }}
                                            @else
                                                <span class="text-muted fst-italic">Belum diisi</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('maturasi.edit', $maturasi->id) }}" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Tambah/Edit Hasil Lab
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data maturasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        @include('template.footer')
    </footer>

</div>

@include('template.script')

</body>
</html>
