<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pasien') {
    header('Location: ../login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$sqlPasien = "SELECT p.*, u.email 
              FROM pasien p
              JOIN users u ON p.id_user = u.id_user
              WHERE p.id_user = ?
              LIMIT 1";
$stmt = $conn->prepare($sqlPasien);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pasien = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sqlRM = "SELECT r.*, d.nama_dokter, p.nama_poli
          FROM rekam_medis r
          JOIN dokter d ON r.id_dokter = d.id_dokter
          JOIN poli p ON r.id_poli = p.id_poli
          WHERE r.id_pasien = ?
          ORDER BY r.tanggal_kunjungan DESC";
$stmtRM = $conn->prepare($sqlRM);
$stmtRM->bind_param("i", $pasien['id_pasien']);
$stmtRM->execute();
$dataRM = $stmtRM->get_result();
$stmtRM->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekam Medis Pasien</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="px-6 py-4 flex items-center gap-4 max-w-2xl mx-auto">

            <a href="dashboard.php"
               class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M328 112 184 256l144 144"/>
                </svg>
            </a>

            <h1 class="text-lg font-semibold text-gray-800">Rekam Medis</h1>
        </div>
    </div>

    <div class="px-6 py-6 max-w-2xl mx-auto space-y-6">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex items-center gap-4">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-green-600" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M256 256a112 112 0 1 0-112-112 112.13 112.13 0 0 0 112 112Zm0 32c-70.7 0-208 35.82-208 107.5 0 21.39 8.35 36.5 29.74 36.5h356.52C455.65 432 464 416.89 464 395.5 464 323.82 326.7 288 256 288Z"/>
                </svg>
            </div>

            <div>
                <p class="text-base text-gray-800 font-semibold">
                    <?php echo htmlspecialchars($pasien['nama_lengkap']); ?>
                </p>
                <p class="text-sm text-gray-500">NIK: <?php echo htmlspecialchars($pasien['nik']); ?></p>
            </div>
        </div>

        <div class="space-y-4">
            <?php if ($dataRM->num_rows > 0): ?>
                <?php while ($rm = $dataRM->fetch_assoc()): ?>
                    <a href="rekam_medis_detail.php?id=<?php echo $rm['id_rekam']; ?>"
                       class="block bg-white border border-gray-200 hover:shadow-md transition-shadow rounded-2xl p-5">

                        <div class="flex justify-between items-center mb-1">
                            <p class="text-sm text-gray-500">
                                <?php echo date("d M Y", strtotime($rm['tanggal_kunjungan'])); ?>
                            </p>

                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" viewBox="0 0 512 512" fill="currentColor">
                                <path d="M184 112l144 144-144 144"/>
                            </svg>
                        </div>

                        <p class="text-gray-800 font-medium mb-1">
                            <?php echo htmlspecialchars($rm['diagnosa']); ?>
                        </p>

                        <p class="text-sm text-gray-600 mb-1">
                            Poli <?php echo htmlspecialchars($rm['nama_poli']); ?>
                        </p>

                        <p class="text-xs text-gray-500 mb-2">
                            dr. <?php echo htmlspecialchars($rm['nama_dokter']); ?>
                        </p>

                        <?php if (!empty($rm['resep_obat'])): ?>
                        <p class="text-xs text-gray-500">
                            Resep: <?php echo htmlspecialchars($rm['resep_obat']); ?>
                        </p>
                        <?php endif; ?>

                    </a>
                <?php endwhile; ?>

            <?php else: ?>
                <p class="text-sm text-gray-500 text-center">Belum ada rekam medis.</p>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
