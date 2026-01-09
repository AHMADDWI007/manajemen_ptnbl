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
        {{-- Container user-card akan otomatis menyesuaikan saat collapse --}}
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

                {{-- 1. BERANDA --}}
                <li class="nav-item">
                    <a href="{{ url('/beranda') }}" class="nav-link {{ request()->is('beranda') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Beranda</p>
                    </a>
                </li>

                <li class="nav-header text-uppercase text-white-50 font-weight-bold mt-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                    Operasional
                </li>

                {{-- 2. DATA LABORATORIUM --}}
                @php
                    $isLabOpen = request()->is('hasil-uji-bokar*', 'hasil-uji-bokar-diolah*', 'hasil-uji-maturasi*', 'hasil-uji-troli*', 'hasil-uji-sir20*');
                @endphp
                <li class="nav-item {{ $isLabOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isLabOpen ? 'active-parent' : '' }}">
                        <i class="nav-icon fas fa-vial"></i>
                        <p>
                            Data Laboratorium
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-bokar') }}" class="nav-link {{ request()->is('hasil-uji-bokar*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Bokar Diterima</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-bokar-diolah') }}" class="nav-link {{ request()->is('hasil-uji-bokar-diolah*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Bokar Diolah</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-maturasi') }}" class="nav-link {{ request()->is('hasil-uji-maturasi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Maturasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-troli') }}" class="nav-link {{ request()->is('hasil-uji-troli*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji Troli</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/hasil-uji-sir20') }}" class="nav-link {{ request()->is('hasil-uji-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Uji SIR 20</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 3. DATA PENGOLAHAN --}}
                @php
                    $isPengolahanOpen = request()->is('pengolahan-basah*', 'maturasi*', 'bahan-proses*');
                @endphp
                <li class="nav-item {{ $isPengolahanOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isPengolahanOpen ? 'active-parent' : '' }}">
                        <i class="nav-icon fas fa-sync-alt"></i>
                        <p>
                            Data Pengolahan
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/pengolahan-basah') }}" class="nav-link {{ request()->is('pengolahan-basah*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pengolahan Basah</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/maturasi') }}" class="nav-link {{ request()->is('maturasi*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pengolahan Maturasi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/bahan-proses') }}" class="nav-link {{ request()->is('bahan-proses*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Bahan Dalam Proses</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 4. DATA PRODUKSI --}}
                @php
                    $isProduksiOpen = request()->is('produksi-sir20*', 'data-sir*', 'penjualan-sir20*');
                @endphp
                <li class="nav-item {{ $isProduksiOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isProduksiOpen ? 'active-parent' : '' }}">
                        <i class="nav-icon fas fa-industry"></i>
                        <p>
                            Data Produksi
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/produksi-sir20') }}" class="nav-link {{ request()->is('produksi-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Produksi SIR 20</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/data-sir') }}" class="nav-link {{ request()->is('data-sir*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Gudang & Mutu</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/penjualan-sir20') }}" class="nav-link {{ request()->is('penjualan-sir20*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Penjualan SIR 20</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header text-uppercase text-white-50 font-weight-bold mt-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                    Administrasi
                </li>

                {{-- 5. DATA PENGGUNA --}}
                <li class="nav-item">
                    <a href="{{ url('/data-pengguna') }}" class="nav-link {{ request()->is('data-pengguna*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Data Pengguna</p>
                    </a>
                </li>

                {{-- 6. DATA LAINNYA --}}
                @php
                    $isLainnyaOpen = request()->is('data-lainnya*');
                @endphp
                <li class="nav-item {{ $isLainnyaOpen ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $isLainnyaOpen ? 'active-parent' : '' }}">
                        <i class="nav-icon fas fa-archive"></i>
                        <p>
                            Data Lainnya
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('/data-lainnya/truck') }}" class="nav-link {{ request()->is('data-lainnya/truck*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Data Truck</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('/data-lainnya/audit-trail') }}" class="nav-link {{ request()->is('data-lainnya/audit-trail*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Audit Trail</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- 7. LAPORAN & PERSETUJUAN --}}
                <li class="nav-item">
                    <a href="{{ url('/laporan-harian') }}" class="nav-link {{ request()->is('laporan-harian*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Laporan Harian</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/persetujuan') }}" class="nav-link {{ request()->is('persetujuan*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Persetujuan</p>
                    </a>
                </li>

                {{-- LOGOUT --}}
                <li class="nav-item mt-4 mb-5">
                    <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Yakin ingin logout?');">
                        @csrf
                        <button type="submit" class="nav-link logout-btn w-100 text-left border-0" style="cursor: pointer;">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Logout</p>
                        </button>
                    </form>
                </li>

            </ul>
        </nav>
    </div>

    {{-- ====================== STYLE CSS (PERBAIKAN RESPONSIVE) ====================== --}}
    <style>
        /* 1. Base Sidebar Styling */
        .modern-sidebar {
            background: linear-gradient(180deg, #0B6623 0%, #053b13 100%);
            box-shadow: 4px 0 15px rgba(0,0,0,0.2);
            font-family: 'Source Sans Pro', sans-serif;
        }

        /* 2. Brand Link (Logo) */
        .brand-link {
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
            display: flex;
            align-items: center;
            height: 57px;
            padding: 0 1rem !important;
        }
        .brand-link .brand-image {
            float: left;
            margin-right: 10px;
            margin-top: 0;
            max-height: 33px;
        }
        /* Saat collapsed, sembunyikan teks logo */
        body.sidebar-collapse .brand-text {
            display: none !important;
        }

        /* 3. User Card - PERBAIKAN UTAMA SAAT COLLAPSE */
        .user-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 10px;
            margin: 10px 10px 15px 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        /* Kondisi Normal (Terbuka) */
        .user-card:hover { 
            background: rgba(255, 255, 255, 0.15); 
            transform: translateY(-2px); 
        }

        /* Kondisi Tertutup (Sidebar Collapse) */
        body.sidebar-collapse .user-card {
            background: transparent;
            border: none;
            padding: 0;
            margin: 10px 0;
            text-align: center;
        }
        body.sidebar-collapse .user-card .image {
            margin-right: 0 !important;
            display: flex;
            justify-content: center;
        }
        body.sidebar-collapse .user-card .image img {
            width: 30px !important; /* Ukuran gambar mengecil sedikit */
            height: 30px !important;
        }
        body.sidebar-collapse .user-card .info {
            display: none !important; /* Sembunyikan teks nama & status */
        }

        /* 4. Search Bar */
        .search-glass .form-control-sidebar {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: none;
            color: #fff !important;
        }
        .search-glass .form-control-sidebar:focus { 
            background-color: rgba(255, 255, 255, 0.2) !important; 
        }
        .search-glass .btn-sidebar { 
            background-color: rgba(255, 255, 255, 0.1) !important; 
            border: none; 
            color: #ccc !important; 
        }
        .search-glass .form-control::placeholder { 
            color: rgba(255,255,255,0.5); 
        }
        /* Sembunyikan input search saat collapse (bawaan AdminLTE biasanya handle ini, tapi kita pertegas) */
        body.sidebar-collapse .search-glass .form-control-sidebar {
            display: none;
        }
        body.sidebar-collapse .search-glass {
            background: transparent;
            border: none;
            padding: 0;
            justify-content: center;
        }
        body.sidebar-collapse .search-glass .btn-sidebar {
            background: transparent !important;
            width: 100%;
        }

        /* 5. Menu Styling */
        .nav-sidebar .nav-link {
            color: #ecf0f1 !important;
            border-radius: 8px !important;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            white-space: nowrap; /* Mencegah teks turun baris saat animasi collapse */
        }
        .nav-sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Menu Induk Aktif */
        .nav-sidebar > .nav-item > .nav-link.active,
        .nav-sidebar .nav-link.active-parent {
            background-color: #3BB143 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            font-weight: 700;
        }

        /* Sub-Menu Aktif */
        .nav-sidebar .nav-treeview > .nav-item > .nav-link.active {
            background-color: rgba(0, 0, 0, 0.3) !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        /* Submenu Container */
        .nav-sidebar .nav-treeview {
            background-color: rgba(0, 0, 0, 0.1); 
            border-radius: 8px;
        }

        /* PERBAIKAN PADDING SAAT COLLAPSE */
        /* Saat normal */
        .nav-sidebar .nav-treeview > .nav-item > .nav-link {
            padding-left: 25px;
        }
        /* Saat collapse (AdminLTE akan menyembunyikan treeview, tapi jika di-hover muncul) */
        body.sidebar-collapse .nav-sidebar .nav-treeview > .nav-item > .nav-link {
            padding-left: 20px; /* Reset padding agar ikon sub-menu pas */
        }

        /* 6. Logout Button */
        .logout-btn {
            background-color: rgba(220, 53, 69, 0.1) !important;
            color: #ff6b6b !important;
            transition: 0.3s;
        }
        .logout-btn:hover { 
            background-color: #e74c3c !important; 
            color: white !important; 
        }
        /* Logout saat collapse -> hanya ikon */
        body.sidebar-collapse .logout-btn p {
            display: none;
        }
        body.sidebar-collapse .logout-btn {
            text-align: center;
            padding-left: 0;
        }

        /* Scrollbar */
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { 
            background-color: rgba(255,255,255,0.2); 
            border-radius: 10px; 
        }
    </style>
</aside>