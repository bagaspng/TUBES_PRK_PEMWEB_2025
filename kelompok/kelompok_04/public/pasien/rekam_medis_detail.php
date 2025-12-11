<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pasien') {
    header('Location: ../login.php');
    exit;
}

$id_rekam = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_rekam <= 0) {
    die("Data tidak ditemukan.");
}

$sql = "SELECT r.*, d.nama_dokter, p.nama_poli
        FROM rekam_medis r
        JOIN dokter d ON r.id_dokter = d.id_dokter
        JOIN poli p ON r.id_poli = p.id_poli
        WHERE r.id_rekam = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_rekam);
$stmt->execute();
$rm = $stmt->get_result()->fetch_assoc();
$stmt->close();

$q_rujukan = $conn->query("SELECT * FROM rujukan WHERE id_rekam = '$id_rekam'");
$rujukan = $q_rujukan->fetch_assoc();

if (!$rm) {
    die("Rekam medis tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Rekam Medis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="px-6 py-4 flex items-center gap-4 max-w-2xl mx-auto">
            <a href="rekam_medis.php"
               class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M328 112 184 256l144 144"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-800">Detail Rekam Medis</h1>
        </div>
    </div>

    <div class="px-6 py-6 max-w-2xl mx-auto space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">

            <p class="text-sm text-gray-500 mb-1">
                Tanggal kunjungan: <?php echo date("d M Y", strtotime($rm['tanggal_kunjungan'])); ?>
            </p>

            <p class="text-xl font-semibold text-gray-800 mb-3">
                <?php echo htmlspecialchars($rm['diagnosa']); ?>
            </p>

            <div class="space-y-3 text-sm text-gray-700">

                <p><span class="font-semibold">Poli:</span>
                    <?php echo htmlspecialchars($rm['nama_poli']); ?>
                </p>

                <p><span class="font-semibold">Dokter:</span>
                    dr. <?php echo htmlspecialchars($rm['nama_dokter']); ?>
                </p>

                <p><span class="font-semibold">Keluhan:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['keluhan'])); ?>
                </p>

                <?php if (!empty($rm['td_sistolik']) && ($rm['td_diastolik'])): ?>
                <p><span class="font-semibold">Tensi:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['td_sistolik'] . '/' . $rm['td_diastolik'])); ?>
                    <span class="font"> mmHg</span>
                </p>
                <?php endif; ?>

                <?php if (!empty($rm['suhu'])): ?>
                <p><span class="font-semibold">Suhu:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['suhu'])); ?>
                    <span class="font"> °C</span>
                </p>
                <?php endif; ?>

                <?php if (!empty($rm['nadi'])): ?>
                <p><span class="font-semibold">Nadi:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['nadi'])); ?>
                    <span class="font"> bpm</span>
                </p>
                <?php endif; ?>

                <?php if (!empty($rm['rr'])): ?>
                <p><span class="font-semibold">Pernapasan:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['rr'])); ?>
                    <span class="font"> frekuensi</span>
                </p>
                <?php endif; ?>

                <?php if (!empty($rm['riwayat_penyakit'])): ?>
                <p><span class="font-semibold">Riwayat Penyakit:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['riwayat_penyakit'])); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($rm['pemeriksaan'])): ?>
                <p><span class="font-semibold">Pemeriksaan:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['pemeriksaan'])); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($rm['resep_obat'])): ?>
                <p><span class="font-semibold">Resep Obat:</span><br>
                    <?php echo nl2br(htmlspecialchars($rm['resep_obat'])); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
            <?php if ($rujukan): ?>
            <div class="bg-white p-6 rounded-2xl border border-slate-200">
                <h4 class="font-bold text-slate-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Data Rujukan
                </h4>
                <div class="grid gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Rumah Sakit Tujuan</label>
                        <input type="text" value="<?= $rujukan['faskes_tujuan'] ?>" readonly class="w-full bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-sm text-blue-900 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Poli Tujuan</label>
                        <textarea readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700"><?= $rujukan['poli_tujuan'] ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>
    </div>
    

</body>
</html>
