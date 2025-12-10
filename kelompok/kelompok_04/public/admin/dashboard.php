<?php
// public/admin/dashboard.php
session_start();

require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$idUser = (int) $_SESSION['user_id'];

$sqlUser = "SELECT username, email, role FROM users WHERE id_user = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param('i', $idUser);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$admin   = $resUser->fetch_assoc();
$stmtUser->close();

$adminName = $admin['username'] ?? 'Admin';

function getCount($conn, $sql) {
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        return (int) ($row['c'] ?? 0);
    }
    return 0;
}

$totalDokter        = getCount($conn, "SELECT COUNT(*) AS c FROM dokter");
$totalPoli          = getCount($conn, "SELECT COUNT(*) AS c FROM poli");
$totalPasien        = getCount($conn, "SELECT COUNT(*) AS c FROM pasien");
$kunjunganHariIni   = getCount($conn, "SELECT COUNT(*) AS c FROM rekam_medis WHERE tanggal_kunjungan = CURDATE()");
$totalPengumuman    = getCount($conn, "SELECT COUNT(*) AS c FROM pengumuman");

$sqlAktivitas = "
    SELECT 
        CONCAT('Pasien baru terdaftar: ', p.nama_lengkap) AS teks,
        p.created_at AS waktu,
        'pasien' AS tipe
    FROM pasien p

    UNION ALL

    SELECT 
        CONCAT('Pengumuman: ', pg.judul) AS teks,
        pg.created_at AS waktu,
        'pengumuman' AS tipe
    FROM pengumuman pg

    UNION ALL

    SELECT 
        CONCAT('Jadwal praktik diperbarui (ID: ', jp.id_jadwal, ')') AS teks,
        jp.updated_at AS waktu,
        'jadwal' AS tipe
    FROM jadwal_praktik jp

    ORDER BY waktu DESC
    LIMIT 5
";

$aktivitas = [];
if ($resultAkt = $conn->query($sqlAktivitas)) {
    while ($row = $resultAkt->fetch_assoc()) {
        $aktivitas[] = $row;
    }
    $resultAkt->free();
}

function waktuRelatif($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return $diff . ' detik yang lalu';
    if ($diff < 3600) return floor($diff / 60) . ' menit yang lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam yang lalu';

    return date('d M Y H:i', $time);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Puskesmas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100 font-sans">

<div class="min-h-screen flex">
    <!-- SIDEBAR (include reusable file) -->
    <?php
        // Tandai menu aktif untuk sidebar
        $active = 'dashboard';
        include __DIR__ . '/sidebar.php';
    ?>

    <!-- MAIN AREA -->
    <div class="flex-1 flex flex-col">
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Admin Dashboard</h1>
                <p class="text-xs text-gray-500">Selamat datang, <?php echo htmlspecialchars($adminName); ?></p>
            </div>
        </header>

       <main class="flex-1 flex flex-col px-4 md:px-6 py-6 w-full">
            <!-- STAT CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6 w-full">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-blue-50 text-blue-700 rounded-xl flex items-center justify-center mb-3 text-sm font-semibold">
                        Dr
                    </div>
                    <div class="text-2xl text-gray-800 mb-1"><?php echo $totalDokter; ?></div>
                    <p class="text-xs text-gray-600">Total Dokter</p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-green-50 text-green-700 rounded-xl flex items-center justify-center mb-3 text-sm font-semibold">
                        Po
                    </div>
                    <div class="text-2xl text-gray-800 mb-1"><?php echo $totalPoli; ?></div>
                    <p class="text-xs text-gray-600">Total Poli</p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-purple-50 text-purple-700 rounded-xl flex items-center justify-center mb-3 text-sm font-semibold">
                        Ps
                    </div>
                    <div class="text-2xl text-gray-800 mb-1"><?php echo $totalPasien; ?></div>
                    <p class="text-xs text-gray-600">Total Pasien</p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center mb-3 text-sm font-semibold">
                        Kj
                    </div>
                    <div class="text-2xl text-gray-800 mb-1"><?php echo $kunjunganHariIni; ?></div>
                    <p class="text-xs text-gray-600">Kunjungan Hari Ini</p>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-xl flex items-center justify-center mb-3 text-sm font-semibold">
                        Pg
                    </div>
                    <div class="text-2xl text-gray-800 mb-1"><?php echo $totalPengumuman; ?></div>
                    <p class="text-xs text-gray-600">Pengumuman</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- AKTIVITAS TERBARU -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-gray-800 text-base font-semibold">Aktivitas Terbaru</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($aktivitas)): ?>
                                <p class="text-sm text-gray-500">Belum ada aktivitas tercatat.</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($aktivitas as $act): ?>
                                        <div class="flex items-start gap-4">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                                                <?php
                                                    if ($act['tipe'] === 'pasien')      echo 'bg-green-100 text-green-600';
                                                    elseif ($act['tipe'] === 'pengumuman') echo 'bg-orange-100 text-orange-600';
                                                    else                                  echo 'bg-blue-100 text-blue-600';
                                                ?>">
                                                <span class="text-xs font-semibold uppercase">
                                                    <?php echo strtoupper(substr($act['tipe'],0,2)); ?>
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-800">
                                                    <?php echo htmlspecialchars($act['teks']); ?>
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    <?php echo waktuRelatif($act['waktu']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- AKSI CEPAT -->
                <div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-gray-800 text-base font-semibold">Aksi Cepat</h2>
                        </div>
                        <div class="p-6 space-y-3">
                            <a href="dokter_tambah.php" class="bg-blue-700 text-white px-4 py-4 rounded-xl hover:opacity-90 transition-opacity flex items-center gap-3 shadow-sm text-sm">
                                <span>Tambah Dokter</span>
                                <span class="ml-auto text-lg font-semibold">+</span>
                            </a>
                            <a href="poli_tambah.php" class="bg-green-500 text-white px-4 py-4 rounded-xl hover:opacity-90 transition-opacity flex items-center gap-3 shadow-sm text-sm">
                                <span>Tambah Poli</span>
                                <span class="ml-auto text-lg font-semibold">+</span>
                            </a>
                            <a href="artikel_tambah.php" class="bg-purple-500 text-white px-4 py-4 rounded-xl hover:opacity-90 transition-opacity flex items-center gap-3 shadow-sm text-sm">
                                <span>Tambah Artikel</span>
                                <span class="ml-auto text-lg font-semibold">+</span>
                            </a>
                            <a href="pengumuman_tambah.php" class="bg-orange-500 text-white px-4 py-4 rounded-xl hover:opacity-90 transition-opacity flex items-center gap-3 shadow-sm text-sm">
                                <span>Tambah Pengumuman</span>
                                <span class="ml-auto text-lg font-semibold">+</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>

</script>
</body>
</html>
