<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../src/config/database.php';

$page = $_GET['page'] ?? 'dashboard';
$user_id = $_SESSION['user_id'];
$message = "";

$daftar_spesialis = [
    "Dokter Umum", "Dokter Gigi", "Spesialis Penyakit Dalam (Sp.PD)", "Spesialis Anak (Sp.A)", 
    "Spesialis Bedah (Sp.B)", "Spesialis Kandungan (Sp.OG)", "Spesialis Saraf (Sp.S)", 
    "Spesialis Jantung (Sp.JP)", "Spesialis Mata (Sp.M)", "Spesialis THT (Sp.THT-KL)", 
    "Spesialis Paru (Sp.P)", "Spesialis Kulit & Kelamin (Sp.KK)", "Spesialis Jiwa (Sp.KJ)"
];

$daftar_rs = [
    "RSUP Dr. Cipto Mangunkusumo (Jakarta)", "RS Harapan Kita (Jakarta)", "RS Kanker Dharmais (Jakarta)",
    "RSUP Dr. Hasan Sadikin (Bandung)", "RSUP Dr. Kariadi (Semarang)", "RSUP Dr. Sardjito (Yogyakarta)",
    "RSUD Dr. Soetomo (Surabaya)", "RSUP H. Adam Malik (Medan)", "RSUP Dr. M. Djamil (Padang)",
    "RSUD Arifin Achmad (Pekanbaru)", "RSUD Dr. H. Abdul Moeloek (Lampung)", "RSUD Ulin (Banjarmasin)",
    "RSUP Dr. Wahidin Sudirohusodo (Makassar)", "RSUP Sanglah (Denpasar)", "RSUD Jayapura (Papua)"
];
sort($daftar_rs);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan_pemeriksaan') {
        $id_antrian = $_POST['id_antrian'];
        $td = $_POST['td'] ?? '-'; $suhu = $_POST['suhu'] ?? '-'; $nadi = $_POST['nadi'] ?? '-'; $rr = $_POST['rr'] ?? '-';
        $keluhan = $_POST['keluhan']; $diagnosa = $_POST['diagnosa']; $resep = $_POST['resep']; $tindakan = $_POST['tindakan'];

        $q = $conn->query("SELECT a.id_pasien, d.id_poli, d.id_dokter FROM antrian a JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal JOIN dokter d ON j.id_dokter = d.id_dokter WHERE a.id_antrian = $id_antrian");
        $data = $q->fetch_assoc();

        if ($data) {
            $stmt_rm = $conn->prepare("INSERT INTO rekam_medis (id_pasien, id_dokter, id_poli, id_antrian, tanggal_kunjungan, tensi, suhu, nadi, rr, keluhan, diagnosa, resep_obat, pemeriksaan, created_at) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt_rm->bind_param("iiiisssssssss", $data['id_pasien'], $data['id_dokter'], $data['id_poli'], $id_antrian, $td, $suhu, $nadi, $rr, $keluhan, $diagnosa, $resep, $tindakan);
            
            if ($stmt_rm->execute()) {
                $conn->query("UPDATE antrian SET status = 'selesai' WHERE id_antrian = $id_antrian");
                $message = "Pemeriksaan selesai dan data berhasil disimpan.";
                $page = 'dashboard';
            }
        }
    }

    if ($action === 'update_profil') {
        $nama_baru = $_POST['nama_dokter'];
        $nip_baru  = $_POST['kode_dokter']; 
        $spesialis_baru = $_POST['spesialis'];
        $hp_baru = $_POST['no_hp'];
        $email_baru = $_POST['email'];

        $stmt_up = $conn->prepare("UPDATE dokter SET nama_dokter = ?, kode_dokter = ?, spesialis = ?, no_hp = ? WHERE id_user = ?");
        $stmt_up->bind_param("ssssi", $nama_baru, $nip_baru, $spesialis_baru, $hp_baru, $user_id);
        
        $stmt_user = $conn->prepare("UPDATE users SET email = ? WHERE id_user = ?");
        $stmt_user->bind_param("si", $email_baru, $user_id);

        if ($stmt_up->execute() && $stmt_user->execute()) {
            $message = "Profil berhasil diperbarui.";
            $page = 'profil';
        } else {
            $message = "Gagal update: " . $conn->error;
        }
    }

    if ($action === 'logout') {
        session_destroy();
        header("Location: ../login.php");
        exit;
    }
}

$query_info = "SELECT dokter.*, users.email FROM dokter JOIN users ON dokter.id_user = users.id_user WHERE dokter.id_user = ?";
$stmt = $conn->prepare($query_info);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$dokter = $stmt->get_result()->fetch_assoc();

if (!$dokter) {
    $dokter = ['nama_dokter' => 'dr. User', 'kode_dokter' => '-', 'spesialis' => 'Umum', 'no_hp' => '-', 'email' => '-'];
}

function getBadge($status) {
    if ($status == 'menunggu') return 'bg-yellow-100 text-yellow-700';
    if ($status == 'diperiksa') return 'bg-emerald-100 text-emerald-700';
    return 'bg-gray-100 text-gray-600';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puskesmas Panel Dokter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #FAFAFA; }
        .nav-active { background-color: #45BC7D; color: white; box-shadow: 0 4px 10px rgba(69, 188, 125, 0.2); }
        .nav-item { color: #64748B; }
        .nav-item:hover { background-color: #F0FDF4; color: #45BC7D; }
        .btn-primary { background-color: #45BC7D; color: white; }
        .btn-primary:hover { background-color: #3AA668; }
        .profil-card { padding: 1rem; border-radius: 0.75rem; border: 1px solid #E5E7EB; background-color: white; }
        .input-view { background: none; border: none; padding: 0; width: 100%; font-size: 1rem; font-weight: 500; color: #1f2937; }
        .input-view:focus { outline: none; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <aside class="w-72 bg-white border-r border-gray-100 flex flex-col z-20 flex-shrink-0">
        <div class="p-8 flex items-center gap-4">
            <div class="w-10 h-10 bg-[#45BC7D] rounded-xl flex items-center justify-center text-white text-xl shadow-lg">
                <i class="fas fa-plus"></i>
            </div>
            <div>
                <h1 class="font-bold text-gray-800 text-lg">Puskesmas</h1>
                <p class="text-xs text-gray-500">Panel Dokter</p>
            </div>
        </div>
        <nav class="flex-1 px-6 space-y-2 py-4 overflow-y-auto">
            <?php
            $menus = [
                'dashboard' => ['icon' => 'fa-th-large', 'label' => 'Dashboard'],
                'daftar_pasien' => ['icon' => 'fa-users', 'label' => 'Daftar Pasien'],
                'pemeriksaan' => ['icon' => 'fa-stethoscope', 'label' => 'Pemeriksaan', 'hidden' => true],
                'detail_pasien' => ['icon' => 'fa-file-alt', 'label' => 'Detail Pasien', 'hidden' => true],
                'rekam_medis' => ['icon' => 'fa-file-medical', 'label' => 'Rekam Medis'],
                'rujukan' => ['icon' => 'fa-file-invoice', 'label' => 'Cetak Rujukan'],
                'profil' => ['icon' => 'fa-user-circle', 'label' => 'Profil Dokter']
            ];
            foreach ($menus as $k => $m) {
                if(isset($m['hidden'])) continue;
                $cls = ($page == $k) ? 'nav-active' : 'nav-item';
                echo "<a href='dashboard.php?page=$k' class='flex items-center gap-4 px-4 py-3.5 rounded-xl text-sm font-medium transition-all $cls'>
                        <i class='fas {$m['icon']} w-5 text-center'></i> {$m['label']}
                      </a>";
            }
            ?>
        </nav>
        <div class="p-6 border-t border-gray-100">
            <form method="POST">
                <input type="hidden" name="action" value="logout">
                <button class="w-full flex items-center justify-center gap-2 text-red-500 hover:bg-red-50 py-3 rounded-xl transition-colors text-sm font-medium">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="h-20 bg-white border-b border-gray-100 flex items-center justify-between px-8 flex-shrink-0">
            <h2 class="text-xl font-bold text-gray-800 capitalize">
                <?= str_replace('_', ' ', $page == 'detail_pasien' ? 'Detail Pasien' : $page) ?>
            </h2>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 font-bold">
                    <?= substr($dokter['nama_dokter'], 4, 1) ?>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-sm font-bold text-gray-800"><?= $dokter['nama_dokter'] ?></p>
                    <p class="text-xs text-gray-500">NIP: <?= $dokter['kode_dokter'] ?></p>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
        <div class="bg-emerald-50 border-l-4 border-[#45BC7D] text-emerald-800 p-4 m-8 mb-0 rounded shadow-sm flex justify-between animate-bounce">
            <p class="text-sm font-medium"><i class="fas fa-check-circle mr-2"></i> <?= $message ?></p>
            <button onclick="this.parentElement.style.display='none'"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>

        <div class="flex-1 overflow-y-auto p-8">

            <?php if ($page === 'dashboard'): ?>
                <?php 
                $today = date('Y-m-d');
                $res = $conn->query("SELECT COUNT(*) as total, SUM(status='menunggu') as t, SUM(status='diperiksa') as p, SUM(status='selesai') as s FROM antrian WHERE DATE(waktu_daftar)='$today'")->fetch_assoc();
                ?>
                <div class="grid grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm"><div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="fas fa-users"></i></div><h3 class="text-3xl font-bold text-gray-800"><?= $res['total'] ?></h3><p class="text-gray-500 text-sm">Total Pasien</p></div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm"><div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="far fa-clock"></i></div><h3 class="text-3xl font-bold text-gray-800"><?= $res['t'] ?></h3><p class="text-gray-500 text-sm">Menunggu</p></div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm"><div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="fas fa-briefcase-medical"></i></div><h3 class="text-3xl font-bold text-gray-800"><?= $res['p'] ?></h3><p class="text-gray-500 text-sm">Sedang Diperiksa</p></div>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm"><div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="far fa-check-circle"></i></div><h3 class="text-3xl font-bold text-gray-800"><?= $res['s'] ?></h3><p class="text-gray-500 text-sm">Selesai Hari Ini</p></div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <div class="flex justify-between items-center mb-6"><h3 class="font-bold text-lg text-gray-800">Daftar Pasien Hari Ini</h3><a href="?page=daftar_pasien" class="text-[#45BC7D] text-sm font-bold">Lihat Semua</a></div>
                    <table class="w-full text-left"><thead class="text-gray-400 text-xs uppercase font-bold border-b border-gray-50"><tr><th class="py-4">No</th><th class="py-4">Nama</th><th class="py-4">Poli</th><th class="py-4">Status</th><th class="py-4 text-right">Aksi</th></tr></thead><tbody class="divide-y divide-gray-50">
                        <?php $q=$conn->query("SELECT a.*, p.nama_lengkap, po.nama_poli FROM antrian a JOIN pasien p ON a.id_pasien=p.id_pasien JOIN poli po ON po.id_poli=1 WHERE DATE(a.waktu_daftar)='$today' ORDER BY a.nomor_antrian ASC LIMIT 5"); while($r=$q->fetch_assoc()): ?>
                        <tr>
                            <td class="py-4 font-bold">A-<?= $r['nomor_antrian'] ?></td><td class="py-4"><?= $r['nama_lengkap'] ?></td><td class="py-4 text-gray-500"><?= $r['nama_poli'] ?></td><td class="py-4"><span class="px-3 py-1 rounded text-xs font-bold <?= getBadge($r['status']) ?> capitalize"><?= $r['status'] ?></span></td>
                            <td class="py-4 text-right"><?php if($r['status']!='selesai'): ?><a href="?page=pemeriksaan&id=<?= $r['id_antrian'] ?>" class="btn-primary px-4 py-2 rounded-lg text-xs font-bold shadow-sm">Periksa</a><?php else: ?><a href="?page=detail_pasien&id=<?= $r['id_antrian'] ?>" class="text-[#45BC7D] text-xs font-bold hover:underline">Detail</a><?php endif; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody></table>
                </div>

            <?php elseif ($page === 'daftar_pasien'): ?>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <form class="flex gap-4 mb-6"><input type="hidden" name="page" value="daftar_pasien"><div class="flex-1 relative"><i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i><input name="q" class="w-full pl-10 pr-4 py-3 bg-gray-50 border-0 rounded-xl text-sm focus:ring-2 focus:ring-[#45BC7D]" placeholder="Cari pasien..."></div><button class="btn-primary px-6 rounded-xl font-bold shadow-lg shadow-emerald-100">Cari</button></form>
                    <table class="w-full text-left"><thead class="text-gray-400 text-xs uppercase font-bold border-b border-gray-50"><tr><th class="py-4">No</th><th class="py-4">Nama</th><th class="py-4">Umur</th><th class="py-4">Poli</th><th class="py-4">Status</th><th class="py-4 text-right">Aksi</th></tr></thead><tbody>
                        <?php $kw=$_GET['q']??''; $today=date('Y-m-d'); $q=$conn->query("SELECT a.*, p.nama_lengkap, p.tanggal_lahir, po.nama_poli FROM antrian a JOIN pasien p ON a.id_pasien=p.id_pasien JOIN poli po ON po.id_poli=1 WHERE DATE(a.waktu_daftar)='$today' AND p.nama_lengkap LIKE '%$kw%' ORDER BY a.nomor_antrian ASC"); while($r=$q->fetch_assoc()): $umur=date_diff(date_create($r['tanggal_lahir']), date_create('today'))->y; ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50"><td class="py-4 font-bold">A-<?= $r['nomor_antrian'] ?></td><td class="py-4"><?= $r['nama_lengkap'] ?></td><td class="py-4 text-gray-500"><?= $umur ?> th</td><td class="py-4 text-gray-500"><?= $r['nama_poli'] ?></td><td class="py-4"><span class="px-3 py-1 rounded text-xs font-bold <?= getBadge($r['status']) ?>"><?= $r['status'] ?></span></td><td class="py-4 text-right"><?php if($r['status']!='selesai'): ?><a href="?page=pemeriksaan&id=<?= $r['id_antrian'] ?>" class="btn-primary px-3 py-1.5 rounded-lg text-xs font-bold">Periksa</a><?php else: ?><a href="?page=detail_pasien&id=<?= $r['id_antrian'] ?>" class="text-[#45BC7D] text-xs font-bold">Detail</a><?php endif; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody></table>
                </div>

            <?php elseif ($page === 'pemeriksaan'): ?>
                <?php $id=$_GET['id']; $conn->query("UPDATE antrian SET status='diperiksa' WHERE id_antrian=$id AND status='menunggu'"); $p=$conn->query("SELECT a.*, p.*, po.nama_poli FROM antrian a JOIN pasien p ON a.id_pasien=p.id_pasien JOIN poli po ON po.id_poli=1 WHERE a.id_antrian=$id")->fetch_assoc(); $umur=date_diff(date_create($p['tanggal_lahir']), date_create('today'))->y; ?>
                <div class="max-w-4xl mx-auto pb-12">
                    <div class="bg-[#45BC7D] rounded-2xl p-6 text-white mb-6 shadow-lg flex justify-between"><div class="flex gap-4"><div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center text-xl"><i class="far fa-user"></i></div><div><h2 class="text-xl font-bold"><?= $p['nama_lengkap'] ?></h2><p class="text-emerald-50 text-sm mt-1">Antrian: A-<?= $p['nomor_antrian'] ?> &bull; <?= $p['jenis_kelamin'] ?> &bull; <?= $umur ?> Tahun &bull; <?= $p['nama_poli'] ?></p></div></div><div class="text-right text-emerald-50"><i class="far fa-calendar-alt"></i> <?= date('d M Y') ?></div></div>
                    <form method="POST">
                        <input type="hidden" name="action" value="simpan_pemeriksaan"><input type="hidden" name="id_antrian" value="<?= $id ?>">
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm mb-6"><h4 class="font-bold text-gray-800 mb-4">Tanda Vital</h4><div class="grid grid-cols-4 gap-4"><div><label class="text-xs text-gray-500 font-bold mb-1 block">TD</label><input name="td" placeholder="120/80 mmHg" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm"></div><div><label class="text-xs text-gray-500 font-bold mb-1 block">Suhu</label><input name="suhu" placeholder="36.5 C" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm"></div><div><label class="text-xs text-gray-500 font-bold mb-1 block">Nadi</label><input name="nadi" placeholder="80 x/mnt" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm"></div><div><label class="text-xs text-gray-500 font-bold mb-1 block">RR</label><input name="rr" placeholder="20 x/mnt" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm"></div></div></div>
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm mb-6 space-y-5"><h4 class="font-bold text-gray-800">Data Pemeriksaan</h4><div><label class="text-sm font-bold text-gray-700 mb-2 block">Keluhan</label><textarea name="keluhan" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm"></textarea></div><div><label class="text-sm font-bold text-gray-700 mb-2 block">Diagnosa</label><input name="diagnosa" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm"></div><div><label class="text-sm font-bold text-gray-700 mb-2 block">Resep Obat</label><textarea name="resep" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm"></textarea></div><div><label class="text-sm font-bold text-gray-700 mb-2 block">Tindakan</label><textarea name="tindakan" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm"></textarea></div></div>
                        <div class="flex gap-4"><button type="submit" class="flex-1 bg-[#496A9A] text-white py-4 rounded-xl font-bold shadow-lg">Simpan Pemeriksaan</button><button type="button" onclick="history.back()" class="flex-1 bg-[#45BC7D] text-white py-4 rounded-xl font-bold shadow-lg">Tandai Selesai</button></div>
                    </form>
                </div>

            <?php elseif ($page === 'detail_pasien'): ?>
                <?php $id_antrian=$_GET['id']; $q_rm=$conn->query("SELECT rm.*, p.nama_lengkap, p.no_rm FROM rekam_medis rm JOIN pasien p ON rm.id_pasien=p.id_pasien WHERE rm.id_antrian=$id_antrian"); $rm=$q_rm->fetch_assoc(); ?>
                <div class="max-w-4xl mx-auto"><a href="?page=dashboard" class="mb-4 inline-block text-gray-500 hover:text-[#45BC7D]"><i class="fas fa-arrow-left mr-2"></i> Kembali</a><div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm"><div class="flex justify-between items-center border-b pb-6 mb-6"><div><h1 class="text-2xl font-bold text-gray-800"><?= $rm['nama_lengkap'] ?></h1><p class="text-gray-500 text-sm">No. RM: <?= $rm['no_rm'] ?></p></div><span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-bold">Selesai</span></div><div class="grid grid-cols-2 gap-8"><div><h4 class="text-xs font-bold text-gray-400 uppercase mb-1">Tgl Kunjungan</h4><p class="font-medium"><?= date('d F Y', strtotime($rm['tanggal_kunjungan'])) ?></p></div><div><h4 class="text-xs font-bold text-gray-400 uppercase mb-1">Diagnosa</h4><p class="font-medium text-emerald-600"><?= $rm['diagnosa'] ?></p></div><div class="col-span-2"><h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Tanda Vital</h4><div class="grid grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl"><div class="text-center"><span class="text-xs text-gray-500 block">TD</span><b><?= $rm['tensi'] ?></b></div><div class="text-center"><span class="text-xs text-gray-500 block">Suhu</span><b><?= $rm['suhu'] ?></b></div><div class="text-center"><span class="text-xs text-gray-500 block">Nadi</span><b><?= $rm['nadi'] ?></b></div><div class="text-center"><span class="text-xs text-gray-500 block">RR</span><b><?= $rm['rr'] ?></b></div></div></div><div class="col-span-2"><h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Keluhan</h4><p class="bg-gray-50 p-4 rounded-xl text-sm"><?= $rm['keluhan'] ?></p></div><div class="col-span-2"><h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Resep Obat</h4><p class="bg-gray-50 p-4 rounded-xl text-sm font-mono"><?= nl2br($rm['resep_obat']) ?></p></div></div></div></div>

            <?php elseif ($page === 'rujukan'): ?>
                <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="font-bold text-lg text-gray-800 mb-6">Buat Surat Rujukan</h3>
                    <form action="cetak_rujukan.php" method="POST" target="_blank">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold mb-2">Pilih Pasien *</label>
                                <select name="id_pasien" class="w-full border border-gray-200 p-3 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#45BC7D]" required>
                                    <option value="">-- Pilih Pasien (Hanya yang memiliki Rekam Medis) --</option>
                                    <?php 

                                    $q_pasien = "SELECT DISTINCT p.id_pasien, p.nama_lengkap, p.no_rm 
                                                 FROM pasien p 
                                                 JOIN rekam_medis rm ON p.id_pasien = rm.id_pasien 
                                                 ORDER BY p.nama_lengkap ASC";
                                    $ps = $conn->query($q_pasien);
                                    
                                    if ($ps->num_rows > 0) {
                                        while($p = $ps->fetch_assoc()) {
                                            echo "<option value='{$p['id_pasien']}'>{$p['nama_lengkap']} - {$p['no_rm']}</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>Belum ada data pasien dengan rekam medis</option>";
                                    }
                                    ?>
                                </select>
                                <p class="text-xs text-gray-400 mt-1">*Hanya pasien yang sudah diperiksa (memiliki rekam medis) yang muncul disini.</p>
                            </div>
                            <div><label class="block text-sm font-bold mb-2">Rumah Sakit Tujuan *</label><select name="rs_tujuan" class="w-full border border-gray-200 p-3 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#45BC7D]"><option value="">-- Pilih RS Rujukan --</option><?php foreach($daftar_rs as $rs): echo "<option value='$rs'>$rs</option>"; endforeach; ?></select></div>
                            <div><label class="block text-sm font-bold mb-2">Spesialis yang Dituju *</label><select name="poli_tujuan" class="w-full border border-gray-200 p-3 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#45BC7D]"><option value="">-- Pilih Spesialis --</option><?php foreach($daftar_spesialis as $sp): echo "<option value='$sp'>$sp</option>"; endforeach; ?></select></div>
                            <div><label class="block text-sm font-bold mb-2">Diagnosa Sementara *</label><textarea name="diagnosa" rows="2" class="w-full border border-gray-200 p-3 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#45BC7D]"></textarea></div>
                            <div><label class="block text-sm font-bold mb-2">Alasan Rujukan *</label><textarea name="alasan" rows="2" class="w-full border border-gray-200 p-3 rounded-xl bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#45BC7D]"></textarea></div>
                            <div class="text-right"><button class="btn-primary px-8 py-3 rounded-xl font-bold shadow-lg"><i class="fas fa-print mr-2"></i> Cetak & Simpan</button></div>
                        </div>
                    </form>
                </div>

            <?php elseif ($page === 'rekam_medis'): ?>
                <?php $kw=$_GET['q']??''; $q=$conn->query("SELECT rm.*, p.nama_lengkap, p.nik, po.nama_poli FROM rekam_medis rm JOIN pasien p ON rm.id_pasien=p.id_pasien JOIN poli po ON po.id_poli=1 WHERE p.nama_lengkap LIKE '%$kw%' ORDER BY rm.tanggal_kunjungan DESC"); ?>
                <div class="mb-6"><h2 class="text-2xl font-bold text-gray-800">Rekam Medis Pasien</h2></div><form class="mb-6 relative"><input type="hidden" name="page" value="rekam_medis"><i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i><input name="q" value="<?= htmlspecialchars($kw) ?>" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-sm shadow-sm" placeholder="Cari pasien..."></form>
                <div class="space-y-4"><?php while($r=$q->fetch_assoc()): ?><a href="?page=detail_pasien&id=<?= $r['id_antrian'] ?>" class="block bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition-all cursor-pointer flex justify-between items-center group"><div><h3 class="font-bold text-lg"><?= $r['nama_lengkap'] ?> <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full ml-2"><?= $r['nama_poli'] ?></span></h3><p class="text-xs text-gray-400 font-mono mt-1">NIK: <?= $r['nik'] ?></p><p class="text-sm text-gray-600 mt-2"><span class="font-semibold"><?= date('d M Y', strtotime($r['tanggal_kunjungan'])) ?></span> &bull; <?= $r['diagnosa'] ?></p></div><i class="fas fa-chevron-right text-gray-300 group-hover:text-[#45BC7D]"></i></a><?php endwhile; ?></div>

            <?php elseif ($page === 'profil'): ?>
                <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <?php if (isset($_GET['edit'])): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profil">
                        <h3 class="font-bold text-lg mb-6">Edit Profil</h3>
                        <div class="profil-card mb-4"><p class="text-xs text-gray-500 uppercase font-semibold mb-1">Nama Lengkap</p><input name="nama_dokter" value="<?= $dokter['nama_dokter'] ?>" class="input-view p-2 rounded focus:ring-1 focus:ring-[#45BC7D] border border-gray-200"></div>
                        <div class="profil-card mb-4"><p class="text-xs text-gray-500 uppercase font-semibold mb-1">NIP (Kode Dokter)</p><input name="kode_dokter" value="<?= $dokter['kode_dokter'] ?>" class="input-view p-2 rounded focus:ring-1 focus:ring-[#45BC7D] border border-gray-200"></div>
                        <div class="profil-card mb-4"><p class="text-xs text-gray-500 uppercase font-semibold mb-1">Spesialisasi</p><select name="spesialis" class="w-full p-2 rounded focus:ring-1 focus:ring-[#45BC7D] border border-gray-200 bg-white"><?php foreach($daftar_spesialis as $sp): ?><option value="<?= $sp ?>" <?= ($dokter['spesialis'] == $sp) ? 'selected' : '' ?>><?= $sp ?></option><?php endforeach; ?></select></div>
                        <div class="profil-card mb-4"><p class="text-xs text-gray-500 uppercase font-semibold mb-1">Email</p><input name="email" value="<?= $dokter['email'] ?>" class="input-view p-2 rounded focus:ring-1 focus:ring-[#45BC7D] border border-gray-200"></div>
                        <div class="profil-card mb-4"><p class="text-xs text-gray-500 uppercase font-semibold mb-1">Nomor Telepon</p><input name="no_hp" value="<?= $dokter['no_hp'] ?>" class="input-view p-2 rounded focus:ring-1 focus:ring-[#45BC7D] border border-gray-200"></div>
                        <div class="flex gap-4"><a href="?page=profil" class="flex-1 text-center border py-3 rounded-xl font-bold hover:bg-gray-50">Batal</a><button class="flex-1 btn-primary py-3 rounded-xl font-bold">Simpan Perubahan</button></div>
                    </form>
                    <?php else: ?>
                    <div class="bg-[#45BC7D] rounded-2xl p-6 text-white mb-8 shadow-lg"><div class="flex items-center gap-4"><i class="far fa-user text-3xl"></i><div><h2 class="text-xl font-bold"><?= $dokter['nama_dokter'] ?></h2><p class="text-sm text-emerald-100"><?= $dokter['spesialis'] ?></p><p class="text-xs text-emerald-200">NIP: <?= $dokter['kode_dokter'] ?></p></div></div></div>
                    <h3 class="font-bold text-gray-800 mb-4">Informasi Pribadi</h3>
                    <div class="space-y-4 mb-8">
                        <?php $data_profil = [['user', 'Nama Lengkap', $dokter['nama_dokter']], ['id-card', 'NIP', $dokter['kode_dokter']], ['stethoscope', 'Spesialisasi', $dokter['spesialis']], ['envelope', 'Email', $dokter['email']], ['phone', 'Nomor Telepon', $dokter['no_hp']], ['calendar-alt', 'Jadwal Kerja', 'Senin - Jumat: 08:00 - 14:00 WIB']];
                        foreach ($data_profil as $d): ?><div class="profil-card flex items-center gap-4"><i class="fas fa-<?= $d[0] ?> text-gray-400 w-6"></i><div><p class="text-xs text-gray-500 uppercase font-semibold"><?= $d[1] ?></p><p class="font-medium text-gray-800"><?= $d[2] ?></p></div></div><?php endforeach; ?>
                    </div>
                    <a href="?page=profil&edit=true" class="block w-full text-center border py-3 rounded-xl font-bold hover:bg-gray-50"><i class="fas fa-edit mr-2"></i> Edit Profil</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>