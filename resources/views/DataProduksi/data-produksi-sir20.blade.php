<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; vertical-align: middle; white-space: nowrap; padding: 6px 12px; }
        .header-white th { text-align: center; font-weight: bold; background-color: #ffffff; color: #343a40; }
        .header-green th { text-align: center; font-weight: bold; background-color: #28a745; color: white; }
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
                <h3 class="mb-0 text-success fw-bold">Data SIR (Gudang & Mutu)</h3>
                <div>
                    {{-- TOMBOL INPUT DATA --}}
                    <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" id="btnInputData">
                        <i class="fas fa-plus-circle"></i> Input Data
                    </button>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                
                {{-- TABEL IV (GUDANG) --}}
                <div class="card shadow-sm mb-4">
                    
                    {{-- HEADER HIJAU DENGAN FILTER TANGGAL --}}
                    <div class="card-header bg-success d-flex align-items-center">
                        <h3 class="card-title font-weight-bold text-white">IV. PRODUKSI SIR 20</h3>
                        
                        {{-- FORM FILTER DI KANAN --}}
                        <form action="{{ route('data-sir.index') }}" method="GET" class="form-inline ml-auto">
                            <label for="filter_tanggal" class="mr-2 text-white font-weight-normal">Tanggal:</label>
                            <input type="date" name="filter_tanggal" id="filter_tanggal" 
                                   class="form-control form-control-sm mr-2" 
                                   value="{{ $selected_date }}" 
                                   onchange="this.form.submit()" 
                                   style="max-width: 160px;">
                        </form>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="header-white">
                                    <tr>
                                        <th rowspan="2">No</th>
                                        <th rowspan="2">Stock Dalam Gudang SIR</th>
                                        <th rowspan="2">Saldo Awal</th>
                                        <th rowspan="2">Masuk</th>
                                        <th rowspan="2">Total</th>
                                        <th colspan="2">Produksi Bulan Ini</th>
                                        <th rowspan="2">Pengiriman</th>
                                        <th rowspan="2">Saldo Akhir</th>
                                        <th rowspan="2">TOTAL I SD IV</th>
                                        <th rowspan="2">Aksi</th>
                                    </tr>
                                    <tr><th>Yg lalu</th><th>s/d HI</th></tr>
                                </thead>
                                <tbody>
                                @foreach ($tabelIV as $item)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $item->no }}</td>
                                        <td>{{ $item->uraian }}</td>
                                        <td class="text-center">{{ number_format($item->saldo_awal, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold text-primary">{{ number_format($item->masuk, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->total, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->prod_bln_lalu, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($item->prod_sd_hi, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold text-danger">{{ number_format($item->pengiriman, 2, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold">{{ number_format($item->saldo_akhir, 2, ',', '.') }}</td>
                                        <td class="text-center">-</td>
                                        <td class="text-center">
                                            {{-- TOMBOL AKSI DROPDOWN --}}
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-success btn-sm dropdown-toggle font-weight-bold" data-toggle="dropdown">
                                                    Aksi
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    {{-- DETAIL --}}
                                                    @if($item->id)
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                           onclick="bukaDetailIV(this)"
                                                           data-uraian="{{ $item->uraian }}"
                                                           data-masuk="{{ number_format($item->masuk, 2, ',', '.') }}"
                                                           data-pengiriman="{{ number_format($item->pengiriman, 2, ',', '.') }}"
                                                           data-ket="{{ $item->keterangan }}">
                                                            <i class="fas fa-eye text-info mr-2"></i> Detail
                                                        </a>
                                                    @else
                                                        <a class="dropdown-item disabled"><i class="fas fa-eye mr-2"></i> Detail</a>
                                                    @endif

                                                    {{-- EDIT --}}
                                                    <a class="dropdown-item btn-edit-iv" href="javascript:void(0)"
                                                       data-uraian="{{ $item->uraian }}"
                                                       data-masuk="{{ $item->masuk }}"
                                                       data-ket="{{ $item->keterangan }}" 
                                                       data-id="{{ $item->id }}">
                                                        <i class="fas fa-edit text-warning mr-2"></i> {{ $item->id ? 'Edit' : 'Edit' }}
                                                    </a>

                                                    <div class="dropdown-divider"></div>

                                                    {{-- RESET --}}
                                                    @if($item->id)
                                                        <form action="{{ route('data-sir.destroy', $item->id) }}" method="POST" class="form-reset">
                                                            @csrf @method('DELETE')
                                                            <button type="button" class="dropdown-item text-danger btn-reset">
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
                                <tr class="row-jumlah">
                                    <td colspan="2" class="text-center">Jumlah 4.1 - 4.4</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('saldo_awal'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('masuk'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('total'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('prod_bln_lalu'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('prod_sd_hi'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('pengiriman'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelIV->sum('saldo_akhir'), 2, ',', '.') }}</td>
                                    <td class="text-center font-weight-bold">{{ number_format($grandTotal ?? 0, 2, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- TABEL VI (MUTU) --}}
                <div class="card shadow-sm">
                     <div class="card-header bg-success">
                         <h3 class="card-title font-weight-bold text-white">VI. Rincian Mutu</h3>
                     </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="header-white">
                                    <tr>
                                        <th>No</th>
                                        <th>Uraian</th>
                                        <th>Kg</th>
                                        <th>Pallet</th>
                                        <th>Keterangan</th>
                                        <th width="5%">Aksi</th>
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
                                            {{-- TOMBOL AKSI DROPDOWN --}}
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-success btn-sm dropdown-toggle font-weight-bold" data-toggle="dropdown">
                                                    Aksi
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    {{-- DETAIL --}}
                                                    @if($item->id)
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                           onclick="bukaDetailVI(this)"
                                                           data-uraian="{{ $item->uraian }}"
                                                           data-kg="{{ number_format($item->kg, 2, ',', '.') }}"
                                                           data-pallet="{{ $item->pallet }}"
                                                           data-ket="{{ $item->keterangan }}">
                                                            <i class="fas fa-eye text-info mr-2"></i> Detail
                                                        </a>
                                                    @else
                                                        <a class="dropdown-item disabled"><i class="fas fa-eye mr-2"></i> Detail</a>
                                                    @endif

                                                    {{-- EDIT --}}
                                                    <a class="dropdown-item btn-edit-vi" href="javascript:void(0)"
                                                       data-uraian="{{ $item->uraian }}" 
                                                       data-kg="{{ $item->kg }}"
                                                       data-pallet="{{ $item->pallet }}"
                                                       data-ket="{{ $item->keterangan }}"
                                                       data-id="{{ $item->id }}">
                                                        <i class="fas fa-edit text-warning mr-2"></i> {{ $item->id ? 'Edit' : 'Input' }}
                                                    </a>

                                                    <div class="dropdown-divider"></div>

                                                    {{-- RESET --}}
                                                    @if($item->id)
                                                        <form action="{{ route('data-sir.destroy', $item->id) }}" method="POST" class="form-reset">
                                                            @csrf @method('DELETE')
                                                            <button type="button" class="dropdown-item text-danger btn-reset">
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
                                <tr class="row-jumlah">
                                    <td colspan="2" class="text-center">Total</td>
                                    <td class="text-center">{{ number_format($tabelVI->sum('kg'), 2, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($tabelVI->sum('pallet'), 0, ',', '.') }}</td>
                                    <td></td>
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

{{-- MODAL INPUT DATA --}}
<div class="modal fade" id="modalInputData" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('data-sir.store') }}" method="POST">
                @csrf
                <input type="hidden" name="tanggal" value="{{ $selected_date }}">
                <input type="hidden" name="id" id="editId">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Input Data Gudang & Mutu</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    {{-- 1. DATA OTOMATIS DARI PRODUKSI --}}
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Masuk (Kg)</label>
                            <input type="number" step="0.01" name="masuk" id="inputMasuk" class="form-control font-weight-bold text-primary" readonly>
                            <small class="text-muted" id="infoAuto">*Otomatis dari Produksi</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Pallet</label>
                            <input type="number" name="pallet" id="inputPallet" class="form-control font-weight-bold text-primary" readonly>
                            <small class="text-muted" id="infoAuto2">*Otomatis dari Produksi</small>
                        </div>
                    </div>
                    {{-- 2. PILIH GUDANG --}}
                    <div class="form-group mb-3" id="groupGudang">
                        <label>Pilih Gudang / Lokasi (Wajib)</label>
                        <select name="uraian" id="inputUraian" class="form-control font-weight-bold" required>
                            <option value="Di Gudang SIR">Di Gudang SIR</option>
                            <option value="Di Areal Press Bale">Di Areal Press Bale</option>
                            <option value="Di Gudang TOH 1">Di Gudang TOH 1</option>
                            <option value="Di Gudang TOH 2">Di Gudang TOH 2</option>
                        </select>
                    </div>
                    {{-- 3. INPUT PENGIRIMAN --}}
                    <div class="form-group mb-3" id="groupPengiriman">
                         <label>Pengiriman (Kg)</label>
                         <input type="number" step="0.01" name="pengiriman" id="inputPengiriman" class="form-control font-weight-bold text-danger">
                    </div>
                    <hr>
                    <label class="text-success">Rincian Mutu (Kg) :</label>
                    <div class="row">
                        <div class="col-md-6 mb-2"> <label><small>Mutu Prima (Siap Jual)</small></label> <input type="number" step="0.01" name="mutu_prima" id="in_mutu_prima" class="form-control" placeholder="0"> </div>
                        <div class="col-md-6 mb-2"> <label><small>PO / PRI Low</small></label> <input type="number" step="0.01" name="po_pri" id="in_po_pri" class="form-control" placeholder="0"> </div>
                        <div class="col-md-6 mb-2"> <label><small>WhiteSpot (WS)</small></label> <input type="number" step="0.01" name="ws" id="in_ws" class="form-control" placeholder="0"> </div>
                        <div class="col-md-6 mb-2"> <label><small>Kontaminasi</small></label> <input type="number" step="0.01" name="kontaminasi" id="in_kontaminasi" class="form-control" placeholder="0"> </div>
                        <div class="col-md-6 mb-2"> <label><small>Repacking On Hold</small></label> <input type="number" step="0.01" name="repacking" id="in_repacking" class="form-control" placeholder="0"> </div>
                    </div>
                    <hr>
                    <div class="form-group mb-3"> <label>Keterangan</label> <textarea name="keterangan" id="inputKeterangan" class="form-control" rows="2" placeholder="Contoh: PTNB4"></textarea> </div>
                </div>
                <div class="modal-footer"> <button type="submit" class="btn btn-primary">Simpan Data</button> </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETAIL --}}
<div class="modal fade" id="modalDetailIV" tabindex="-1"> <div class="modal-dialog"> <div class="modal-content"> <div class="modal-header bg-warning text-white"> <h5 class="modal-title">Detail Gudang SIR</h5> <button type="button" class="close text-white" data-dismiss="modal">&times;</button> </div> <div class="modal-body"> <dl class="row mb-0"> <dt class="col-sm-4">Uraian</dt><dd class="col-sm-8" id="detUraianIV"></dd> <dt class="col-sm-4">Masuk</dt><dd class="col-sm-8" id="detMasukIV"></dd> <dt class="col-sm-4">Pengiriman</dt><dd class="col-sm-8" id="detPengirimanIV"></dd> <dt class="col-sm-4">Keterangan</dt><dd class="col-sm-8" id="detKetIV"></dd> </dl> </div> </div> </div> </div>
<div class="modal fade" id="modalDetailVI" tabindex="-1"> <div class="modal-dialog"> <div class="modal-content"> <div class="modal-header bg-info text-white"> <h5 class="modal-title">Detail Data Mutu</h5> <button type="button" class="close text-white" data-dismiss="modal">&times;</button> </div> <div class="modal-body"> <dl class="row mb-0"> <dt class="col-sm-4">Uraian</dt><dd class="col-sm-8" id="detUraianVI"></dd> <dt class="col-sm-4">Kg</dt><dd class="col-sm-8" id="detKgVI"></dd> <dt class="col-sm-4">Pallet</dt><dd class="col-sm-8" id="detPalletVI"></dd> <dt class="col-sm-4">Keterangan</dt><dd class="col-sm-8" id="detKetVI"></dd> </dl> </div> </div> </div> </div>

@include('template.script')

<script>
    @if(session('success')) Swal.fire({ icon: 'success', title: 'BERHASIL!', text: '{{ session('success') }}', showConfirmButton: false, timer: 2000 }); @endif
    @if(session('error')) Swal.fire({ icon: 'error', title: 'GAGAL!', text: '{{ session('error') }}', showConfirmButton: true }); @endif

    $(document).on('click', '.btn-reset', function(e) {
        e.preventDefault(); var form = $(this).closest('form');
        Swal.fire({ title: 'Hapus Data?', text: "Data akan di-reset.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!' }).then((result) => { if (result.isConfirmed) form.submit(); })
    });

    function bukaDetailIV(el) { $('#detUraianIV').text($(el).data('uraian')); $('#detMasukIV').text($(el).data('masuk')); $('#detPengirimanIV').text($(el).data('pengiriman')); $('#detKetIV').text($(el).data('ket')); $('#modalDetailIV').modal('show'); }
    function bukaDetailVI(el) { $('#detUraianVI').text($(el).data('uraian')); $('#detKgVI').text($(el).data('kg')); $('#detPalletVI').text($(el).data('pallet')); $('#detKetVI').text($(el).data('ket')); $('#modalDetailVI').modal('show'); }

    $(document).ready(function () {
        $('#btnInputData').click(function() {
            var tanggal = '{{ $selected_date }}'; 
            $('#modalTitle').text('Input Data Gudang & Mutu');
            $('#groupGudang').show(); $('#inputUraian').val('Di Gudang SIR'); $('#inputKeterangan').val(''); $('#inputPengiriman').val('');
            $('#in_mutu_prima').val(''); $('#in_po_pri').val(''); $('#in_ws').val(''); $('#in_kontaminasi').val(''); $('#in_repacking').val('');
            $('#inputMasuk').val('Loading...'); $('#inputPallet').val('Loading...');
            $('#modalInputData').modal('show');
            $.ajax({
                url: "{{ route('data-sir.getProductionToday') }}", type: "GET", data: { date: tanggal },
                success: function(response) { $('#inputMasuk').val(response.masuk); $('#inputPallet').val(response.pallet); },
                error: function() { $('#inputMasuk').val(0); $('#inputPallet').val(0); Swal.fire('Info', 'Gagal mengambil data produksi.', 'info'); }
            });
        });

        $(document).on('click', '.btn-edit-iv', function() {
            $('#modalTitle').text('Edit Data Gudang');
            $('#groupGudang').show();
            $('#inputUraian').val($(this).data('uraian'));
            $('#inputMasuk').val($(this).data('masuk'));
            $('#inputPengiriman').val(0); 
            $('#inputKeterangan').val($(this).data('ket'));
            $('#in_mutu_prima').val(''); $('#in_po_pri').val(''); $('#in_ws').val(''); $('#in_kontaminasi').val(''); $('#in_repacking').val('');
            $('#modalInputData').modal('show');
        });

        $(document).on('click', '.btn-edit-vi', function() {
            $('#modalTitle').text('Edit Data Mutu');
            $('#groupGudang').hide(); $('#inputUraian').val(''); 
            $('#in_mutu_prima').val(''); $('#in_po_pri').val(''); $('#in_ws').val(''); $('#in_kontaminasi').val(''); $('#in_repacking').val('');
            var uraian = $(this).data('uraian'); var kg = $(this).data('kg');
            if(uraian.includes("Mutu Prima")) $('#in_mutu_prima').val(kg);
            else if(uraian.includes("PO")) $('#in_po_pri').val(kg);
            else if(uraian.includes("WhiteSpot")) $('#in_ws').val(kg);
            else if(uraian.includes("Kontaminasi")) $('#in_kontaminasi').val(kg);
            else if(uraian.includes("Repacking")) $('#in_repacking').val(kg);
            $('#inputMasuk').val(0); $('#inputPallet').val(0); $('#inputKeterangan').val($(this).data('ket'));
            $('#modalInputData').modal('show');
        });
    });
</script>
</body>
</html>