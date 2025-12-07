<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Data Produksi SIR 20</title>
    
    {{-- CSS Libraries --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Style Tabel */
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
            white-space: nowrap;
            padding: 0.5rem;
            vertical-align: middle !important;
        }
        .table thead th {
            text-align: center !important;
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-weight-bold { font-weight: 700 !important; }
        .row-jumlah { font-weight: bold; background-color: #f8f9fa; }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h3 class="mb-0 text-success fw-bold">Laporan Produksi SIR 20</h3>
                <div>
                    <button type="button" class="btn btn-success btn-sm fw-bold mr-2" id="btnInputBaru">
                        <i class="fas fa-plus-circle"></i> Input Data Gudang
                    </button>
                    <button type="button" class="btn btn-success btn-sm fw-bold mr-2" id="btnInputMutu">
                        <i class="fas fa-plus-circle"></i> Input Data Mutu
                    </button>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                {{-- FILTER TANGGAL --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-2">
                        <form method="GET" action="{{ route('produksi-sir.index') }}" class="form-inline justify-content-end">
                            <label class="mr-2">Tanggal:</label>
                            <input type="date" name="filter_tanggal" class="form-control form-control-sm mr-2" value="{{ $selected_date }}" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                @endif

                {{-- TABEL IV (GUDANG) --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">IV. PRODUKSI SIR 20</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="header-white">
                                    <tr>
                                        <th rowspan="2" width="5%">No</th>
                                        <th rowspan="2" width="20%">Stock Dalam Gudang SIR</th>
                                        <th rowspan="2">Saldo Awal</th>
                                        <th rowspan="2">Masuk</th>
                                        <th rowspan="2">Total</th>
                                        <th colspan="2">Produksi Bulan Ini</th>
                                        <th rowspan="2">Pengiriman</th>
                                        <th rowspan="2">Saldo Akhir</th>
                                        <th rowspan="2">TOTAL I SD IV</th>
                                        <th rowspan="2" width="8%">Aksi</th>
                                    </tr>
                                    <tr><th>Yg lalu</th><th>s/d HI</th></tr>
                                </thead>
                                <tbody>
                                @foreach ($tabelIV as $item)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $item->no }}</td>
                                        <td>{{ $item->uraian }}</td>
                                        <td class="text-center">{{ number_format($item->saldo_awal, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->masuk, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->total, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->prod_bln_lalu, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->prod_sd_hi, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->pengiriman, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($item->saldo_akhir, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($item->total_i_sd_iv, 2, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($item->id)
                                                <button class="btn btn-warning btn-sm btn-edit-iv" 
                                                    data-uraian="{{ $item->uraian }}" 
                                                    data-masuk="{{ $item->masuk }}" 
                                                    data-pengiriman="{{ $item->pengiriman }}" 
                                                    data-ket="{{ $item->keterangan }}"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('produksi-sir.destroy', $item->id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset data?')" title="Reset">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn btn-success btn-sm btn-edit-iv" 
                                                    data-new="true" 
                                                    data-uraian="{{ $item->uraian }}" 
                                                    data-masuk="0" 
                                                    data-pengiriman="0" 
                                                    data-ket="-"
                                                    title="Input">
                                                    <i class="fas fa-plus-circle"></i> Input
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="row-jumlah">
                                    <td colspan="2" class="text-center">Jumlah 4.1 - 4.4</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('saldo_awal'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('masuk'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('total'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('prod_bln_lalu'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('prod_sd_hi'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('pengiriman'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('saldo_akhir'), 2, ',', '.') }}</td>
                                    <td class="text-center bg-warning">{{ number_format($tabelIV->sum('total_i_sd_iv'), 2, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- TABEL VI (MUTU) --}}
                <div class="card shadow-sm">
                     <div class="card-header bg-success text-white">VI. Rincian Mutu</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="header-white">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="30%">Uraian</th>
                                        <th>Kg</th>
                                        <th>Pallet</th>
                                        <th>Keterangan</th>
                                        <th width="8%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($tabelVI as $item)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $item->no }}</td>
                                        <td>{{ $item->uraian }}</td>
                                        <td class="text-center">{{ number_format($item->kg, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->pallet, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $item->keterangan }}</td>
                                        <td class="text-center">
                                            @if($item->id)
                                                <button class="btn btn-warning btn-sm btn-edit-vi" 
                                                    data-uraian="{{ $item->uraian }}" data-kg="{{ $item->kg }}" 
                                                    data-pallet="{{ $item->pallet }}" data-ket="{{ $item->keterangan }}"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('produksi-sir.destroy', $item->id) }}" method="POST" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reset data?')" title="Reset">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <button class="btn btn-success btn-sm btn-edit-vi" 
                                                    data-new="true"
                                                    data-uraian="{{ $item->uraian }}" data-kg="0" data-pallet="0" data-ket="-"
                                                    title="Input">
                                                    <i class="fas fa-plus-circle"></i> Input
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
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

{{-- MODAL FORM IV (GUDANG) --}}
<div class="modal fade" id="modalEditIV" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('produksi-sir.store') }}" method="POST">
                @csrf
                <input type="hidden" name="tanggal" value="{{ $selected_date }}">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Input Data Gudang SIR</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Pilih Gudang / Lokasi</label>
                        <select name="uraian" id="uraianIV" class="form-control font-weight-bold">
                            <option value="Di Gudang SIR">Di Gudang SIR</option>
                            <option value="Di Areal Press Bale">Di Areal Press Bale</option>
                            <option value="Di Gudang TOH 1">Di Gudang TOH 1</option>
                            <option value="Di Gudang TOH 2">Di Gudang TOH 2</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label>Masuk (Kg)</label>
                        <input type="number" step="0.01" name="masuk" id="masuk" class="form-control" placeholder="0">
                        <small class="text-muted" id="hintWIP" style="display:none;">*Angka otomatis dari Laporan WIP.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label>Pengiriman (Kg)</label>
                        <input type="number" step="0.01" name="pengiriman" id="pengiriman" class="form-control" placeholder="0">
                    </div>
                    <div class="form-group mb-3">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" id="keteranganIV" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL FORM VI (MUTU) --}}
<div class="modal fade" id="modalEditVI" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('produksi-sir.store') }}" method="POST">
                @csrf
                <input type="hidden" name="tanggal" value="{{ $selected_date }}">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Input Data Mutu</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Uraian</label>
                        {{-- ✅ PERBAIKAN: Ganti Input Text jadi Select agar bisa pilih semua --}}
                        <select name="uraian" id="uraianVI" class="form-control font-weight-bold">
                            <option value="Mutu Prima (siap jual)">Mutu Prima (siap jual)</option>
                            <option value="PO / PRI Low">PO / PRI Low</option>
                            <option value="WhiteSpot (WS)">WhiteSpot (WS)</option>
                            <option value="Kontaminasi">Kontaminasi</option>
                            <option value="Repacking On Hold">Repacking On Hold</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Kg</label>
                            <input type="number" step="0.01" name="kg" id="kg" class="form-control">
                            <small class="text-muted" id="hintMutu" style="display:none;">*Sesuai Saldo Gudang SIR</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Pallet</label>
                            <input type="number" name="pallet" id="pallet" class="form-control">
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="keteranganVI" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('template.script')
<script>
$(document).ready(function () {
    
    // Variabel PHP ke JS
    var produksiWip = {{ $produksiHariIni ?? 0 }};
    var saldoGudang = {{ $saldoAkhirGudangSIR ?? 0 }};

    // --- LOGIKA TABEL IV (GUDANG) ---
    function updateMasukValue(isNew) {
        var uraian = $('#uraianIV').val();
        if (isNew) {
            if (uraian === 'Di Gudang SIR') {
                $('#masuk').val(produksiWip);
                $('#hintWIP').show();
            } else {
                $('#masuk').val(0);
                $('#hintWIP').hide();
            }
        }
    }

    $('#btnInputBaru').click(function() {
        $('#uraianIV').val('Di Gudang SIR').prop('readonly', false); 
        // Enable kembali select agar user bisa pilih gudang lain
        $('#uraianIV option').prop('disabled', false); 
        
        $('#pengiriman').val(0); $('#keteranganIV').val('');
        updateMasukValue(true);
        $('#modalEditIV').modal('show');
    });

    $('#uraianIV').change(function() {
        if (!$('#uraianIV').prop('readonly')) { updateMasukValue(true); }
    });

    $(document).on('click', '.btn-edit-iv', function() {
        var uraian = $(this).data('uraian');
        var isNew = $(this).data('new');

        $('#uraianIV').val(uraian).prop('readonly', true);
        // Disable opsi lain agar tidak bisa ganti gudang saat edit per baris
        $('#uraianIV option').not(':selected').prop('disabled', true);

        $('#pengiriman').val($(this).data('pengiriman')); 
        $('#keteranganIV').val($(this).data('ket'));

        if (isNew) {
            updateMasukValue(true);
        } else {
            $('#masuk').val($(this).data('masuk'));
            $('#hintWIP').hide();
        }
        $('#modalEditIV').modal('show');
    });

    // --- LOGIKA TABEL VI (MUTU) ---
    
    // Hitung Pallet Otomatis
    function hitungPallet() {
        var kg = parseFloat($('#kg').val()) || 0;
        var pallet = Math.round(kg / 1260); // Pembulatan
        $('#pallet').val(pallet);
    }
    $('#kg').on('input', hitungPallet);

    // Auto Fill Saldo Gudang
    function updateMutuValue(isNew) {
        // SEMUA URAIAN MUTU mengambil saran saldo gudang
        if (isNew) {
            $('#kg').val(saldoGudang);
            hitungPallet(); 
            $('#hintMutu').show();
        } else {
            $('#hintMutu').hide();
        }
    }

    // 1. Tombol Input Mutu (Header)
    $('#btnInputMutu').click(function() {
        $('#uraianVI').val('Mutu Prima (siap jual)').prop('readonly', false);
        $('#uraianVI option').prop('disabled', false); // Buka kunci dropdown

        $('#keteranganVI').val('');
        updateMutuValue(true); // Auto fill
        $('#modalEditVI').modal('show');
    });
    
    // Listener Ganti Dropdown Mutu
    $('#uraianVI').change(function() {
        if (!$('#uraianVI').prop('readonly')) { updateMutuValue(true); }
    });

    // 2. Tombol Edit/Input Per Baris Mutu
    $(document).on('click', '.btn-edit-vi', function() {
        var uraian = $(this).data('uraian');
        var isNew = $(this).data('new');

        $('#uraianVI').val(uraian).prop('readonly', true);
        $('#uraianVI option').not(':selected').prop('disabled', true);

        $('#keteranganVI').val($(this).data('ket'));

        if (isNew) {
            updateMutuValue(true);
        } else {
            $('#kg').val($(this).data('kg'));
            $('#pallet').val($(this).data('pallet'));
            $('#hintMutu').hide();
        }
        $('#modalEditVI').modal('show');
    });
});
</script>
</body>
</html>