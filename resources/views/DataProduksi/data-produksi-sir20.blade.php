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
                                                    @if($item->id_produksi_sir)
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
                                                       data-id="{{ $item->id_produksi_sir }}">
                                                        <i class="fas fa-edit text-warning mr-2"></i> {{ $item->id_produksi_sir ? 'Edit' : 'Edit' }}
                                                    </a>

                                                    <div class="dropdown-divider"></div>

                                                    {{-- RESET --}}
                                                    @if($item->id_produksi_sir)
                                                        <form action="{{ route('data-sir.destroy', $item->id_produksi_sir) }}" method="POST" class="form-reset">
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
                                                    @if($item->id_produksi_sir)
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
                                                       data-id="{{ $item->id_produksi_sir }}">
                                                        <i class="fas fa-edit text-warning mr-2"></i> {{ $item->id_produksi_sir ? 'Edit' : 'Input' }}
                                                    </a>

                                                    <div class="dropdown-divider"></div>

                                                    {{-- RESET --}}
                                                    @if($item->id_produksi_sir)
                                                        <form action="{{ route('data-sir.destroy', $item->id_produksi_sir) }}" method="POST" class="form-reset">
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
{{-- MODAL INPUT DATA --}}
<div class="modal fade" id="modalInputData" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('data-sir.store') }}" method="POST">
                @csrf
                <input type="hidden" name="tanggal" value="{{ $selected_date }}">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="pengiriman" value="0">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalTitle">Input Data Gudang & Mutu</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    
                    {{-- 1. DATA TOTAL DARI PRODUKSI --}}
                    <div class="alert alert-light border">
                        <div class="row text-center">
                            <div class="col-6 border-right">
                                <label class="mb-0 text-muted">Total Pallet</label>
                                <h4 class="font-weight-bold mb-0 text-dark" id="lblTotalPallet">0</h4>
                            </div>
                            <div class="col-6">
                                <label class="mb-0 text-muted">Total Masuk (Kg)</label>
                                <h4 class="font-weight-bold mb-0 text-dark" id="lblTotalKg">0</h4>
                            </div>
                        </div>
                        <input type="hidden" name="masuk" id="inputMasuk">
                    </div>

                    {{-- 2. PILIH GUDANG --}}
                    <div class="form-group mb-3" id="groupGudang">
                        <label>Pilih Gudang / Lokasi</label>
                        <select name="uraian" id="inputUraian" class="form-control font-weight-bold" required>
                            <option value="Di Gudang SIR">Di Gudang SIR</option>
                            <option value="Di Areal Press Bale">Di Areal Press Bale</option>
                            <option value="Di Gudang TOH 1">Di Gudang TOH 1</option>
                            <option value="Di Gudang TOH 2">Di Gudang TOH 2</option>
                        </select>
                    </div>

                    <hr>
                    {{-- 3. RINCIAN MUTU (INPUT PALLET -> AUTO KG) --}}
                    <label class="text-success font-weight-bold mb-3">Rincian Mutu (Isi Pallet):</label>
                    
                    {{-- Mutu Prima --}}
                    <div class="form-group row mb-2">
                        <label class="col-sm-4 col-form-label font-weight-bold text-success">Mutu Prima</label>
                        <div class="col-sm-3">
                            <input type="number" name="pallet_mutu_prima" id="pal_mutu_prima" class="form-control text-center font-weight-bold bg-light" readonly placeholder="Pallet">
                        </div>
                        <div class="col-sm-5">
                            <div class="input-group">
                                <input type="number" step="0.01" name="mutu_prima" id="kg_mutu_prima" class="form-control text-right font-weight-bold bg-light" readonly placeholder="Kg">
                                <div class="input-group-append"><span class="input-group-text">Kg</span></div>
                            </div>
                        </div>
                    </div>
                    {{-- PO / PRI Low (SEKARANG READONLY) --}}
                    <div class="form-group row mb-2">
                        <label class="col-sm-4 col-form-label"><small>PO / PRI Low </small></label>
                        <div class="col-sm-3">
                            <input type="number" name="pallet_po_pri" id="pal_po_pri" class="form-control text-center bg-light" readonly placeholder="Auto">
                        </div>
                        <div class="col-sm-5">
                            <div class="input-group">
                                <input type="number" step="0.01" name="po_pri" id="kg_po_pri" class="form-control text-right bg-light" readonly placeholder="Otomatis">
                                <div class="input-group-append"><span class="input-group-text">Kg</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Loop Inputan Grade Cacat --}}
                    @php
                        $fields = [
                           
                            ['label' => 'WhiteSpot (WS)', 'id' => 'ws'],
                            ['label' => 'Kontaminasi', 'id' => 'kontaminasi'],
                            ['label' => 'Repacking', 'id' => 'repacking']
                        ];
                    @endphp

                    @foreach($fields as $f)
                    <div class="form-group row mb-2">
                        <label class="col-sm-4 col-form-label"><small>{{ $f['label'] }}</small></label>
                        <div class="col-sm-3">
                            <input type="number" name="pallet_{{ $f['id'] }}" id="pal_{{ $f['id'] }}" class="form-control text-center input-pallet" placeholder="Pallet">
                        </div>
                        <div class="col-sm-5">
                            <div class="input-group">
                                <input type="number" step="0.01" name="{{ $f['id'] }}" id="kg_{{ $f['id'] }}" class="form-control text-right bg-white" readonly placeholder="Otomatis">
                                <div class="input-group-append"><span class="input-group-text">Kg</span></div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <hr>
                    
                    {{-- KETERANGAN --}}
                    <div class="form-group mb-3"> 
                        <label>Keterangan</label> 
                        <select id="selectKeterangan" class="form-control mb-2 font-weight-bold">
                            <option value="PTNBL">PTNBL</option>
                            <option value="PTPN4">PTPN4</option>
                            <option value="Custom">Custom (Isi Manual)</option>
                        </select>
                        <input type="text" name="keterangan" id="inputKeterangan" class="form-control" placeholder="Isi keterangan manual..." style="display:none;"> 
                    </div>

                </div>
                <div class="modal-footer"> <button type="submit" class="btn btn-primary">Simpan Data</button> </div>
            </form>
        </div>
    </div>
</div>

@include('template.script')

<script>
    // SweetAlert
    @if(session('success')) Swal.fire({ icon: 'success', title: 'BERHASIL!', text: '{{ session('success') }}', showConfirmButton: false, timer: 2000 }); @endif
    @if(session('error')) Swal.fire({ icon: 'error', title: 'GAGAL!', text: '{{ session('error') }}', showConfirmButton: true }); @endif



    var globalTotalPallet = 0;
const KG_PER_PALLET = 1260;

$(document).ready(function () {

    function hitungMutu() {
        var p_low = parseInt($('#pal_po_pri').val()) || 0;
        var p_ws = parseInt($('#pal_ws').val()) || 0;
        var p_kontam = parseInt($('#pal_kontaminasi').val()) || 0;
        var p_repack = parseInt($('#pal_repacking').val()) || 0;

        var totalCacat = p_low + p_ws + p_kontam + p_repack;
        var sisaPrima = globalTotalPallet - totalCacat;
        if (sisaPrima < 0) sisaPrima = 0;

        $('#pal_mutu_prima').val(sisaPrima);
        $('#kg_mutu_prima').val(sisaPrima * KG_PER_PALLET);

        $('#kg_po_pri').val(p_low * KG_PER_PALLET);
        $('#kg_ws').val(p_ws * KG_PER_PALLET);
        $('#kg_kontaminasi').val(p_kontam * KG_PER_PALLET);
        $('#kg_repacking').val(p_repack * KG_PER_PALLET);
    }

    $('.input-pallet').on('input keyup', hitungMutu);

    // =========================
    // TOMBOL INPUT DATA (FIX)
    // =========================
    $('#btnInputData').on('click', function () {

        var tanggal = $('#filter_tanggal').val();

        if (!tanggal) {
            Swal.fire('Error', 'Tanggal belum dipilih', 'error');
            return;
        }

        // 🔥 UPDATE TANGGAL KE INPUT HIDDEN MODAL
        $('input[name="tanggal"]').val(tanggal);

        // RESET SEMUA INPUT MUTU
        $('.input-pallet').val('');
        $('#pal_po_pri').val(0);
        $('#pal_mutu_prima').val(0);
        $('#kg_mutu_prima').val(0);
        $('#kg_po_pri').val(0);
        $('#kg_ws').val(0);
        $('#kg_kontaminasi').val(0);
        $('#kg_repacking').val(0);

        $.ajax({
            url: "{{ route('data-sir.getProductionToday') }}",
            type: "GET",
            data: { date: tanggal },
            success: function (res) {

                console.log('RESPON SERVER:', res);

                globalTotalPallet = parseInt(res.total_target_pallet) || 0;

                $('#lblTotalPallet').text(globalTotalPallet);
                $('#lblTotalKg').text(
                    new Intl.NumberFormat('id-ID').format(res.total_target_kg || 0)
                );

                $('#inputMasuk').val(res.masuk_kg || 0);

                // PO / PRI Low dari LAB
                $('#pal_po_pri').val(res.low_pri || 0);

                hitungMutu();

                $('#modalInputData').modal('show');
            },
            error: function () {
                Swal.fire('Error', 'Gagal mengambil data produksi', 'error');
            }
        });
    });

        // --- TOMBOL EDIT MUTU ---
        $(document).on('click', '.btn-edit-vi', function() {
            $('#groupGudang').hide(); 
            $('#inputUraian').val(''); 
            $('#editId').val($(this).data('id'));

            var uraian = $(this).data('uraian');
            var palletDB = parseInt($(this).data('pallet')) || 0;
            
            $('.input-pallet').val('');
            
            if(uraian.includes("Mutu Prima")) {
                globalTotalPallet = palletDB;
                hitungMutu();
            } else {
                if(uraian.includes("PO")) $('#pal_po_pri').val(palletDB);
                else if(uraian.includes("WhiteSpot")) $('#pal_ws').val(palletDB);
                else if(uraian.includes("Kontaminasi")) $('#pal_kontaminasi').val(palletDB);
                else if(uraian.includes("Repacking")) $('#pal_repacking').val(palletDB);
                
                hitungMutu();
            }

            setupKeterangan($(this).data('ket'));
            $('#modalInputData').modal('show');
        });

        function setupKeterangan(ket) {
            if (ket === 'PTNBL' || ket === 'PTPN4') {
                $('#selectKeterangan').val(ket);
                $('#inputKeterangan').val(ket).hide();
            } else {
                $('#selectKeterangan').val('Custom');
                $('#inputKeterangan').val(ket).show();
            }
        }

        $('#selectKeterangan').change(function() {
            var val = $(this).val();
            if(val === 'Custom') $('#inputKeterangan').val('').show().focus();
            else $('#inputKeterangan').val(val).hide();
        });

        $(document).on('click', '.btn-reset', function(e) {
            e.preventDefault(); var form = $(this).closest('form');
            Swal.fire({ title: 'Hapus Data?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya' }).then((result) => { if (result.isConfirmed) form.submit(); })
        });
        window.bukaDetailIV = function(el) { $('#detUraianIV').text($(el).data('uraian')); $('#detMasukIV').text($(el).data('masuk')); $('#detPengirimanIV').text($(el).data('pengiriman')); $('#detKetIV').text($(el).data('ket')); $('#modalDetailIV').modal('show'); }
        window.bukaDetailVI = function(el) { $('#detUraianVI').text($(el).data('uraian')); $('#detKgVI').text($(el).data('kg')); $('#detPalletVI').text($(el).data('pallet')); $('#detKetVI').text($(el).data('ket')); $('#modalDetailVI').modal('show'); }
    });
</script>
</body>
</html>