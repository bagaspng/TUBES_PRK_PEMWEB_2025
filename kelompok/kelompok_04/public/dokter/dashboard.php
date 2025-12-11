<?php
session_start();

// hak akses dokter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../../src/config/database.php';

$user_id = $_SESSION['user_id'];
$query_info = "SELECT dokter.*, users.email FROM dokter JOIN users ON dokter.id_user = users.id_user WHERE dokter.id_user = ?";
$stmt = $conn->prepare($query_info);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$dokter = $stmt->get_result()->fetch_assoc();

if (!$dokter) {
    $dokter = ['nama_dokter' => 'dr. User', 'kode_dokter' => '-', 'spesialis' => 'Umum', 'no_hp' => '-', 'email' => '-'];
}

$message = $_GET['msg'] ?? '';

function getBadge($status) {
    if ($status == 'menunggu') return 'bg-yellow-100 text-yellow-700';
    if ($status == 'diperiksa') return 'bg-emerald-100 text-emerald-700';
    return 'bg-gray-100 text-gray-600';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dashboard — Puskesmas (Dokter)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #FAFAFA; }
        .nav-active { background-color: #45BC7D; color: white; box-shadow: 0 4px 10px rgba(69,188,125,0.2); }
        .nav-item { color: #64748B; }
        .nav-item:hover { background-color: #F0FDF4; color: #45BC7D; }
        .btn-primary { background-color: #45BC7D; color: white; }
        .btn-primary:hover { background-color: #3AA668; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <!-- SIDEBAR: include terpisah -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-20 bg-white border-b border-gray-100 flex items-center justify-between px-8 flex-shrink-0">
            <h2 class="text-xl font-bold text-gray-800">Dashboard</h2>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 font-bold">
                    <?= htmlspecialchars(substr(trim($dokter['nama_dokter']), 0, 1)) ?>
                </div>
                <div class="hidden md:block text-right">
                    <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($dokter['nama_dokter']) ?></p>
                    <p class="text-xs text-gray-500"> <?= htmlspecialchars($dokter['kode_dokter']) ?></p>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
        <div class="bg-emerald-50 border-l-4 border-[#45BC7D] text-emerald-800 p-4 m-8 mb-0 rounded shadow-sm flex justify-between">
            <p class="text-sm font-medium"><i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($message) ?></p>
            <button onclick="this.parentElement.style.display='none'"><i class="fas fa-times"></i></button>
        </div>
        <?php endif; ?>

        <div class="flex-1 overflow-y-auto p-8">
            <?php
            // Statistik hari ini
            $today = date('Y-m-d');
            $res = $conn->query("SELECT COUNT(*) as total, SUM(status='menunggu') as t, SUM(status='diperiksa') as p, SUM(status='selesai') as s FROM antrian WHERE DATE(waktu_daftar)='$today'")->fetch_assoc();
            ?>
            <div class="grid grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="fas fa-users"></i></div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $res['total'] ?></h3>
                    <p class="text-gray-500 text-sm">Total Pasien</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="far fa-clock"></i></div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $res['t'] ?></h3>
                    <p class="text-gray-500 text-sm">Menunggu</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="fas fa-briefcase-medical"></i></div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $res['p'] ?></h3>
                    <p class="text-gray-500 text-sm">Sedang Diperiksa</p>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center text-xl mb-4"><i class="far fa-check-circle"></i></div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $res['s'] ?></h3>
                    <p class="text-gray-500 text-sm">Selesai Hari Ini</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-lg text-gray-800">Daftar Pasien Hari Ini</h3>
                    <a href="daftar_pasien.php" class="text-[#45BC7D] text-sm font-bold">Lihat Semua</a>
                </div>

                <table class="w-full text-left">
                    <thead class="text-gray-400 text-xs uppercase font-bold border-b border-gray-50">
                        <tr>
                            <th class="py-4">No</th>
                            <th class="py-4">Nama</th>
                            <th class="py-4">Poli</th>
                            <th class="py-4">Status</th>
                            <th class="py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php
            
                        $sql_pasien = "SELECT a.*, p.nama_lengkap, po.nama_poli, rm.id_rekam
                                       FROM antrian a
                                       JOIN pasien p ON a.id_pasien = p.id_pasien
                                       JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal
                                       JOIN dokter d ON j.id_dokter = d.id_dokter
                                       JOIN poli po ON d.id_poli = po.id_poli
                                       LEFT JOIN rekam_medis rm ON a.id_antrian = rm.id_antrian
                                       WHERE DATE(a.waktu_daftar) = ?
                                       ORDER BY 
                                           CASE 
                                               WHEN a.status = 'menunggu' THEN 1
                                               WHEN a.status = 'diperiksa' THEN 2
                                               WHEN a.status = 'selesai' THEN 3
                                               ELSE 4
                                           END ASC,
                                           a.waktu_daftar ASC,
                                           a.nomor_antrian ASC
                                       LIMIT 5";
                        $stmt_pasien = $conn->prepare($sql_pasien);
                        $stmt_pasien->bind_param("s", $today);
                        $stmt_pasien->execute();
                        $q = $stmt_pasien->get_result();

                        if ($q && $q->num_rows > 0):
                            while ($r = $q->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="py-4 font-bold">A-<?= htmlspecialchars(str_pad($r['nomor_antrian'], 3, '0', STR_PAD_LEFT)) ?></td>
                            <td class="py-4"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                            <td class="py-4 text-gray-500"><?= htmlspecialchars($r['nama_poli']) ?></td>
                            <td class="py-4">
                                <span class="px-3 py-1 rounded text-xs font-bold <?= getBadge($r['status']) ?> capitalize"><?= htmlspecialchars($r['status']) ?></span>
                            </td>
                            <td class="py-4 text-right">
                                <?php if ($r['status'] != 'selesai'): ?>
                                    <a href="pemeriksaan.php?id=<?= (int)$r['id_antrian'] ?>" class="btn-primary px-4 py-2 rounded-lg text-xs font-bold shadow-sm">Periksa</a>
                                <?php else: ?>
                                    <?php if ($r['id_rekam']): ?>
                                        <a href="detail_rekam_medis.php?id=<?= (int)$r['id_rekam'] ?>" class="text-[#45BC7D] text-xs font-bold hover:underline">Detail</a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs italic">Tidak ada rekam medis</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="5" class="py-6 text-center text-gray-400 italic">Belum ada antrian pasien hari ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

</body>
</html>
