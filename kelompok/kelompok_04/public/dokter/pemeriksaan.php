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
$id_rekam = 0;
$data_draft = null;

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
    $stmt_pasien = $conn->prepare("SELECT a.*, p.*, po.nama_poli, rm.id_rekam, rm.td_sistolik, rm.td_diastolik, rm.suhu, rm.nadi, rm.rr, rm.keluhan, rm.diagnosa, rm.resep_obat, rm.pemeriksaan
                                    FROM antrian a 
                                    JOIN pasien p ON a.id_pasien = p.id_pasien 
                                    JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal
                                    JOIN dokter d ON j.id_dokter = d.id_dokter
                                    JOIN poli po ON d.id_poli = po.id_poli 
                                    LEFT JOIN rekam_medis rm ON a.id_antrian = rm.id_antrian
                                    WHERE a.id_antrian = ?");
    $stmt_pasien->bind_param("i", $id_antrian);
    $stmt_pasien->execute();
    $pasien = $stmt_pasien->get_result()->fetch_assoc();
    
    if ($pasien && $pasien['id_rekam']) {
        $id_rekam = (int)$pasien['id_rekam'];
        $data_draft = [
            'td_sistolik' => $pasien['td_sistolik'],
            'td_diastolik' => $pasien['td_diastolik'],
            'suhu' => $pasien['suhu'],
            'nadi' => $pasien['nadi'],
            'rr' => $pasien['rr'],
            'keluhan' => $pasien['keluhan'],
            'diagnosa' => $pasien['diagnosa'],
            'resep_obat' => $pasien['resep_obat'],
            'pemeriksaan' => $pasien['pemeriksaan']
        ];
        
        // Cek rujukan jika ada
        $stmt_rujukan = $conn->prepare("SELECT * FROM rujukan WHERE id_rekam = ? LIMIT 1");
        $stmt_rujukan->bind_param("i", $id_rekam);
        $stmt_rujukan->execute();
        $rujukan_data = $stmt_rujukan->get_result()->fetch_assoc();
        if ($rujukan_data) {
            $data_draft['rujukan'] = $rujukan_data;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan_draft') {
        $id_antrian = (int)$_POST['id_antrian'];
        $td_sistolik = $_POST['td_sistolik'] ?? '-';
        $td_diastolik = $_POST['td_diastolik'];
        $suhu = $_POST['suhu'] ?? '-';
        $nadi = $_POST['nadi'] ?? '-';
        $rr = $_POST['rr'] ?? '-';
        $keluhan = $_POST['keluhan'] ?? '';
        $diagnosa = $_POST['diagnosa'] ?? '';
        $resep = $_POST['resep'] ?? '';
        $tindakan = $_POST['tindakan'] ?? '';

        $stmt_data = $conn->prepare("SELECT a.id_pasien, d.id_poli, d.id_dokter 
                                     FROM antrian a 
                                     JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal 
                                     JOIN dokter d ON j.id_dokter = d.id_dokter 
                                     WHERE a.id_antrian = ?");
        $stmt_data->bind_param("i", $id_antrian);
        $stmt_data->execute();
        $data = $stmt_data->get_result()->fetch_assoc();

        if ($data) {
            $id_pasien = (int)$data['id_pasien'];
            $id_dokter = (int)$data['id_dokter'];
            $id_poli = (int)$data['id_poli'];
            
            $stmt_cek = $conn->prepare("SELECT id_rekam FROM rekam_medis WHERE id_antrian = ? LIMIT 1");
            $stmt_cek->bind_param("i", $id_antrian);
            $stmt_cek->execute();
            $cek_hasil = $stmt_cek->get_result()->fetch_assoc();
            
            if ($cek_hasil) {
                $id_rekam = $cek_hasil['id_rekam'];
                $stmt_rm = $conn->prepare("UPDATE rekam_medis 
                                           SET td_sistolik=?, td_dioastolik=?, suhu=?, nadi=?, rr=?, keluhan=?, diagnosa=?, resep_obat=?, pemeriksaan=?, updated_at=NOW()
                                           WHERE id_rekam = ?");
                $stmt_rm->bind_param("sssssssssi", $td_sistolik, $td_diastolik, $suhu, $nadi, $rr, $keluhan, $diagnosa, $resep, $tindakan, $id_rekam);
            } else {
                $stmt_rm = $conn->prepare("INSERT INTO rekam_medis (id_pasien, id_dokter, id_poli, id_antrian, tanggal_kunjungan, td_sistolik, td_diastolik, suhu, nadi, rr, keluhan, diagnosa, resep_obat, pemeriksaan, created_at, updated_at) 
                                           VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt_rm->bind_param("iiiisssssssss", $id_pasien, $id_dokter, $id_poli, $id_antrian, $td_sistolik, $td_diastolik, $suhu, $nadi, $rr, $keluhan, $diagnosa, $resep, $tindakan);
            }
            
            if ($stmt_rm->execute()) {
                if (!$cek_hasil) {
                    $id_rekam = $conn->insert_id;
                }
                
                $ada_rujukan = isset($_POST['ada_rujukan']);
                if ($ada_rujukan) {
                    $faskes_tujuan = $_POST['faskes_tujuan'] ?? '';
                    $poli_tujuan   = $_POST['poli_tujuan'] ?? '';

                    if (empty($faskes_tujuan)) {
                        $_SESSION['error'] = "Faskes tujuan wajib diisi saat rujukan dicentang.";
                        header("Location: pemeriksaan.php?id=" . $id_antrian);
                        exit;
                    }

                    // Cek apakah rujukan sudah ada
                    $stmt_cek_rujukan = $conn->prepare("SELECT id_rujukan FROM rujukan WHERE id_rekam = ? LIMIT 1");
                    $stmt_cek_rujukan->bind_param("i", $id_rekam);
                    $stmt_cek_rujukan->execute();
                    $cek_rujukan = $stmt_cek_rujukan->get_result()->fetch_assoc();
                    
                    if ($cek_rujukan) {
                        // UPDATE rujukan
                        $stmt_rujukan = $conn->prepare("UPDATE rujukan SET faskes_tujuan=?, poli_tujuan=?, diagnosa=?, updated_at=NOW() WHERE id_rekam = ?");
                        $stmt_rujukan->bind_param("sssi", $faskes_tujuan, $poli_tujuan, $diagnosa, $id_rekam);
                    } else {
                        // INSERT rujukan baru
                        $stmt_rujukan = $conn->prepare("INSERT INTO rujukan (id_rekam, id_dokter, faskes_tujuan, poli_tujuan, diagnosa, tanggal_rujukan, created_at, updated_at) 
                                                         VALUES (?, ?, ?, ?, ?, CURDATE(), NOW(), NOW())");
                        $stmt_rujukan->bind_param("iisss", $id_rekam, $id_dokter, $faskes_tujuan, $poli_tujuan, $diagnosa);
                    }
                    
                    if (!$stmt_rujukan->execute()) {
                        $_SESSION['error'] = "Rujukan gagal: " . $stmt_rujukan->error;
                        error_log("Rujukan Error: " . $stmt_rujukan->error);
                    }
                } else {
                    // Hapus rujukan jika checkbox tidak dicentang
                    $stmt_hapus_rujukan = $conn->prepare("DELETE FROM rujukan WHERE id_rekam = ?");
                    $stmt_hapus_rujukan->bind_param("i", $id_rekam);
                    $stmt_hapus_rujukan->execute();
                }

                $_SESSION['message'] = "✅ Pemeriksaan disimpan sebagai draft. Anda dapat mengeditnya kembali.";
                header("Location: pemeriksaan.php?id=" . $id_antrian);
                exit;
            } else {
                $_SESSION['error'] = "Error: " . $stmt_rm->error;
                error_log("Database Error: " . $stmt_rm->error);
            }
        }
    }

    // FINALISASI / TANDAI SELESAI
    if ($action === 'finalisasi_pemeriksaan') {
        $id_antrian = (int)$_POST['id_antrian'];
        $id_rekam = (int)$_POST['id_rekam'] ?? 0;

        // Validasi apakah rekam medis sudah ada
        if ($id_rekam <= 0) {
            $_SESSION['error'] = "⚠️ Harap simpan pemeriksaan terlebih dahulu sebelum finalisasi!";
            header("Location: pemeriksaan.php?id=" . $id_antrian);
            exit;
        }
        
        // Update status antrian menjadi selesai
        $stmt_selesai = $conn->prepare("UPDATE antrian SET status = 'selesai' WHERE id_antrian = ?");
        $stmt_selesai->bind_param("i", $id_antrian);
        $stmt_selesai->execute();

        $_SESSION['message'] = "✅ Pemeriksaan berhasil difinalisasi dan antrian ditandai selesai.";
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

// Tampilkan pesan jika ada
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
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

    <!-- include sidebar terpisah -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="h-20 bg-white border-b border-gray-100 flex items-center justify-between px-8 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button type="button"
                        onclick="window.history.back()"
                        class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:text-[#45BC7D] hover:border-[#45BC7D] transition">
                    <span class="sr-only">Kembali</span>
                    <span class="text-lg">&larr;</span>
                </button>
                <h2 class="text-xl font-bold text-gray-800">Pemeriksaan Pasien</h2>
            </div>
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
                
                <?php if (!empty($message)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 mb-6 rounded-xl shadow-sm flex items-center gap-3">
                    <i class="fas fa-check-circle text-xl"></i>
                    <p class="text-sm font-medium"><?= htmlspecialchars($message) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-6 rounded-xl shadow-sm flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                    <p class="text-sm font-medium"><?= htmlspecialchars($error_message) ?></p>
                </div>
                <?php endif; ?>
                
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
                            <?php if ($id_rekam > 0): ?>
                            <div class="mt-2 bg-white/20 px-3 py-1 rounded-full text-xs">
                                Draft tersimpan
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" id="formPemeriksaan">
                    <input type="hidden" name="action" value="simpan_draft">
                    <input type="hidden" name="id_antrian" value="<?= $id_antrian ?>">
                    <input type="hidden" name="id_rekam" value="<?= $id_rekam ?>">

                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6">
                        <h3 class="font-bold text-gray-800 text-lg mb-4">Tanda Vital</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tekanan Darah</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="td_sistolik" value="<?= htmlspecialchars($data_draft['td_sistolik'] ?? '') ?>" placeholder="120" 
                                           class="w-20 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                                    <span class="text-gray-500 font-bold">/</span>
                                    <input type="text" name="td_diastolik" value="<?= htmlspecialchars($data_draft['td_diastolik'] ?? '') ?>" placeholder="80" 
                                           class="w-20 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Suhu Tubuh</label>
                                <input type="text" name="suhu" value="<?= htmlspecialchars($data_draft['suhu'] ?? '') ?>" placeholder="36.5°C" 
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nadi</label>
                                <input type="text" name="nadi" value="<?= htmlspecialchars($data_draft['nadi'] ?? '') ?>" placeholder="80 x/menit" 
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Pernapasan</label>
                                <input type="text" name="rr" value="<?= htmlspecialchars($data_draft['rr'] ?? '') ?>" placeholder="20 x/menit" 
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
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent resize-none"><?= htmlspecialchars($data_draft['keluhan'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Diagnosa <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="diagnosa" value="<?= htmlspecialchars($data_draft['diagnosa'] ?? '') ?>" required
                                       placeholder="Tuliskan hasil diagnosa..."
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Resep Obat</label>
                                <textarea name="resep" rows="4"
                                          placeholder="Tuliskan resep obat yang diberikan (nama obat, dosis, durasi)..."
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent resize-none"><?= htmlspecialchars($data_draft['resep_obat'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tindakan / Anjuran</label>
                                <textarea name="tindakan" rows="4"
                                          placeholder="Tuliskan tindakan yang dilakukan atau anjuran kepada pasien..."
                                          class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent resize-none"><?= htmlspecialchars($data_draft['pemeriksaan'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                            <label class="flex items-center gap-3 cursor-pointer mb-3">
                                <input type="checkbox" id="checkbox_rujukan" name="ada_rujukan" 
                                       <?= isset($data_draft['rujukan']) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-[#45BC7D] rounded focus:ring-2 focus:ring-[#45BC7D]">
                                <span class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-hospital text-blue-600 mr-1"></i>
                                    Pasien perlu dirujuk ke Rumah Sakit
                                </span>
                            </label>
                            
                            <div id="section_rujukan" class="<?= isset($data_draft['rujukan']) ? '' : 'hidden' ?> mt-4 pt-4 border-t border-blue-200 space-y-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Faskes Tujuan</label>
                                    <input type="text" name="faskes_tujuan" 
                                           value="<?= htmlspecialchars($data_draft['rujukan']['faskes_tujuan'] ?? '') ?>"
                                           placeholder="Nama Rumah Sakit / Faskes tujuan..." 
                                           class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Poli Tujuan</label>
                                    <input type="text" name="poli_tujuan" 
                                           value="<?= htmlspecialchars($data_draft['rujukan']['poli_tujuan'] ?? '') ?>"
                                           placeholder="Poli / Spesialis tujuan..." 
                                           class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" 
                                class="flex-1 btn-primary py-4 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Simpan Draft Pemeriksaan
                        </button>
                        <button type="button" onclick="finalizePemeriksaan()" 
                                class="flex-1 btn-secondary py-4 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            Finalisasi & Selesai
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
        document.getElementById('checkbox_rujukan').addEventListener('change', function() {
            const sectionRujukan = document.getElementById('section_rujukan');
            if (this.checked) {
                sectionRujukan.classList.remove('hidden');
            } else {
                sectionRujukan.classList.add('hidden');
            }
        });

        function finalizePemeriksaan() {
            const keluhan = document.querySelector('textarea[name="keluhan"]').value.trim();
            const diagnosa = document.querySelector('input[name="diagnosa"]').value.trim();
            const id_rekam = document.querySelector('input[name="id_rekam"]').value;
            
            if (!keluhan || !diagnosa) {
                alert('⚠️ Keluhan dan Diagnosa wajib diisi!');
                return;
            }
            
            if (id_rekam == '0') {
                alert('⚠️ Harap simpan pemeriksaan terlebih dahulu sebelum finalisasi!');
                return;
            }
            
            if (confirm('✅ Apakah Anda yakin ingin menfinalisasi pemeriksaan ini?\n\nSetelah finalisasi, status antrian akan berubah menjadi SELESAI.')) {
                const form = document.getElementById('formPemeriksaan');
                const actionInput = form.querySelector('input[name="action"]');
                actionInput.value = 'finalisasi_pemeriksaan';
                form.submit();
            }
        }
    </script>

</body>
</html>
