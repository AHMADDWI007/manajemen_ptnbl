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
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-success">Data Bokar</h1>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="content">
      <div class="container-fluid">

        {{-- Tabel Data --}}
        <div class="card shadow-sm">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0">Daftar Data Bokar</h5>
          </div>
          <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
              <thead class="bg-success text-white text-center">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Berat Penuh (kg)</th>
                  <th>Berat Truk (kg)</th>
                  <th>Berat Muatan (kg)</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($data_bokar as $index => $bokar)
                  <tr class="text-center">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($bokar->created_at)->format('d-m-Y') }}</td>
                    <td>{{ $bokar->berat_penuh }}</td>
                    <td>{{ $bokar->berat_truk }}</td>
                    <td>{{ $bokar->berat_muatan }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada data bokar.</td>
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
