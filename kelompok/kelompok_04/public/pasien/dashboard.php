<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pasien') {
    header('Location: ../login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$bookingError = '';
$bookingSuccess = '';

$sqlPasien = "SELECT p.*, u.email 
              FROM pasien p 
              JOIN users u ON p.id_user = u.id_user
              WHERE p.id_user = ? LIMIT 1";
$stmtPasien = $conn->prepare($sqlPasien);
$stmtPasien->bind_param('i', $user_id);
$stmtPasien->execute();
$resPasien = $stmtPasien->get_result();
$pasien = $resPasien->fetch_assoc();
$stmtPasien->close();

if (!$pasien) {
    $pasien = [
        'nama_lengkap' => 'Pasien',
        'nik' => '',
        'id_pasien' => 0
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ambil_antrian') {
    $id_poli_post    = (int) ($_POST['id_poli'] ?? 0);
    $jam_mulai_post  = trim($_POST['jam_mulai'] ?? '');
    $tanggal_post    = trim($_POST['tanggal'] ?? '');

    if ($id_poli_post <= 0 || $jam_mulai_post === '' || $tanggal_post === '') {
        $bookingError = 'Semua field wajib diisi untuk ambil antrian.';
    } else {

        $timestamp = strtotime($tanggal_post);
        $hariIdx = date('N', $timestamp); 
        $mapHari = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];
        $hariEnum = $mapHari[$hariIdx] ?? 'Senin';

        $sqlJadwal = "SELECT j.id_jadwal, j.id_dokter
                      FROM jadwal_praktik j
                      JOIN dokter d ON j.id_dokter = d.id_dokter
                      WHERE d.id_poli = ? 
                        AND j.hari = ? 
                        AND j.jam_mulai = ?
                        AND j.status = 'active'
                      LIMIT 1";
        $stmtJadwal = $conn->prepare($sqlJadwal);
        $stmtJadwal->bind_param('iss', $id_poli_post, $hariEnum, $jam_mulai_post);
        $stmtJadwal->execute();
        $resJadwal = $stmtJadwal->get_result();
        $jadwal = $resJadwal->fetch_assoc();
        $stmtJadwal->close();

        if (!$jadwal) {
            $bookingError = 'Tidak ditemukan jadwal praktik untuk poli dan jam yang dipilih.';
        } else {
            $id_jadwal = (int) $jadwal['id_jadwal'];

            $sqlCheck = "SELECT id_antrian 
                         FROM antrian 
                         WHERE id_pasien = ? 
                           AND id_jadwal = ?
                           AND DATE(waktu_daftar) = ?
                           AND status IN ('menunggu','diperiksa')
                         LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param('iis', $pasien['id_pasien'], $id_jadwal, $tanggal_post);
            $stmtCheck->execute();
            $stmtCheck->store_result();

            if ($stmtCheck->num_rows > 0) {
                $bookingError = 'Anda sudah memiliki antrian aktif untuk jadwal tersebut.';
            }
            $stmtCheck->close();

           if ($bookingError === '') {
            $sqlMaxNo = "SELECT COALESCE(MAX(a.nomor_antrian), 0) AS max_no
                        FROM antrian a
                        JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal
                        JOIN dokter d ON j.id_dokter = d.id_dokter
                        WHERE d.id_poli = ?
                        AND DATE(a.waktu_daftar) = ?";
        
            $stmtMax = $conn->prepare($sqlMaxNo);
            $stmtMax->bind_param('is', $id_poli_post, $tanggal_post); 
            $stmtMax->execute();
            $resMax = $stmtMax->get_result();
            $rowMax = $resMax->fetch_assoc();
            $stmtMax->close();

            $nextNo = ((int) $rowMax['max_no']) + 1;

                $now = date('Y-m-d H:i:s');
                $waktu_daftar = $tanggal_post . ' ' . date('H:i:s');

                $sqlIns = "INSERT INTO antrian
                           (id_jadwal, id_pasien, nomor_antrian, waktu_daftar, status, created_at, updated_at)
                           VALUES (?, ?, ?, ?, 'menunggu', ?, ?)";
                $stmtIns = $conn->prepare($sqlIns);
                $stmtIns->bind_param('iiisss', $id_jadwal, $pasien['id_pasien'], $nextNo, $waktu_daftar, $now, $now);

                if ($stmtIns->execute()) {
                    $bookingSuccess = 'Antrian berhasil dibuat. Nomor antrian Anda: ' . $nextNo;
                } else {
                    $bookingError = 'Gagal membuat antrian: ' . $stmtIns->error;
                }

                $stmtIns->close();
            }
        }
    }
}

$today = date('Y-m-d');
$sqlAntrian = "SELECT a.*, 
                      p.nama_poli,
                      d.nama_dokter,
                      j.jam_mulai,
                      j.jam_selesai
               FROM antrian a
               JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal
               JOIN dokter d ON j.id_dokter = d.id_dokter
               JOIN poli p ON d.id_poli = p.id_poli
               WHERE a.id_pasien = ?
                 AND DATE(a.waktu_daftar) = ?
                 AND a.status IN ('menunggu','diperiksa')
               ORDER BY a.waktu_daftar ASC";
$stmtAntri = $conn->prepare($sqlAntrian);
$stmtAntri->bind_param('is', $pasien['id_pasien'], $today);
$stmtAntri->execute();
$resAntri = $stmtAntri->get_result();
$semuaAntrian = [];
while ($row = $resAntri->fetch_assoc()) {
    $semuaAntrian[] = $row;
}
$stmtAntri->close();
$hasQueue = count($semuaAntrian) > 0;

foreach ($semuaAntrian as &$antrian) {

    $sqlCurr = "SELECT MIN(nomor_antrian) AS curr_no
                FROM antrian
                WHERE id_jadwal = ?
                  AND DATE(waktu_daftar) = ?
                  AND status IN ('menunggu','diperiksa')";
    $stmtCurr = $conn->prepare($sqlCurr);
    $stmtCurr->bind_param('is', $antrian['id_jadwal'], $today);
    $stmtCurr->execute();
    $resCurr = $stmtCurr->get_result();
    $rowCurr = $resCurr->fetch_assoc();
    $stmtCurr->close();

    $antrian['antrian_saat_ini'] = $rowCurr['curr_no'] ?? null;

    if ($antrian['antrian_saat_ini'] !== null) {
        $selisih = (int)$antrian['nomor_antrian'] - (int)$antrian['antrian_saat_ini'];
        if ($selisih < 0) $selisih = 0;
        $antrian['estimasi_menit'] = $selisih * 15;
    } else {
        $antrian['estimasi_menit'] = null;
    }
}

$hariIdx = date('N');
$mapHari = [
    1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
    5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
];
$hariEnumToday = $mapHari[$hariIdx] ?? 'Senin';

$sqlJadwalPoli = "SELECT DISTINCT p.nama_poli, j.jam_mulai, j.jam_selesai
                  FROM jadwal_praktik j
                  JOIN dokter d ON j.id_dokter = d.id_dokter
                  JOIN poli p ON d.id_poli = p.id_poli
                  WHERE j.hari = ? AND j.status = 'active'";
$stmtJPoli = $conn->prepare($sqlJadwalPoli);
$stmtJPoli->bind_param('s', $hariEnumToday);
$stmtJPoli->execute();
$resJPoli = $stmtJPoli->get_result();
$jadwalPoli = [];
while ($row = $resJPoli->fetch_assoc()) {
    $jadwalPoli[] = $row;
}
$stmtJPoli->close();

$sqlJadwalDokter = "SELECT d.nama_dokter, p.nama_poli, j.jam_mulai, j.jam_selesai
                    FROM jadwal_praktik j
                    JOIN dokter d ON j.id_dokter = d.id_dokter
                    JOIN poli p ON d.id_poli = p.id_poli
                    WHERE j.hari = ? AND j.status = 'active'";
$stmtJDok = $conn->prepare($sqlJadwalDokter);
$stmtJDok->bind_param('s', $hariEnumToday);
$stmtJDok->execute();
$resJDok = $stmtJDok->get_result();
$jadwalDokter = [];
while ($row = $resJDok->fetch_assoc()) {
    $jadwalDokter[] = $row;
}
$stmtJDok->close();

$sqlPeng = "SELECT id_pengumuman, judul, isi, gambar, tanggal
            FROM pengumuman
            WHERE status = 'publish'
            ORDER BY tanggal DESC
            LIMIT 3";
$resPeng = $conn->query($sqlPeng);
$artikel = [];
while ($row = $resPeng->fetch_assoc()) {
    $row['ringkasan'] = mb_strimwidth(strip_tags($row['isi']), 0, 80, '...');
    $artikel[] = $row;
}

function getImagePath($gambar) {
    if (!empty($gambar) && file_exists(__DIR__ . '/../../' . $gambar)) {
        return '../../' . $gambar;
    }
    return 'https://images.unsplash.com/photo-1580281658627-7665a298f61a?q=80&w=200';
}

$sqlRM = "SELECT r.id_rekam, r.tanggal_kunjungan, r.diagnosa, d.nama_dokter
          FROM rekam_medis r
          JOIN dokter d ON r.id_dokter = d.id_dokter
          WHERE r.id_pasien = ?
          ORDER BY r.tanggal_kunjungan DESC
          LIMIT 3";
$stmtRM = $conn->prepare($sqlRM);
$stmtRM->bind_param('i', $pasien['id_pasien']);
$stmtRM->execute();
$resRM = $stmtRM->get_result();
$rekamMedis = [];
while ($row = $resRM->fetch_assoc()) {
    $rekamMedis[] = $row;
}
$stmtRM->close();

$sqlPoli = "SELECT id_poli, nama_poli FROM poli ORDER BY nama_poli ASC";
$resPoli = $conn->query($sqlPoli);
$poliOptions = [];
while ($row = $resPoli->fetch_assoc()) {
    $poliOptions[] = $row;
}

$sqlJadwalPerPoli = "SELECT DISTINCT d.id_poli, j.jam_mulai, j.jam_selesai, j.hari
                     FROM jadwal_praktik j
                     JOIN dokter d ON j.id_dokter = d.id_dokter
                     WHERE j.status = 'active'
                     ORDER BY d.id_poli, j.jam_mulai";
$resJadwalPerPoli = $conn->query($sqlJadwalPerPoli);
$jadwalPerPoli = [];
while ($row = $resJadwalPerPoli->fetch_assoc()) {
    $pid = $row['id_poli'];
    if (!isset($jadwalPerPoli[$pid])) {
        $jadwalPerPoli[$pid] = [];
    }
    $jadwalPerPoli[$pid][] = [
        'jam_mulai' => $row['jam_mulai'],
        'jam_selesai' => $row['jam_selesai'],
        'hari' => $row['hari']
    ];
}
$jadwalPerPoliJson = json_encode($jadwalPerPoli);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Pasien - Puskesmas Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="bg-white border-b border-gray-100 sticky top-0 z-10">
        <div class="px-6 py-4 flex items-center max-w-2xl mx-auto">
            <div class="w-10 h-10 flex">
                <img src="../img/puskesmas.svg" alt="Logo Puskesmas" class="w-full h-full object-contain drop-shadow-lg">
            </div>
                <h2 class="text-gray-800 font-semibold">Puskesmas</h2>
                <div class="flex items-center ml-auto">
                <a href="profil.php">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-600" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M256 256a112 112 0 1 0-112-112 112.13 112.13 0 0 0 112 112Zm0 32c-70.7 0-208 35.82-208 107.5 0 21.39 8.35 36.5 29.74 36.5h356.52C455.65 432 464 416.89 464 395.5 464 323.82 326.7 288 256 288Z"/>
                </svg>
                </a>
                </div>
            </div>
        </div>
    </div>

    <div class="px-6 py-6 max-w-2xl mx-auto space-y-8">

        <div>
            <h1 class="text-xl font-semibold text-gray-800 mb-1">
                Halo, <?php echo htmlspecialchars($pasien['nama_lengkap']); ?>
            </h1>
            <p class="text-gray-500 text-sm">Status layanan & informasi kesehatan Anda.</p>
        </div>

        <?php if ($bookingError): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($bookingError); ?>
            </div>
        <?php elseif ($bookingSuccess): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($bookingSuccess); ?>
            </div>
        <?php endif; ?>

        <div>
            <div class="grid grid-cols-4 gap-4">
                <button type="button" onclick="openAntrianModal()" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-[#45BC7D] rounded-2xl flex items-center justify-center shadow-md hover:shadow-lg transition-shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 512 512" fill="currentColor">
                            <rect x="48" y="80" width="416" height="384" rx="48" ry="48" fill="none" stroke="currentColor" stroke-width="32"/>
                            <path d="M128 48v64M384 48v64" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round"/>
                            <path d="M464 160H48" fill="none" stroke="currentColor" stroke-width="32"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-700 text-center">Antri</span>
                </button>

                <a href="#status-antrian" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-[#496A9A] rounded-2xl flex items-center justify-center shadow-md hover:shadow-lg transition-shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 512 512" fill="currentColor">
                            <path d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64Zm16 208H224V144h32Zm0 96H224V336h32Z"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-700 text-center">Status</span>
                </a>

                <a href="rekam_medis.php" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-[#45BC7D] rounded-2xl flex items-center justify-center shadow-md hover:shadow-lg transition-shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 512 512" fill="currentColor">
                            <path d="M368 64H144a32 32 0 0 0-32 32v320a32 32 0 0 0 32 32h224a32 32 0 0 0 32-32V128ZM192 192h128M192 256h96M192 320h64" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-700 text-center">Rekam</span>
                </a>

                <a href="pengumuman.php" class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 bg-[#496A9A] rounded-2xl flex items-center justify-center shadow-md hover:shadow-lg transition-shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-white" viewBox="0 0 512 512" fill="currentColor">
                            <path d="M96 112h320v288a32 32 0 0 1-32 32H128a32 32 0 0 1-32-32Z" fill="none" stroke="currentColor" stroke-width="32"/>
                            <path d="M160 160h192M160 208h192M160 256h128" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <span class="text-xs text-gray-700 text-center">Pengumuman</span>
                </a>
            </div>
        </div>
<div id="status-antrian">
    <?php if ($hasQueue): ?>
        <?php 
        $antrianUtama = $semuaAntrian[0]; 
        ?>
        <div class="bg-gradient-to-r from-[#45BC7D] to-[#3aa668] rounded-2xl p-6 text-white shadow-md">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <p class="text-sm opacity-90 mb-1">Antrian Anda</p>
                    <div class="text-3xl font-semibold mb-2">
                        <?php echo 'A-' . str_pad($antrianUtama['nomor_antrian'], 3, '0', STR_PAD_LEFT); ?>
                    </div>
                    <p class="text-sm opacity-90 mb-1">
                        <?php echo htmlspecialchars($antrianUtama['nama_poli']); ?>
                    </p>
                    <p class="text-xs opacity-75">
                        <?php echo htmlspecialchars($antrianUtama['nama_dokter']); ?>
                    </p>
                </div>
                <div class="bg-white/20 px-3 py-1 rounded-lg text-sm capitalize">
                    <?php echo htmlspecialchars($antrianUtama['status']); ?>
                </div>
            </div>
            
            <div class="space-y-2 text-sm border-t border-white/20 pt-3">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 512 512" fill="currentColor">
                        <path d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64Zm16 208H224V144h32Z"/>
                    </svg>
                    <span>
                        Jam Praktik: <?php echo substr($antrianUtama['jam_mulai'], 0, 5) . ' - ' . substr($antrianUtama['jam_selesai'], 0, 5); ?>
                    </span>
                </div>
                
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 512 512" fill="currentColor">
                        <path d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64Zm16 208H224V144h32Z"/>
                    </svg>
                    <span>
                        Estimasi Tunggu: <?php echo $antrianUtama['estimasi_menit'] !== null ? $antrianUtama['estimasi_menit'] . ' menit' : '-'; ?>
                    </span>
                </div>
                
                <?php if ($antrianUtama['antrian_saat_ini'] !== null): ?>
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 512 512" fill="currentColor">
                        <path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48Zm95.6 145.6-112 112a16 16 0 0 1-22.6 0l-56-56a16 16 0 0 1 22.6-22.6L232 274.34l100.69-100.68a16 16 0 0 1 22.6 22.6Z"/>
                    </svg>
                    <span>Antrian Dipanggil: <?php echo 'A-' . str_pad($antrianUtama['antrian_saat_ini'], 3, '0', STR_PAD_LEFT); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-gray-400" viewBox="0 0 512 512" fill="currentColor">
                        <path d="M448 256c0 106-86 192-192 192S64 362 64 256 150 64 256 64s192 86 192 192Zm-208-96v112h32V160Zm0 160v32h32V320Z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-700 mb-4">
                    Anda belum mengambil antrian hari ini.
                </p>
                <button type="button" onclick="openAntrianModal()" class="bg-[#45BC7D] text-white px-6 py-3 rounded-xl hover:bg-[#3aa668] transition-colors text-sm font-semibold">
                    Ambil Antrian Sekarang
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

        <div>
            <h3 class="text-gray-800 mb-4 font-semibold">Jadwal Layanan Hari Ini (<?php echo $hariEnumToday; ?>)</h3>

            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-3">Poli</p>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <?php if ($jadwalPoli): ?>
                        <?php foreach ($jadwalPoli as $jp): ?>
                            <div class="bg-white rounded-xl p-4 min-w-[160px] shadow-sm border border-gray-100">
                                <div class="flex items-start justify-between mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#45BC7D]" viewBox="0 0 512 512" fill="currentColor">
                                        <path d="M352 96h80a48 48 0 0 1 48 48v256a48 48 0 0 1-48 48H80a48 48 0 0 1-48-48V144a48 48 0 0 1 48-48h80V80a48 48 0 0 1 48-48h96a48 48 0 0 1 48 48ZM208 80v16h96V80Z"/><path d="M296 208h-40v-40h-32v40h-40v32h40v40h32v-40h40Z"/>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#45BC7D]" viewBox="0 0 512 512" fill="currentColor">
                                        <path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48Zm95.6 145.6-112 112a16 16 0 0 1-22.6 0l-56-56a16 16 0 0 1 22.6-22.6L232 274.34l100.69-100.68a16 16 0 0 1 22.6 22.6Z"/>
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-800 mb-1"><?php echo htmlspecialchars($jp['nama_poli']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php echo substr($jp['jam_mulai'],0,5) . ' - ' . substr($jp['jam_selesai'],0,5); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-xs text-gray-500">Tidak ada jadwal poli aktif hari ini.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <p class="text-sm text-gray-600 mb-3">Dokter Bertugas</p>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
                    <?php if ($jadwalDokter): ?>
                        <?php foreach ($jadwalDokter as $jd): ?>
                            <div class="p-4 flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-800 mb-1"><?php echo htmlspecialchars($jd['nama_dokter']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($jd['nama_poli']); ?></p>
                                </div>
                                <p class="text-xs text-gray-600">
                                    <?php echo substr($jd['jam_mulai'],0,5) . ' - ' . substr($jd['jam_selesai'],0,5); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-xs text-gray-500 p-4">Tidak ada jadwal dokter aktif hari ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-gray-800 mb-4 font-semibold">Pengumuman</h3>
            <div class="space-y-3">
                <?php if ($artikel): ?>
                    <?php foreach ($artikel as $item): ?>
                        <a href="pengumuman_detail.php?id=<?php echo $item['id_pengumuman']; ?>"
                           class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow block overflow-hidden">
                            <div class="flex gap-3">
                                <div class="w-24 h-24 flex-shrink-0 bg-gray-200">
                                    <img src="<?php echo getImagePath($item['gambar']); ?>" 
                                         class="w-full h-full object-cover" 
                                         alt="<?php echo htmlspecialchars($item['judul']); ?>">
                                </div>
                                <div class="flex-1 p-4 pl-0">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="text-sm text-gray-800 pr-4 line-clamp-2"><?php echo htmlspecialchars($item['judul']); ?></p>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 flex-shrink-0" viewBox="0 0 512 512" fill="currentColor">
                                            <path d="M184 112l144 144-144 144"/>
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-2 line-clamp-2"><?php echo htmlspecialchars($item['ringkasan']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo date('d M Y', strtotime($item['tanggal'])); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-gray-500">Belum ada pengumuman.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="pb-10">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-gray-800 font-semibold">Rekam Medis Terakhir</h3>
                <a href="rekam_medis.php" class="text-xs text-[#496A9A] hover:underline">Lihat Semua</a>
            </div>
            <div class="space-y-3">
                <?php if ($rekamMedis): ?>
                    <?php foreach ($rekamMedis as $rm): ?>
                        <a href="rekam_medis_detail.php?id=<?php echo $rm['id_rekam']; ?>"
                           class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 block hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-1"><?php echo date('d M Y', strtotime($rm['tanggal_kunjungan'])); ?></p>
                                    <p class="text-sm text-gray-800 mb-1"><?php echo htmlspecialchars($rm['diagnosa']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($rm['nama_dokter']); ?></p>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 flex-shrink-0 mt-1" viewBox="0 0 512 512" fill="currentColor">
                                    <path d="M184 112l144 144-144 144"/>
                                </svg>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-xs text-gray-500">Belum ada riwayat rekam medis.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<div id="antrianModal" class="fixed inset-0 bg-black/50 items-center justify-center z-50 px-6 hidden">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-100 sticky top-0 bg-white">
            <h2 class="text-gray-800 font-semibold">Ambil Antrian</h2>
            <button type="button" onclick="closeAntrianModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M368 368 144 144M368 144 144 368" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        
        <form method="post" action="" class="p-6 space-y-5">
            <input type="hidden" name="action" value="ambil_antrian">
            <input type="hidden" name="tanggal" value="<?php echo date('Y-m-d'); ?>">

            <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-600" viewBox="0 0 512 512" fill="currentColor">
                            <rect x="48" y="80" width="416" height="384" rx="48" ry="48" fill="none" stroke="currentColor" stroke-width="32"/>
                            <path d="M128 48v64M384 48v64" fill="none" stroke="currentColor" stroke-width="32" stroke-linecap="round"/>
                            <path d="M464 160H48" fill="none" stroke="currentColor" stroke-width="32"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-blue-600 mb-0.5">Antrian Hari Ini</p>
                        <p class="text-sm font-semibold text-blue-900" id="hariInfo"></p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#45BC7D]" viewBox="0 0 512 512" fill="currentColor">
                            <path d="M352 96h80a48 48 0 0 1 48 48v256a48 48 0 0 1-48 48H80a48 48 0 0 1-48-48V144a48 48 0 0 1 48-48h80V80a48 48 0 0 1 48-48h96a48 48 0 0 1 48 48Z"/>
                        </svg>
                        Pilih Poli
                    </span>
                </label>
                <select name="id_poli" id="selectPoli" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent" required>
                    <option value="">-- Pilih Poli --</option>
                    <?php foreach ($poliOptions as $p): ?>
                        <option value="<?php echo $p['id_poli']; ?>">
                            <?php echo htmlspecialchars($p['nama_poli']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="jam_mulai" id="inputJamMulai">

            <div id="jamContainer" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#45BC7D]" viewBox="0 0 512 512" fill="currentColor">
                            <path d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64zm74 238h-84a6 6 0 0 1-6-6v-84a6 6 0 0 1 6-6h12a6 6 0 0 1 6 6v66h66a6 6 0 0 1 6 6v12a6 6 0 0 1-6 6z"/>
                        </svg>
                        Pilih Jam Praktik
                    </span>
                </label>
                <div id="jamList" class="space-y-2"></div>
                <div id="jamKosong" class="hidden bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-yellow-600 mx-auto mb-2" viewBox="0 0 512 512" fill="currentColor">
                        <path d="M85.57 446.25h340.86a32 32 0 0 0 28.17-47.17L284.18 82.58c-12.09-22.44-44.27-22.44-56.36 0L57.4 399.08a32 32 0 0 0 28.17 47.17Z" fill="none" stroke="currentColor" stroke-width="32"/>
                        <path d="M250.26 195.39l5.74 122 5.73-121.95a5.74 5.74 0 0 0-5.79-6h0a5.74 5.74 0 0 0-5.68 5.95Z" stroke="currentColor" stroke-width="16"/>
                        <path d="M256 397.25a20 20 0 1 1 20-20 20 20 0 0 1-20 20Z"/>
                    </svg>
                    <p class="text-sm text-yellow-800 font-medium">Tidak ada jadwal tersedia</p>
                    <p class="text-xs text-yellow-600 mt-1">Poli ini tidak buka hari ini</p>
                </div>
            </div>

            <div id="summaryBox" class="hidden bg-gradient-to-r from-[#45BC7D]/10 to-[#3aa668]/10 border border-[#45BC7D]/30 rounded-xl p-4">
                <div class="space-y-1.5 text-sm">
                </div>
            </div>

            <button type="submit" id="btnSubmit" class="w-full bg-[#45BC7D] text-white py-3 rounded-xl hover:bg-[#3aa668] transition-colors shadow-md text-sm font-semibold disabled:bg-gray-300 disabled:cursor-not-allowed" disabled>
                <span class="flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 512 512" fill="currentColor">
                        <path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm95.6 145.6-112 112a16 16 0 0 1-22.6 0l-56-56a16 16 0 0 1 22.6-22.6L232 274.34l100.69-100.68a16 16 0 0 1 22.6 22.6Z"/>
                    </svg>
                    Konfirmasi & Ambil Antrian
                </span>
            </button>
        </form>
    </div>
</div>

<script>
    const jadwalPerPoli = <?php echo $jadwalPerPoliJson ?: '{}'; ?>;
    const selectPoli = document.getElementById('selectPoli');
    const inputJamMulai = document.getElementById('inputJamMulai');
    const jamContainer = document.getElementById('jamContainer');
    const jamList = document.getElementById('jamList');
    const jamKosong = document.getElementById('jamKosong');
    const btnSubmit = document.getElementById('btnSubmit');
    const hariInfo = document.getElementById('hariInfo');
    const summaryBox = document.getElementById('summaryBox');
    const summaryPoli = document.getElementById('summaryPoli');
    const summaryJam = document.getElementById('summaryJam');

    const mapHari = {
        0: 'Minggu', 1: 'Senin', 2: 'Selasa', 3: 'Rabu',
        4: 'Kamis', 5: 'Jumat', 6: 'Sabtu'
    };

    const bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                       'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // Tanggal hari ini
    const today = new Date();
    const hariIdx = today.getDay();
    const namaHari = mapHari[hariIdx];

    function openAntrianModal() {
        document.getElementById('antrianModal').classList.remove('hidden');
        document.getElementById('antrianModal').classList.add('flex');
        updateHariInfo();
    }

    function closeAntrianModal() {
        document.getElementById('antrianModal').classList.add('hidden');
        document.getElementById('antrianModal').classList.remove('flex');
        // Reset form
        document.querySelector('#antrianModal form').reset();
        jamContainer.classList.add('hidden');
        jamList.innerHTML = '';
        inputJamMulai.value = '';
        btnSubmit.disabled = true;
        summaryBox.classList.add('hidden');
    }

    function updateHariInfo() {
        const tgl = today.getDate();
        const bln = bulanIndo[today.getMonth()];
        const thn = today.getFullYear();
        
        hariInfo.textContent = `${namaHari}, ${tgl} ${bln} ${thn}`;
    }

    function updateSummary() {
        const poliText = selectPoli.options[selectPoli.selectedIndex]?.text || '-';
        const jamVal = inputJamMulai.value;

        if (poliText !== '-- Pilih Poli --' && jamVal) {
            summaryPoli.textContent = poliText;
            summaryJam.textContent = jamVal.substring(0, 5);
            summaryBox.classList.remove('hidden');
        } else {
            summaryBox.classList.add('hidden');
        }
    }

    function updateJamList() {
        const poliId = selectPoli.value;

        jamList.innerHTML = '';
        jamKosong.classList.add('hidden');
        jamContainer.classList.add('hidden');
        inputJamMulai.value = '';
        btnSubmit.disabled = true;
        summaryBox.classList.add('hidden');

        if (!poliId) return;

        if (!jadwalPerPoli[poliId]) {
            jamKosong.classList.remove('hidden');
            jamContainer.classList.remove('hidden');
            return;
        }

        // Filter jadwal berdasarkan hari ini
        const jadwalFiltered = jadwalPerPoli[poliId].filter(j => j.hari === namaHari);

        if (jadwalFiltered.length === 0) {
            jamKosong.classList.remove('hidden');
            jamContainer.classList.remove('hidden');
            return;
        }

        jamContainer.classList.remove('hidden');
        
        jadwalFiltered.forEach((jadwal, index) => {
            const jamMulai = jadwal.jam_mulai.substring(0, 5);
            const jamSelesai = jadwal.jam_selesai.substring(0, 5);
            
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl hover:border-[#45BC7D] hover:bg-[#45BC7D]/5 transition-all text-left flex items-center justify-between group';
            btn.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-[#45BC7D]/10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-[#45BC7D]" viewBox="0 0 512 512" fill="currentColor">
                            <path d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64zm74 238h-84a6 6 0 0 1-6-6v-84a6 6 0 0 1 6-6h12a6 6 0 0 1 6 6v66h66a6 6 0 0 1 6 6v12a6 6 0 0 1-6 6z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">${jamMulai} - ${jamSelesai}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Klik untuk memilih</p>
                    </div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 check-icon hidden" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm95.6 145.6-112 112a16 16 0 0 1-22.6 0l-56-56a16 16 0 0 1 22.6-22.6L232 274.34l100.69-100.68a16 16 0 0 1 22.6 22.6Z"/>
                </svg>
            `;
            
            btn.addEventListener('click', function() {
                // Remove active state from all buttons
                document.querySelectorAll('#jamList button').forEach(b => {
                    b.classList.remove('border-[#45BC7D]', 'bg-[#45BC7D]/10');
                    b.querySelector('.check-icon').classList.add('hidden');
                });
                
                // Add active state to clicked button
                this.classList.add('border-[#45BC7D]', 'bg-[#45BC7D]/10');
                this.querySelector('.check-icon').classList.remove('hidden');
                
                // Set hidden input value
                inputJamMulai.value = jadwal.jam_mulai;
                btnSubmit.disabled = false;
                updateSummary();
            });
            
            jamList.appendChild(btn);
        });
    }

    selectPoli.addEventListener('change', () => {
        updateJamList();
        updateSummary();
    });

    // Initialize hari info on page load
    updateHariInfo();
</script>
</body>
</html>
