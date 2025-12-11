<?php
if (!isset($conn)) { require_once '../../src/config/database.php'; }

$id_user = $_SESSION['user_id'] ?? 0;
$q_user = $conn->query("SELECT d.*, u.email FROM dokter d JOIN users u ON d.id_user = u.id_user WHERE u.id_user = '$id_user'");
$user_login = $q_user->fetch_assoc();

$page = basename($_SERVER['PHP_SELF']);
?>

<aside class="w-64 h-screen bg-white border-r border-slate-200 flex flex-col justify-between flex-shrink-0 sticky top-0 z-40">
    <div>
        <div class="p-6 flex items-center gap-3">
            <div class="w-10 h-10 flex">
                <img src="../img/puskesmas.svg" alt="Logo Puskesmas" class="w-full h-full object-contain drop-shadow-lg">
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-800">Puskesmas</h1>
                <p class="text-xs text-slate-500">Panel Dokter</p>
            </div>
        </div>

        <nav class="mt-4 px-4 space-y-1">
            <?php
            $menus = [
                'dashboard.php' => ['Dashboard', 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                'daftar_pasien.php' => ['Daftar Pasien', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                'rekam_medis.php' => ['Rekam Medis', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                'profil_dokter.php' => ['Profil Dokter', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];

            foreach ($menus as $file => $data):
                $active = ($page == $file) ? 'bg-emerald-500 text-white shadow-emerald-200 shadow-md' : 'text-slate-500 hover:bg-slate-50';
            ?>
            <a href="<?= $file ?>" class="flex items-center px-4 py-3 rounded-xl text-sm font-medium transition <?= $active ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $data[1] ?>"></path></svg>
                <?= $data[0] ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="p-4 m-4 bg-slate-50 rounded-xl border border-slate-100">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold border border-emerald-200">
                <?= substr($user_login['nama_dokter'] ?? 'Dr', 4, 1) ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-sm font-semibold text-slate-700 truncate"><?= $user_login['nama_dokter'] ?? 'Dokter' ?></p>
                <p class="text-[10px] text-slate-400 uppercase">NIP: <?= $user_login['kode_dokter'] ?? '-' ?></p>
            </div>
        </div>
        <button onclick="toggleLogoutModal(true)" class="flex items-center justify-center w-full py-2 text-xs text-red-500 font-medium bg-white border border-red-100 rounded-lg hover:bg-red-50 transition cursor-pointer shadow-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Keluar
        </button>
    </div>
</aside>

<div id="logoutModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center transform scale-100 transition-transform">
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-2">Konfirmasi Keluar</h3>
        <p class="text-slate-500 text-sm mb-6">Apakah Anda yakin ingin keluar dari aplikasi?</p>
        <div class="flex gap-3">
            <button onclick="toggleLogoutModal(false)" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-medium rounded-xl hover:bg-slate-200 transition">Batal</button>
            
            <a href="../logout.php" class="flex-1 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 transition shadow-lg shadow-red-200">Ya, Keluar</a>
        </div>
    </div>
</div>

<script>
function toggleLogoutModal(show) {
    const modal = document.getElementById('logoutModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}
</script>