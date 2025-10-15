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
        <h3 class="mb-4 text-success fw-bold">Hasil Uji Maturasi</h3>
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
            <strong>Daftar Hasil Uji Maturasi</strong>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="text-center bg-light">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Jam</th>
                  <th>No Kamar</th>
                  <th>Hasil Uji</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($data_maturasi as $index => $maturasi)
                  <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($maturasi->tanggal)->format('d-m-Y') }}</td>
                    <td>{{ $maturasi->jam }}</td>
                    <td>{{ $maturasi->no_kamar }}</td>
                    <td>{{ $maturasi->hasil_uji ?? '-' }}</td>
                    <td class="text-center">
                      @if($maturasi->status == 'Menunggu Hasil')
                        <span class="badge bg-warning text-dark">{{ $maturasi->status }}</span>
                      @elseif($maturasi->status == 'Selesai')
                        <span class="badge bg-success">{{ $maturasi->status }}</span>
                      @else
                        <span class="badge bg-secondary">{{ $maturasi->status }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted">Belum ada data hasil uji maturasi.</td>
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
