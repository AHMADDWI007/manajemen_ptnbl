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
                <h3 class="mb-4 text-success fw-bold">Hasil Uji SIR 20</h3>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">

                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <strong>Daftar Hasil Uji SIR 20</strong>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="dataTable">
                            <thead class="text-center bg-light">
                                {{-- HEADER TABEL DISESUAIKAN --}}
                                <tr>
                                    <th>No</th>
                                    <th>No. Palet</th>
                                    <th>Po</th>
                                    <th>Pa</th>
                                    <th>PRI</th>
                                    <th>Dirt (%)</th>
                                    <th>Ash (%)</th>
                                    <th>VM (%)</th>
                                    <th>Money</th>
                                    <th>Nitrogen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data_sir_20 as $item)
                                    <tr>
                                        {{-- ISI TABEL DISESUAIKAN --}}
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ $item->no_palet }}</td>
                                        <td class="text-center">{{ $item->po ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pa ?? '-' }}</td>
                                        <td class="text-center">{{ $item->pri ?? '-' }}</td>
                                        <td class="text-center">{{ $item->dirt ?? '-' }}</td>
                                        <td class="text-center">{{ $item->ask ?? '-' }}</td>
                                        <td class="text-center">{{ $item->vm ?? '-' }}</td>
                                        <td class="text-center">{{ isset($item->money) ? number_format($item->money, 2, ',', '.') : '-' }}</td>
                                        <td class="text-center">{{ $item->nitrogen ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- Colspan disesuaikan menjadi 10 --}}
                                        <td colspan="10" class="text-center text-muted">Belum ada data hasil uji SIR 20.</td>
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