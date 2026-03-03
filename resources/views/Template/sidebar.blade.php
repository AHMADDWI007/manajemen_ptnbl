<aside class="main-sidebar elevation-4 modern-sidebar">

    {{-- ==================== 1. BRAND LOGO ==================== --}}
    <a href="{{ url('/beranda') }}" class="brand-link">
        <img src="{{ asset('gambar/nb_icon.png') }}" 
             alt="Logo"
             class="brand-image img-circle elevation-3"
             style="opacity: .9; background-color: white; padding: 2px;">
        <span class="brand-text font-weight-bold text-white" style="font-size: 0.9rem; letter-spacing: 1px;">
            PT. NBL
        </span>
    </a>

    <div class="sidebar">

        {{-- ==================== 2. USER CARD ==================== --}}
        <div class="user-card mt-3 mb-3">
            <div class="d-flex align-items-center user-panel-content">
                <div class="image">
                    <img src="{{ asset('gambar/user.png') }}" 
                         class="img-circle elevation-2" 
                         alt="User"
                         style="width: 38px; height: 38px; object-fit: cover; background: white; padding: 2px;">
                </div>
                <div class="info pl-2">
                    <a href="#" class="d-block font-weight-bold text-truncate text-white" style="font-size: 0.95rem;">
                        {{ Auth::user()->name ?? 'Administrasi' }}
                    </a>
                    <div class="d-flex align-items-center mt-1 status-badge">
                        <span class="badge badge-success badge-pill" style="font-size: 0.6rem; padding: 3px 6px; background-color: #2ecc71;">
                            <i class="fas fa-circle text-white text-xs mr-1" style="font-size: 0.4rem;"></i> Online
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== SEARCH BAR ==================== --}}
        <div class="form-inline mb-3 px-2">
            <div class="input-group search-glass" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Cari Menu..." aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- ==================== MENU NAVIGASI ==================== --}}
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">

                <li class="nav-item">
                    <a href="{{ url('/beranda') }}" class="nav-link {{ request()->is('beranda') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Beranda</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-white-50 font-weight-bold mt-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                    Operasional
                </li>

                {{-- DATA LABORATORIUM (Admin, Laboratorium, Petugas, User) --}}
                @if(in_array(auth()->user()->role, ['admin', 'laboratorium', 'petugas', 'user']))
                @php
                    $isLabOpen = request()->is('hasil-uji-bokar*', 'hasil-uji-bokar-diolah*', 'hasil-uji-maturasi*', 'hasil-uji-troli*', 'hasil-uji-sir20*');
                @endphp
                <li class="nav-item {{ $isLabOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isLabOpen ? 'active-parent' : '' }}">
                        <i class="nav-icon fas fa-vial"></i>
                        <p>Data Laboratorium <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        
                        {{-- Menu yang disembunyikan HANYA untuk role 'petugas' --}}
                        @if(auth()->user()->role != 'petugas')
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-bokar') }}" class="nav-link {{ request()->is('hasil-uji-bokar*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Uji Bokar Diterima</p>
                            </a>
                        </li>
                        @endif

                        {{-- Uji Bokar Diolah: Tampil untuk SEMUA (Termasuk Petugas) --}}
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-bokar-diolah') }}" class="nav-link {{ request()->is('hasil-uji-bokar-diolah*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Uji Bokar Diolah</p>
                            </a>
                        </li>

                        {{-- Menu yang disembunyikan HANYA untuk role 'petugas' --}}
                        @if(auth()->user()->role != 'petugas')
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-maturasi') }}" class="nav-link {{ request()->is('hasil-uji-maturasi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Uji Maturasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-troli') }}" class="nav-link {{ request()->is('hasil-uji-troli*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Uji Troli</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-sir20') }}" class="nav-link {{ request()->is('hasil-uji-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Uji SIR 20</p>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif

                {{-- DATA PENGOLAHAN (Admin & Petugas serta User) --}}
                @if(in_array(auth()->user()->role, ['admin', 'petugas', 'user']))
                @php
                    $isPengolahanOpen = request()->is('pengolahan-basah*', 'maturasi*', 'bahan-proses*');
                @endphp
                <li class="nav-item {{ $isPengolahanOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isPengolahanOpen ? 'active-parent' : '' }}">
                        <i class="nav-icon fas fa-sync-alt"></i>
                        <p>Data Pengolahan <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/pengolahan-basah') }}" class="nav-link {{ request()->is('pengolahan-basah*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Pengolahan Basah</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/maturasi') }}" class="nav-link {{ request()->is('maturasi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Pengolahan Maturasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/bahan-proses') }}" class="nav-link {{ request()->is('bahan-proses*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Bahan Dalam Proses</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- DATA PRODUKSI (Admin & Petugas serta User) --}}
                @if(in_array(auth()->user()->role, ['admin', 'petugas', 'user']))
                @php
                    $isProduksiOpen = request()->is('produksi-sir20*', 'data-sir*', 'penjualan-sir20*');
                @endphp
                <li class="nav-item {{ $isProduksiOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isProduksiOpen ? 'active-parent' : '' }}">
                        <i class="nav-icon fas fa-industry"></i>
                        <p>Data Produksi <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/produksi-sir20') }}" class="nav-link {{ request()->is('produksi-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Produksi SIR 20</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/data-sir') }}" class="nav-link {{ request()->is('data-sir*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Data Gudang & Mutu</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/penjualan-sir20') }}" class="nav-link {{ request()->is('penjualan-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i> <p>Penjualan SIR 20</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- ADMINISTRASI HEADER (Hanya Admin) --}}
                @if(auth()->user()->role == 'admin')
                <li class="nav-header text-uppercase text-white-50 font-weight-bold mt-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                    Administrasi
                </li>

                <li class="nav-item">
                    <a href="{{ url('/data-pengguna') }}" class="nav-link {{ request()->is('data-pengguna*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i> <p>Data Pengguna</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('pengaturan.index') }}" class="nav-link {{ request()->is('pengaturan*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cogs"></i> <p>Pengaturan Sistem</p>
                    </a>
                </li>
                @endif

                {{-- LAPORAN & PERSETUJUAN (Admin & Petugas) --}}
                @if(in_array(auth()->user()->role, ['admin', 'petugas', 'user']))
                <li class="nav-item">
                    <a href="{{ route('laporan.index') }}" class="nav-link {{ request()->is('laporan') || request()->is('laporan/*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-invoice"></i> <p>Laporan Harian</p>
                    </a>
                </li>
                @endif

                {{-- LOGOUT --}}
                <li class="nav-item mt-4 mb-5">
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a href="javascript:void(0)" id="btn-logout" class="nav-link logout-btn w-100 text-left border-0" style="cursor: pointer;">
                        <i class="nav-icon fas fa-sign-out-alt"></i> <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <style>
        /* FIX: Agar sidebar tetap di tempat saat konten di-scroll */
        .main-sidebar {
            position: fixed !important;
            top: 0;
            bottom: 0;
            left: 0;
            height: 100vh !important;
            overflow-y: hidden;
            z-index: 1038;
        }

        /* Sidebar content area scrollable sendiri jika menu kepanjangan */
        .sidebar {
            height: calc(100vh - 57px) !important;
            overflow-y: auto !important;
        }

        /* Style Original Maswi */
        .modern-sidebar {
            background: linear-gradient(180deg, #0B6623 0%, #053b13 100%);
            box-shadow: 4px 0 15px rgba(0,0,0,0.2);
            font-family: 'Source Sans Pro', sans-serif;
        }

        .brand-link {
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
            display: flex;
            align-items: center;
            height: 57px;
            padding: 0 1rem !important;
        }

        body.sidebar-collapse .brand-text { display: none !important; }

        .user-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 10px;
            margin: 10px 10px 15px 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .user-card:hover { background: rgba(255, 255, 255, 0.15); transform: translateY(-2px); }

        body.sidebar-collapse .user-card {
            background: transparent; border: none; padding: 0; margin: 10px 0; text-align: center;
        }
        body.sidebar-collapse .user-card .image { margin-right: 0 !important; display: flex; justify-content: center; }
        body.sidebar-collapse .user-card .image img { width: 30px !important; height: 30px !important; }
        body.sidebar-collapse .user-card .info { display: none !important; }

        .search-glass .form-control-sidebar { background-color: rgba(255, 255, 255, 0.1) !important; border: none; color: #fff !important; }
        .search-glass .form-control-sidebar:focus { background-color: rgba(255, 255, 255, 0.2) !important; }
        .search-glass .btn-sidebar { background-color: rgba(255, 255, 255, 0.1) !important; border: none; color: #ccc !important; }
        
        body.sidebar-collapse .search-glass .form-control-sidebar { display: none; }
        body.sidebar-collapse .search-glass .btn-sidebar { background: transparent !important; width: 100%; }

        .nav-sidebar .nav-link { color: #ecf0f1 !important; border-radius: 8px !important; margin-bottom: 4px; transition: all 0.2s ease; white-space: nowrap; }
        .nav-sidebar .nav-link:hover { background-color: rgba(255, 255, 255, 0.1) !important; }

        .nav-sidebar > .nav-item > .nav-link.active, .nav-sidebar .nav-link.active-parent {
            background-color: #3BB143 !important; color: #ffffff !important; box-shadow: 0 2px 6px rgba(0,0,0,0.2); font-weight: 700;
        }

        .nav-sidebar .nav-treeview > .nav-item > .nav-link.active { background-color: rgba(0, 0, 0, 0.3) !important; color: #ffffff !important; font-weight: 600; }
        .nav-sidebar .nav-treeview { background-color: rgba(0, 0, 0, 0.1); border-radius: 8px; }

        .logout-btn { background-color: rgba(220, 53, 69, 0.1) !important; color: #ff6b6b !important; transition: 0.3s; }
        .logout-btn:hover { background-color: #e74c3c !important; color: white !important; }
        body.sidebar-collapse .logout-btn p { display: none; }

        /* Custom Scrollbar */
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background-color: rgba(255,255,255,0.2); border-radius: 10px; }
    </style>
</aside>

<script>
    document.getElementById('btn-logout').addEventListener('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Apakah Maswi yakin?',
            text: "Sesi kerja Anda akan diakhiri!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745', // Warna sukses (Hijau)
            cancelButtonColor: '#d33',    // Warna bahaya (Merah)
            confirmButtonText: 'Ya, Logout!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika klik Ya, jalankan form logout
                document.getElementById('logout-form').submit();
            }
        })
    });
</script>