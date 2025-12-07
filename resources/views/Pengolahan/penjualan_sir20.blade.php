<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; padding: 6px 12px; }
        /* Header Tabel Putih */
        .header-white th { text-align: center; font-weight: bold; background-color: #ffffff; color: #343a40; }
        /* Header Tabel Hijau (Opsional jika ingin header hijau) */
        .header-green th { text-align: center; font-weight: bold; background-color: #28a745; color: white; }
        
        /* Highlight Kolom 'Yg Lalu' agar mirip foto Excel */
        .bg-highlight { background-color: #d4edda; color: #155724; } 
        
        .card-header { font-weight: bold; text-transform: uppercase; }
        .row-jumlah { font-weight: bold; background-color: #f8f9fa; }
    </style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Penjualan SIR 20</h3>
                <button type="button" class="btn btn-success btn-sm fw-bold" id="btnInputBaru">
                    <i class="fas fa-plus-circle"></i> Input Penjualan
                </button>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                {{-- FILTER TANGGAL --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-2">
                        <form method="GET" action="{{ route('penjualan_sir20.index') }}" class="form-inline justify-content-end">
                            <label class="mr-2">Tanggal:</label>
                            <input type="date" name="filter_tanggal" class="form-control form-control-sm mr-2" value="{{ $selected_date }}">
                            <button type="submit" class="btn btn-success btn-sm">Tampilkan</button>
                        </form>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif

                {{-- TABEL PENJUALAN --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        V. TELAH DIJUAL (KG SIR-20)
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="header-white">
                                    <tr>
                                        <th rowspan="2" width="5%">V.</th>
                                        <th rowspan="2" width="5%">No</th>
                                        <th rowspan="2">Telah Dijual (KG SIR-20)</th>
                                        <th rowspan="2" width="12%">s/d<br>{{ $headerBulanLalu }}</th>
                                        <th colspan="2">Penjualan Bulan Ini</th>
                                        <th rowspan="2">Total Bulan Ini</th>
                                        <th rowspan="2">Total Penjualan<br>s/d Hari ini</th>
                                        <th rowspan="2">Keterangan</th>
                                        <th rowspan="2" width="5%">Aksi</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-highlight">Yg lalu</th>
                                        <th>Hari Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tabelData as $item)
                                        <tr>
                                            <td></td>
                                            <td class="text-center font-weight-bold">{{ $item->no }}</td>
                                            <td class="font-weight-bold">{{ $item->uraian }}</td>
                                            <td class="text-center">{{ number_format($item->sd_bulan_lalu, 0, ',', '.') }}</td>
                                            
                                            {{-- Kolom Yg Lalu Highlight --}}
                                            <td class="text-center ">{{ number_format($item->bln_ini_lalu, 0, ',', '.') }}</td>
                                            
                                            <td class="text-center font-weight-bold ">{{ number_format($item->hari_ini, 0, ',', '.') }}</td>
                                            <td class="text-center font-weight-bold">{{ number_format($item->total_bln_ini, 0, ',', '.') }}</td>
                                            <td class="text-center font-weight-bold">{{ number_format($item->total_sd_hari_ini, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ $item->keterangan }}</td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-warning btn-sm dropdown-toggle" data-toggle="dropdown">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        {{-- EDIT --}}
                                                        <a class="dropdown-item btn-edit" href="javascript:void(0)" 
                                                           data-uraian="{{ $item->uraian }}"
                                                           data-hari_ini="{{ $item->hari_ini }}"
                                                           data-ket="{{ $item->keterangan }}">
                                                            <i class="fas fa-edit text-warning mr-2"></i> Input/Edit
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        {{-- RESET --}}
                                                        @if($item->id)
                                                            <form action="{{ route('penjualan_sir20.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Reset data ini?');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-undo mr-2"></i> Reset
                                                                </button>
                                                            </form>
                                                        @else
                                                            <a class="dropdown-item disabled"><i class="fas fa-undo mr-2"></i> Reset</a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- BARIS JUMLAH --}}
                                    <tr class="row-jumlah">
                                        <td></td>
                                        <td></td>
                                        <td class="text-center">JUMLAH 5.1 - 5.2</td>
                                        <td class="text-center">{{ number_format($tabelData->sum('sd_bulan_lalu'), 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($tabelData->sum('bln_ini_lalu'), 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($tabelData->sum('hari_ini'), 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($tabelData->sum('total_bln_ini'), 0, ',', '.') }}</td>
                                        <td class="text-center bg-warning">{{ number_format($tabelData->sum('total_sd_hari_ini'), 0, ',', '.') }}</td>
                                        <td>-</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <footer class="main-footer">@include('template.footer')</footer>
</div>

{{-- MODAL INPUT PENJUALAN --}}
<div class="modal fade" id="modalInput" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('penjualan_sir20.store') }}" method="POST">
                @csrf
                <input type="hidden" name="tanggal" value="{{ $selected_date }}">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Input Penjualan SIR 20</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Uraian</label>
                        <select name="uraian" id="inputUraian" class="form-control font-weight-bold">
                            <option value="SIR20 PTNBL">SIR20 PTNBL</option>
                            <option value="SIR20 PTPN4">SIR20 PTPN4</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label>Penjualan Hari Ini (Kg)</label>
                        <input type="number" step="0.01" name="hari_ini" id="inputHariIni" class="form-control" required placeholder="0">
                        <small class="text-muted">Masukkan jumlah penjualan untuk tanggal {{ \Carbon\Carbon::parse($selected_date)->format('d-m-Y') }}</small>
                    </div>

                    <div class="form-group mb-3">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" id="inputKeterangan" class="form-control" placeholder="-">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('template.script')

<script>
$(document).ready(function () {
    
    // 1. Tombol Input Baru (Header)
    $('#btnInputBaru').click(function() {
        $('#inputUraian').val('SIR20 PTNBL'); // Default
        $('#inputHariIni').val('');
        $('#inputKeterangan').val('');
        $('#modalInput').modal('show');
    });

    // 2. Tombol Edit (Di Tabel)
    $('.btn-edit').click(function() {
        var uraian = $(this).data('uraian');
        var hariIni = $(this).data('hari_ini');
        var ket = $(this).data('ket');

        $('#inputUraian').val(uraian);
        $('#inputHariIni').val(hariIni);
        $('#inputKeterangan').val(ket);
        
        $('#modalInput').modal('show');
    });

});
</script>
</body>
</html>