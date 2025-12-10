<?php
// public/admin/rujukan.php
session_start();

require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$idUser = (int) $_SESSION['user_id'];

// Ambil data admin
$sqlUser = "SELECT username, email FROM users WHERE id_user = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param('i', $idUser);
$stmtUser->execute();
$admin = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

$adminName = $admin['username'] ?? 'Admin';

// Ambil data rujukan dengan JOIN ke tabel terkait
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
        rm.tensi,
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

// Fungsi untuk menghitung umur
function hitungUmur($tanggalLahir) {
    if (!$tanggalLahir) return '-';
    $lahir = new DateTime($tanggalLahir);
    $sekarang = new DateTime();
    $umur = $sekarang->diff($lahir);
    return $umur->y . ' tahun';
}

// Handle print action - ambil data rujukan spesifik
$printData = null;
if (isset($_GET['print']) && is_numeric($_GET['print'])) {
    $idRujukan = (int)$_GET['print'];
    
    $sqlPrint = "
        SELECT 
            r.*,
            p.nama_lengkap AS nama_pasien,
            p.nik,
            p.no_rm,
            p.tanggal_lahir,
            p.jenis_kelamin,
            d.nama_dokter,
            d.kode_dokter,
            d.spesialis,
            po.nama_poli,
            rm.keluhan,
            rm.diagnosa AS diagnosa_rekam,
            rm.pemeriksaan,
            rm.resep_obat,
            rm.tensi,
            rm.suhu,
            rm.nadi,
            rm.rr,
            rm.tanggal_kunjungan
        FROM rujukan r
        JOIN rekam_medis rm ON r.id_rekam = rm.id_rekam
        JOIN pasien p ON rm.id_pasien = p.id_pasien
        JOIN dokter d ON r.id_dokter = d.id_dokter
        JOIN poli po ON d.id_poli = po.id_poli
        WHERE r.id_rujukan = ?
    ";
    
    $stmtPrint = $conn->prepare($sqlPrint);
    $stmtPrint->bind_param('i', $idRujukan);
    $stmtPrint->execute();
    $printData = $stmtPrint->get_result()->fetch_assoc();
    $stmtPrint->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= isset($printData) ? 'Cetak Surat Rujukan' : 'Daftar Rujukan' ?> - Admin Puskesmas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { 
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
            }
            .no-print { display: none !important; }
            @page { 
                size: A4;
                margin: 1cm;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

<?php if (!isset($printData)): ?>
<!-- Mode: List Rujukan -->
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
                                    <a href="rujukan.php?print=<?= $ruj['id_rujukan'] ?>" 
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-[#45BC7D] text-white rounded-lg hover:bg-[#3aa668] transition text-sm font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                        </svg>
                                        Cetak
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

<?php else: ?>
<!-- Mode: Print Surat Rujukan -->
<div class="min-h-screen bg-white">
    <!-- Toolbar -->
    <div class="no-print bg-gray-100 p-4 border-b border-gray-200 flex justify-between items-center sticky top-0 z-10">
        <a href="rujukan.php" class="text-gray-600 hover:text-gray-800 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
        <button onclick="window.print()" class="bg-[#45BC7D] text-white px-6 py-2 rounded-lg hover:bg-[#3aa668] transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Cetak Surat
        </button>
    </div>

    <!-- Print Area -->
    <div class="print-area max-w-4xl mx-auto p-12">
        <!-- Kop Surat -->
        <div class="border-b-4 border-gray-800 pb-4 mb-6">
            <div class="flex items-start gap-4">
                <div class="w-20 h-20 bg-gradient-to-br from-[#45BC7D] to-[#3aa668] rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-800 mb-1">PUSKESMAS</h1>
                    <p class="text-sm text-gray-600">Jl. Kesehatan No. 123, Bandung, Jawa Barat</p>
                    <p class="text-sm text-gray-600">Telp: (022) 1234567 | Email: puskesmas@kesehatan.go.id</p>
                </div>
            </div>
        </div>

        <!-- Judul Surat -->
        <div class="text-center mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-2">SURAT RUJUKAN</h2>
            <p class="text-sm text-gray-600">Nomor: <?= date('Y') ?>/SR/<?= str_pad($printData['id_rujukan'], 4, '0', STR_PAD_LEFT) ?>/<?= date('m') ?></p>
        </div>

        <!-- Isi Surat -->
        <div class="space-y-4 text-sm leading-relaxed">
            <p class="text-gray-700">Kepada Yth,</p>
            <p class="text-gray-700 font-semibold">
                <?= htmlspecialchars($printData['poli_tujuan'] ?: 'Dokter Spesialis') ?><br>
                <?= htmlspecialchars($printData['faskes_tujuan']) ?>
            </p>

            <p class="text-gray-700">Dengan hormat,</p>
            <p class="text-gray-700">Bersama ini kami mohon bantuan pemeriksaan dan penanganan lebih lanjut terhadap pasien:</p>

            <!-- Data Pasien -->
            <div class="bg-gray-50 rounded-lg p-6 my-6 border border-gray-200">
                <table class="w-full text-sm">
                    <tbody>
                        <tr>
                            <td class="py-2 text-gray-600 w-1/3">Nama Lengkap</td>
                            <td class="py-2 text-gray-800 font-medium">: <?= htmlspecialchars($printData['nama_pasien']) ?></td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">NIK</td>
                            <td class="py-2 text-gray-800">: <?= htmlspecialchars($printData['nik']) ?></td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">No. Rekam Medis</td>
                            <td class="py-2 text-gray-800">: <?= htmlspecialchars($printData['no_rm']) ?></td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Tanggal Lahir / Umur</td>
                            <td class="py-2 text-gray-800">: <?= date('d/m/Y', strtotime($printData['tanggal_lahir'])) ?> (<?= hitungUmur($printData['tanggal_lahir']) ?>)</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600">Jenis Kelamin</td>
                            <td class="py-2 text-gray-800">: <?= $printData['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Hasil Pemeriksaan -->
            <div class="my-6">
                <h3 class="font-semibold text-gray-800 mb-3 text-base">Hasil Pemeriksaan:</h3>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="grid grid-cols-4 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Tekanan Darah</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($printData['tensi']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Suhu</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($printData['suhu']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Nadi</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($printData['nadi']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-1">RR</p>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($printData['rr']) ?></p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Keluhan:</p>
                            <p class="text-gray-800"><?= nl2br(htmlspecialchars($printData['keluhan'])) ?></p>
                        </div>
                        
                        <?php if ($printData['pemeriksaan']): ?>
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Pemeriksaan:</p>
                            <p class="text-gray-800"><?= nl2br(htmlspecialchars($printData['pemeriksaan'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Diagnosa -->
            <div class="my-6">
                <h3 class="font-semibold text-gray-800 mb-2">Diagnosa:</h3>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                    <p class="text-gray-800 font-medium"><?= nl2br(htmlspecialchars($printData['diagnosa'])) ?></p>
                </div>
            </div>

            <?php if ($printData['resep_obat']): ?>
            <div class="my-6">
                <h3 class="font-semibold text-gray-800 mb-2">Terapi yang telah diberikan:</h3>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-gray-800"><?= nl2br(htmlspecialchars($printData['resep_obat'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <p class="text-gray-700 mt-6">
                Demikian surat rujukan ini kami buat untuk dapat ditindaklanjuti. Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.
            </p>
        </div>

        <!-- Tanda Tangan -->
        <div class="flex justify-end mt-16">
            <div class="text-center">
                <p class="text-gray-700 mb-1">Bandung, <?= strftime('%d %B %Y', strtotime($printData['tanggal_rujukan'])) ?></p>
                <p class="text-gray-700 mb-20">Dokter yang Merujuk,</p>
                <div class="border-t border-gray-800 pt-2">
                    <p class="font-bold text-gray-800"><?= htmlspecialchars($printData['nama_dokter']) ?></p>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($printData['spesialis']) ?></p>
                    <p class="text-xs text-gray-500 mt-1">NIP: <?= htmlspecialchars($printData['kode_dokter']) ?></p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-4 border-t border-gray-300 text-xs text-gray-500 text-center">
            Dokumen ini dicetak pada <?= date('d/m/Y H:i') ?> WIB | Surat rujukan resmi dari Puskesmas
        </div>
    </div>
</div>

<?php endif; ?>

</body>
</html>