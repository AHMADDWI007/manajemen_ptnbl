<!DOCTYPE html>
<html lang="en">
<head>
    @include('template.head')
    {{-- Tambahkan CSS untuk DataTables jika belum ada di template.head --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        .table th, .table td { vertical-align: middle; }
        .btn-add-user { float: right; }
        .dataTables_filter {
            float: right !important;
            text-align: right !important;
        }
        .dataTables_length {
            float: left !important;
        }
        .modal { z-index: 1055 !important; }

        .action-buttons form,
        .action-buttons button {
            margin-left: 5px;
        }
        .action-buttons form:first-child,
        .action-buttons button:first-child {
            margin-left: 0;
        }

    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    @include('template.navbar')
    @include('template.sidebar')

    <div class="content-wrapper">

        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-success fw-bold">Manajemen Data Pengguna</h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button type="button" class="btn btn-success btn-sm btn-add-user" data-toggle="modal" data-target="#userModal" onclick="openAddModal()">
                            <i class="fas fa-plus-circle"></i> Tambah Pengguna
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftar Pengguna Sistem</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="usersTable">
                                <thead class="text-center bg-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Jabatan</th>
                                        <th>Role</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>{{ $user->fullname }}</td>
                                        <td>{{ $user->username }}</td>
                                        <td>{{ $user->jabatan }}</td>
                                        <td class="text-center">
                                            {{-- ✅ PERBAIKAN: Array warna dan teks badge disesuaikan --}}
                                            @php
                                                $roleConfig = [
                                                    'admin' => ['color' => 'success', 'text' => 'Admin'],
                                                    'laboratorium' => ['color' => 'info', 'text' => 'Laboratorium'],
                                                    'penimbangan' => ['color' => 'primary', 'text' => 'Penimbangan'],
                                                    'pengolahan' => ['color' => 'warning', 'text' => 'Pengolahan'],
                                                    'produksi' => ['color' => 'danger', 'text' => 'Produksi'],
                                                    'penjualan' => ['color' => 'purple', 'text' => 'Penjualan'], // Asumsi warna ungu
                                                ];
                                                // Ambil konfigurasi role, default ke abu-abu jika tidak ada
                                                $config = $roleConfig[$user->role] ?? ['color' => 'secondary', 'text' => ucfirst($user->role)];
                                            @endphp
                                            <span class="badge bg-{{ $config['color'] }}">
                                                {{ $config['text'] }}
                                            </span>
                                            {{-- ✅ AKHIR PERBAIKAN BADGE --}}
                                        </td>
                                        <td class="text-center">
                                            <div class="action-buttons d-inline-flex">
                                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#userModal" onclick="openEditModal(this)"
                                                    data-id="{{ $user->id }}"
                                                    data-fullname="{{ $user->fullname }}"
                                                    data-username="{{ $user->username }}"
                                                    data-jabatan="{{ $user->jabatan }}"
                                                    data-role="{{ $user->role }}" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-danger btn-sm btn-delete" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data pengguna.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('template.footer')
</div>

{{-- Modal Tambah/Edit --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="userModalLabel">Form Pengguna</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="userForm" method="POST">
                @csrf
                <div id="method-field"></div>
                <div class="modal-body">
                    {{-- Input Nama, Username, Jabatan (Tidak Berubah) --}}
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-control" id="jabatan" name="jabatan" required>
                    </div>

                    {{-- ✅ PERBAIKAN: Opsi Role Disesuaikan --}}
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="" disabled selected>Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="laboratorium">Laboratorium</option>
                            <option value="penimbangan">Penimbangan</option>
                            <option value="pengolahan">Pengolahan</option>
                            <option value="produksi">Produksi</option>
                            <option value="penjualan">Penjualan</option>
                            {{-- Hapus <option value="user">User (Umum)</option> jika tidak dipakai lagi --}}
                        </select>
                    </div>
                    {{-- ✅ AKHIR PERBAIKAN ROLE --}}

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted" id="passwordHelp">
                            Kosongkan jika tidak ingin mengubah password.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Skrip Lainnya --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

@include('template.script')

<script>
    // Inisialisasi DataTable (Tidak berubah)
    $(document).ready(function() { /* ... */ });

    const form = document.getElementById('userForm');
    const methodField = document.getElementById('method-field');
    const passwordHelp = document.getElementById('passwordHelp');
    const modalTitle = document.getElementById('userModalLabel');
    const passwordInput = document.getElementById('password');

    function openAddModal() {
        form.reset();
        form.action = "{{ route('users.store') }}";
        methodField.innerHTML = "";
        passwordInput.required = true;
        passwordHelp.style.display = 'none';
        modalTitle.textContent = "Tambah Pengguna Baru";
        $('#userModal').modal('show');
    }

    function openEditModal(button) {
        form.reset();
        const id = button.dataset.id;
        form.action = "{{ url('users') }}/" + id;
        methodField.innerHTML = `<input type="hidden" name="_method" value="PUT">`;
        document.getElementById('fullname').value = button.dataset.fullname;
        document.getElementById('username').value = button.dataset.username;
        document.getElementById('jabatan').value = button.dataset.jabatan;
        document.getElementById('role').value = button.dataset.role;
        passwordInput.required = false;
        passwordHelp.style.display = 'block';
        modalTitle.textContent = "Edit Data Pengguna";
        $('#userModal').modal('show');
    }

    // SweetAlert konfirmasi hapus
    $(document).on('click', '.btn-delete', function() {
        let form = $(this).closest('form');
        Swal.fire({
            title: 'Yakin hapus data ini?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745', // Warna hijau
            cancelButtonColor: '#d33', // Warna merah
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Notifikasi popup jika sukses (dari session)
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 2000 // Popup hilang setelah 2 detik
        });
    @endif
</script>
</body>
</html>