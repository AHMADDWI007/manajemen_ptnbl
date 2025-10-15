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
        <h3 class="m-0 text-success">Data Produksi</h3>
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
            <h5 class="mb-0">Daftar Data Produksi</h5>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-bordered table-striped text-center align-middle">
              <thead class="bg-success text-white">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Jam</th>
                  <th>No Kamar</th>
                  <th>No Palet</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($data_produksi as $index => $produksi)
                  <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($produksi->tanggal)->format('d-m-Y') }}</td>
                    <td>{{ $produksi->jam }}</td>
                    <td>{{ $produksi->no_kamar }}</td>
                    <td>{{ $produksi->no_palet }}</td>
                    <td>
                      @if($produksi->status == 'selesai')
                        <span class="badge bg-success">Selesai</span>
                      @elseif($produksi->status == 'proses')
                        <span class="badge bg-warning text-dark">Proses</span>
                      @else
                        <span class="badge bg-secondary">Belum Mulai</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-muted text-center">Belum ada data produksi.</td>
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
