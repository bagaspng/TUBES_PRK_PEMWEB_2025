<?php
// Sidebar Admin Panel
?>

<style>
    .nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 0.75rem;
        color: #4b5563; 
        font-size: 0.875rem;
        transition: 0.2s;
    }
    .nav-item:hover {
        background: #f3f4f6; 
    }
    .nav-active {
        background: #ecfdf5 !important; 
        color: #10b981 !important;      
        font-weight: 600;
    }
    .menu-title {
        margin-top: 1rem;
        margin-bottom: 0.25rem;
        padding-left: 0.75rem;
        font-size: 0.65rem;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">

    <!-- HEADER -->
    <div class="px-6 py-6 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                <span class="text-[#45BC7D] font-bold text-lg">P</span>
            </div>
            <div>
                <p class="text-xs text-gray-400">Puskesmas</p>
                <p class="text-sm font-semibold text-gray-800">Panel Admin</p>
            </div>
        </div>
    </div>

    <!-- NAVIGATION -->
    <nav class="flex-1 px-3 py-4 space-y-1 text-sm">

        <!-- Dashboard -->
        <a href="dashboard.php"
            class="nav-item <?= ($active === 'dashboard') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-green-100 text-green-500 flex items-center justify-center">D</span>
            Dashboard
        </a>

        <!-- Master Data -->
        <p class="menu-title">Master Data</p>

        <a href="data_dokter.php"
            class="nav-item <?= ($active === 'dokter') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-blue-100 text-blue-500 flex items-center justify-center">Dr</span>
            Data Dokter
        </a>

        <a href="data_poli.php"
            class="nav-item <?= ($active === 'poli') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-purple-100 text-purple-500 flex items-center justify-center">Pl</span>
            Data Poli
        </a>

        <a href="jadwal_praktik.php"
            class="nav-item <?= ($active === 'jadwal') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-orange-100 text-orange-500 flex items-center justify-center">Jd</span>
            Jadwal Praktik
        </a>

        <a href="data_pasien.php"
            class="nav-item <?= ($active === 'pasien') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-pink-100 text-pink-500 flex items-center justify-center">Ps</span>
            Data Pasien
        </a>

        <!-- Informasi -->
        <p class="menu-title">Informasi</p>

        <a href="pengumuman.php"
            class="nav-item <?= ($active === 'pengumuman') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-red-100 text-red-500 flex items-center justify-center">Pg</span>
            Pengumuman
        </a>

        <a href="artikel.php"
            class="nav-item <?= ($active === 'artikel') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-yellow-100 text-yellow-600 flex items-center justify-center">Ar</span>
            Artikel Kesehatan
        </a>

        <!-- Pengaturan -->
        <p class="menu-title">Pengaturan</p>

        <a href="profil.php"
            class="nav-item <?= ($active === 'profil') ? 'nav-active' : '' ?>">
            <span class="w-6 h-6 rounded-md bg-gray-100 text-gray-600 flex items-center justify-center">Pr</span>
            Profil
        </a>
    </nav>

    <!-- LOGOUT -->
    <div class="px-4 py-4 border-t border-gray-100">
        <form action="../logout.php" method="post">
            <button type="submit"
                class="w-full px-3 py-2 rounded-xl text-red-600 hover:bg-red-50 text-sm">
                Logout
            </button>
        </form>
    </div>
</aside>
