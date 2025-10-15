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
        <h3 class="mb-4 text-success fw-bold">Hasil Uji Lab Bokar</h3>
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
            <strong>Daftar Hasil Uji Lab Bokar</strong>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="text-center bg-light">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>No Kamar</th>
                  <th>Hasil Uji</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($data_lab as $index => $lab)
                  <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $lab->tanggal }}</td>
                    <td>{{ $lab->no_kamar }}</td>
                    <td>{{ $lab->hasil_uji ?? '-' }}</td>
                    <td class="text-center">
                      @if($lab->status == 'Menunggu Hasil')
                        <span class="badge bg-warning text-dark">{{ $lab->status }}</span>
                      @elseif($lab->status == 'Selesai')
                        <span class="badge bg-success">{{ $lab->status }}</span>
                      @else
                        <span class="badge bg-secondary">{{ $lab->status }}</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada data hasil uji lab.</td>
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
