<?php
// public/admin/rujukan.php
session_start();

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/icon_helper.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$idUser = (int) $_SESSION['user_id'];

$sqlUser = "SELECT username, email FROM users WHERE id_user = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param('i', $idUser);
$stmtUser->execute();
$admin = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$adminName = $admin['username'] ?? 'Admin';

$sqlRujukan = "
    SELECT 
        r.id_rujukan,
        r.id_rekam,
        r.faskes_tujuan,
        r.poli_tujuan,
        r.diagnosa,
        r.tanggal_rujukan,
        r.created_at,
        p.nama_lengkap AS nama_pasien,
        p.nik,
        p.tanggal_lahir,
        p.jenis_kelamin,
        d.nama_dokter,
        d.kode_dokter,
        d.spesialis,
        po.nama_poli,
        rm.keluhan,
        rm.pemeriksaan,
        rm.resep_obat,
        rm.td_sistolik,
        rm.td_diastolik,
        rm.suhu,
        rm.nadi,
        rm.rr
    FROM rujukan r
    JOIN rekam_medis rm ON r.id_rekam = rm.id_rekam
    JOIN pasien p ON rm.id_pasien = p.id_pasien
    JOIN dokter d ON r.id_dokter = d.id_dokter
    JOIN poli po ON d.id_poli = po.id_poli
    ORDER BY r.created_at DESC
";

$rujukanList = [];
if ($resultRujukan = $conn->query($sqlRujukan)) {
    while ($row = $resultRujukan->fetch_assoc()) {
        $rujukanList[] = $row;
    }
    $resultRujukan->free();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Rujukan - Admin Puskesmas</title>
   <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex">
    <?php
        $active = 'rujukan';
        include __DIR__ . '/sidebar.php';
    ?>

    <main class="flex-1 p-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Daftar Rujukan</h1>
            <p class="text-gray-600">Kelola dan cetak surat rujukan dari dokter</p>
        </div>

        <!-- Table Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pasien</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dokter</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Faskes Tujuan</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Poli Tujuan</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($rujukanList)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-lg font-medium">Belum ada rujukan</p>
                                    <p class="text-sm">Rujukan akan muncul ketika dokter membuat rujukan pasien</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($rujukanList as $ruj): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-900"><?= $no++ ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= date('d/m/Y', strtotime($ruj['tanggal_rujukan'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ruj['nama_pasien']) ?></div>
                                    <div class="text-xs text-gray-500">NIK: <?= htmlspecialchars($ruj['nik']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ruj['nama_dokter']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($ruj['spesialis']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($ruj['faskes_tujuan']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($ruj['poli_tujuan'] ?: '-') ?></td>
                                <td class="px-6 py-4 text-center">
                                    <a href="cetak_rujukan_pdf.php?id=<?= $ruj['id_rujukan'] ?>" 
                                       target="_blank"
                                       class="group inline-flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-br from-emerald-500 to-teal-600 text-blue-600 rounded-xl  hover:scale-105 active:scale-95 transition-all duration-300 text-sm font-bold relative overflow-hidden">
                                        <span class="absolute inset-0 bg-blue-600 opacity-0 group-hover:opacity-20 transition-opacity duration-300"></span>
                                        <span class="relative flex items-center gap-2">
                                            <?= render_icon('file-pdf', 'fa', 'text-lg', 'Cetak PDF') ?>
                                            <span>Cetak PDF</span>
                                        </span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>