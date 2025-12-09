<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../src/config/database.php';

$user_id = $_SESSION['user_id'];
$id_antrian = $_GET['id'] ?? 0;
$message = "";

$query_info = "SELECT dokter.*, users.email FROM dokter JOIN users ON dokter.id_user = users.id_user WHERE dokter.id_user = ?";
$stmt = $conn->prepare($query_info);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$dokter = $stmt->get_result()->fetch_assoc();

if (!$dokter) {
    $dokter = ['nama_dokter' => 'dr. User', 'kode_dokter' => '-', 'spesialis' => 'Umum', 'no_hp' => '-', 'email' => '-'];
}

if ($id_antrian > 0) {
    $stmt_update = $conn->prepare("UPDATE antrian SET status='diperiksa' WHERE id_antrian=? AND status='menunggu'");
    $stmt_update->bind_param("i", $id_antrian);
    $stmt_update->execute();
}

$pasien = null;
if ($id_antrian > 0) {
    $stmt_pasien = $conn->prepare("SELECT a.*, p.*, po.nama_poli 
                                    FROM antrian a 
                                    JOIN pasien p ON a.id_pasien = p.id_pasien 
                                    JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal
                                    JOIN dokter d ON j.id_dokter = d.id_dokter
                                    JOIN poli po ON d.id_poli = po.id_poli 
                                    WHERE a.id_antrian = ?");
    $stmt_pasien->bind_param("i", $id_antrian);
    $stmt_pasien->execute();
    $pasien = $stmt_pasien->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan_pemeriksaan') {
        $id_antrian = $_POST['id_antrian'];
        $td = $_POST['td'] ?? '-';
        $suhu = $_POST['suhu'] ?? '-';
        $nadi = $_POST['nadi'] ?? '-';
        $rr = $_POST['rr'] ?? '-';
        $keluhan = $_POST['keluhan'];
        $diagnosa = $_POST['diagnosa'];
        $resep = $_POST['resep'];
        $tindakan = $_POST['tindakan'];

        $stmt_data = $conn->prepare("SELECT a.id_pasien, d.id_poli, d.id_dokter 
                                     FROM antrian a 
                                     JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal 
                                     JOIN dokter d ON j.id_dokter = d.id_dokter 
                                     WHERE a.id_antrian = ?");
        $stmt_data->bind_param("i", $id_antrian);
        $stmt_data->execute();
        $data = $stmt_data->get_result()->fetch_assoc();

        if ($data) {
            $stmt_rm = $conn->prepare("INSERT INTO rekam_medis (id_pasien, id_dokter, id_poli, id_antrian, tanggal_kunjungan, tensi, suhu, nadi, rr, keluhan, diagnosa, resep_obat, pemeriksaan, created_at) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt_rm->bind_param("iiiisssssssss", $data['id_pasien'], $data['id_dokter'], $data['id_poli'], $id_antrian, $td, $suhu, $nadi, $rr, $keluhan, $diagnosa, $resep, $tindakan);
            
            if ($stmt_rm->execute()) {
                $stmt_selesai = $conn->prepare("UPDATE antrian SET status = 'selesai' WHERE id_antrian = ?");
                $stmt_selesai->bind_param("i", $id_antrian);
                $stmt_selesai->execute();
                
                $_SESSION['message'] = "Pemeriksaan selesai dan data berhasil disimpan.";
                header("Location: dashboard.php");
                exit;
            }
        }
    }

    if ($action === 'tandai_selesai') {
        $id_antrian = $_POST['id_antrian'];
        
        $stmt_selesai = $conn->prepare("UPDATE antrian SET status = 'selesai' WHERE id_antrian = ?");
        $stmt_selesai->bind_param("i", $id_antrian);
        $stmt_selesai->execute();
        
        $_SESSION['message'] = "Pasien ditandai selesai.";
        header("Location: dashboard.php");
        exit;
    }

    if ($action === 'logout') {
        session_destroy();
        header("Location: ../login.php");
        exit;
    }
}

$umur = 0;
if ($pasien && isset($pasien['tanggal_lahir'])) {
    $umur = date_diff(date_create($pasien['tanggal_lahir']), date_create('today'))->y;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemeriksaan Pasien - Puskesmas</title>
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
        .btn-secondary { background-color: #496A9A; color: white; }
        .btn-secondary:hover { background-color: #3d5a85; }
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
            <a href="dashboard.php" class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-sm font-medium transition-all nav-item">
                <i class="fas fa-th-large w-5 text-center"></i> Dashboard
            </a>
            <a href="dashboard.php?page=daftar_pasien" class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-sm font-medium transition-all nav-item">
                <i class="fas fa-users w-5 text-center"></i> Daftar Pasien
            </a>
            <a href="#" class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-sm font-medium transition-all nav-active">
                <i class="fas fa-stethoscope w-5 text-center"></i> Pemeriksaan
            </a>
            <a href="dashboard.php?page=rekam_medis" class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-sm font-medium transition-all nav-item">
                <i class="fas fa-file-medical w-5 text-center"></i> Rekam Medis
            </a>
            <a href="dashboard.php?page=rujukan" class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-sm font-medium transition-all nav-item">
                <i class="fas fa-file-invoice w-5 text-center"></i> Cetak Rujukan
            </a>
            <a href="dashboard.php?page=profil" class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-sm font-medium transition-all nav-item">
                <i class="fas fa-user-circle w-5 text-center"></i> Profil Dokter
            </a>
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
            <h2 class="text-xl font-bold text-gray-800">Pemeriksaan Pasien</h2>
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

        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-5xl mx-auto">
                
                <?php if ($pasien): ?>
                
                <div class="bg-[#45BC7D] rounded-2xl p-6 text-white mb-6 shadow-lg">
                    <div class="flex justify-between items-start">
                        <div class="flex gap-4 items-center">
                            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center text-2xl">
                                <i class="far fa-user"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold"><?= htmlspecialchars($pasien['nama_lengkap']) ?></h2>
                                <div class="flex gap-6 text-emerald-50 text-sm mt-2">
                                    <span><strong>No. Antrian:</strong> A-<?= str_pad($pasien['nomor_antrian'], 3, '0', STR_PAD_LEFT) ?></span>
                                    <span><strong>Poli:</strong> <?= htmlspecialchars($pasien['nama_poli']) ?></span>
                                </div>
                                <div class="flex gap-6 text-emerald-50 text-sm mt-1">
                                    <span><strong>Umur:</strong> <?= $umur ?> tahun</span>
                                    <span><strong>Jenis Kelamin:</strong> <?= $pasien['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right text-emerald-50">
                            <i class="far fa-calendar-alt"></i> <?= date('d M Y') ?>
                        </div>
                    </div>
                </div>

                <form method="POST" id="formPemeriksaan">
                    <input type="hidden" name="action" value="simpan_pemeriksaan">
                    <input type="hidden" name="id_antrian" value="<?= $id_antrian ?>">

                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">
                        <h3 class="font-bold text-gray-800 text-lg mb-4">Tanda Vital</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tekanan Darah</label>
                                <input type="text" name="td" placeholder="120/80 mmHg" 
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Suhu Tubuh</label>
                                <input type="text" name="suhu" placeholder="36.5°C" 
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nadi</label>
                                <input type="text" name="nadi" placeholder="80 x/menit" 
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pernapasan</label>
                                <input type="text" name="rr" placeholder="20 x/menit" 
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">
                        <h3 class="font-bold text-gray-800 text-lg mb-4">Data Pemeriksaan</h3>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Keluhan Pasien <span class="text-red-500">*</span>
                                </label>
                                <textarea name="keluhan" rows="4" required
                                          placeholder="Tuliskan keluhan yang disampaikan pasien..."
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent resize-none"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Diagnosa <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="diagnosa" required
                                       placeholder="Tuliskan hasil diagnosa..."
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Resep Obat</label>
                                <textarea name="resep" rows="4"
                                          placeholder="Tuliskan resep obat yang diberikan (nama obat, dosis, durasi)..."
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent resize-none"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tindakan / Anjuran</label>
                                <textarea name="tindakan" rows="4"
                                          placeholder="Tuliskan tindakan yang dilakukan atau anjuran kepada pasien..."
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent resize-none"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" class="w-5 h-5 text-[#45BC7D] rounded focus:ring-2 focus:ring-[#45BC7D]">
                                <span class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-hospital text-blue-600 mr-1"></i>
                                    Pasien perlu dirujuk ke Rumah Sakit
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" 
                                class="flex-1 btn-secondary py-4 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Simpan Pemeriksaan
                        </button>
                        <button type="button" onclick="markAsComplete()" 
                                class="flex-1 btn-primary py-4 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            Tandai Selesai
                        </button>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </form>

                <?php else: ?>
                
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-12 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-injured text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Data Pasien Tidak Ditemukan</h3>
                    <p class="text-gray-500 mb-6">Silakan pilih pasien dari daftar antrian untuk memulai pemeriksaan.</p>
                    <a href="dashboard.php?page=daftar_pasien" class="btn-primary px-6 py-3 rounded-xl font-bold inline-block shadow-lg">
                        <i class="fas fa-list mr-2"></i> Lihat Daftar Pasien
                    </a>
                </div>
                
                <?php endif; ?>

            </div>
        </div>
    </main>

    <script>
        function markAsComplete() {
            if (confirm('Apakah Anda yakin ingin menandai pemeriksaan selesai tanpa menyimpan data?')) {
                const form = document.getElementById('formPemeriksaan');
                const actionInput = form.querySelector('input[name="action"]');
                actionInput.value = 'tandai_selesai';
                form.submit();
            }
        }
    </script>

</body>
</html>