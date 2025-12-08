<?php
// public/admin/jadwal_praktik.php

session_start();
require_once __DIR__ . '/../../src/config/database.php';

// Wajib login & role admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Helper flash message
function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}
function get_flash($type) {
    if (!empty($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}


// Filter hari
$selectedDay = trim($_GET['hari'] ?? 'all');
$validDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Query jadwal praktik dengan join ke dokter dan poli
$whereSql = '';
$params = [];
$types = '';

if ($selectedDay !== 'all' && in_array($selectedDay, $validDays)) {
    $whereSql = "WHERE j.hari = ?";
    $params = [$selectedDay];
    $types = 's';
}

$sqlList = "
    SELECT 
        j.id_jadwal,
        j.hari,
        j.jam_mulai,
        j.jam_selesai,
        j.status,
        d.nama_dokter,
        d.id_dokter,
        p.nama_poli,
        p.id_poli
    FROM jadwal_praktik j
    JOIN dokter d ON j.id_dokter = d.id_dokter
    JOIN poli p ON d.id_poli = p.id_poli
    $whereSql
    ORDER BY 
        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        j.jam_mulai ASC
";

$schedules = [];

if ($whereSql === '') {
    $res = $conn->query($sqlList);
} else {
    $stmt = $conn->prepare($sqlList);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
}

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $schedules[] = $row;
    }
    $res->free();
}
if (isset($stmt) && $stmt) $stmt->close();

// Group schedules by day
$schedulesByDay = [];
foreach ($schedules as $schedule) {
    $day = $schedule['hari'];
    if (!isset($schedulesByDay[$day])) {
        $schedulesByDay[$day] = [];
    }
    $schedulesByDay[$day][] = $schedule;
}

// Nama admin (untuk header)
$adminName = 'Admin';
if (isset($_SESSION['user_id'])) {
    $idUser = (int)$_SESSION['user_id'];
    $qAdmin = $conn->prepare("SELECT username FROM users WHERE id_user = ?");
    $qAdmin->bind_param('i', $idUser);
    $qAdmin->execute();
    $resA = $qAdmin->get_result()->fetch_assoc();
    if ($resA) $adminName = $resA['username'];
    $qAdmin->close();
}

// Helper function to format time
function formatTime($time) {
    return date('H:i', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Praktik Dokter - Admin Puskesmas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .gradient-green {
            background-image: linear-gradient(to right, #45BC7D, #3aa668);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<div class="min-h-screen flex">
    <?php
        $active = 'jadwal';
        include __DIR__ . '/sidebar.php';
    ?>

    <div class="flex-1 flex flex-col">
        <!-- Topbar -->
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Jadwal Praktik Dokter</h1>
                <p class="text-xs text-gray-500">Lihat ringkasan jadwal praktik dokter per hari</p>
            </div>
            <div class="text-xs text-gray-500">
                Login sebagai: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <main class="flex-1 px-4 md:px-8 py-6 max-w-7xl mx-auto w-full">

            <!-- Flash -->
            <?php if ($msg = get_flash('success')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-100">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            <?php if ($msg = get_flash('error')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span class="text-sm text-gray-700">Filter Hari:</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="jadwal_praktik.php?hari=all"
                           class="px-4 py-2 rounded-xl text-sm transition-colors <?php echo $selectedDay === 'all' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Semua Hari
                        </a>
                        <?php foreach ($validDays as $day): ?>
                            <a href="jadwal_praktik.php?hari=<?php echo urlencode($day); ?>"
                               class="px-4 py-2 rounded-xl text-sm transition-colors <?php echo $selectedDay === $day ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                <?php echo htmlspecialchars($day); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500">
                        <span class="font-medium">Catatan:</span> Untuk menambah atau mengubah jadwal, silakan kelola pada halaman
                        <a href="dokter.php" class="text-green-600 hover:underline">Data Dokter</a>
                    </p>
                </div>
            </div>

            <?php if ($selectedDay === 'all'): ?>
                <!-- Summary Cards by Day (All Days View) -->
                <div class="space-y-4">
                    <?php foreach ($validDays as $day): ?>
                        <?php if (isset($schedulesByDay[$day]) && count($schedulesByDay[$day]) > 0): ?>
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                                <div class="p-6 rounded-t-2xl border-b border-gray-100 bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <h3 class="text-gray-800 font-semibold"><?php echo htmlspecialchars($day); ?></h3>
                                        <span class="text-xs bg-green-500 text-white px-2 py-1 rounded-lg">
                                            <?php echo count($schedulesByDay[$day]); ?> Jadwal
                                        </span>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <?php foreach ($schedulesByDay[$day] as $schedule): ?>
                                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                                                <div class="flex items-start justify-between mb-2">
                                                    <p class="text-sm text-gray-800 font-medium">
                                                        <?php echo htmlspecialchars($schedule['nama_dokter']); ?>
                                                    </p>
                                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-lg">
                                                        <?php echo htmlspecialchars($schedule['nama_poli']); ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>
                                                        <?php echo formatTime($schedule['jam_mulai']); ?> - <?php echo formatTime($schedule['jam_selesai']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php if (empty($schedulesByDay)): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-gray-500 mb-2">Belum ada jadwal praktik terdaftar</p>
                            <p class="text-sm text-gray-400">Silakan tambahkan jadwal melalui halaman <a href="dokter.php" class="text-green-600 hover:underline">Data Dokter</a></p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- Single Day View -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="p-6 rounded-t-2xl border-b border-gray-100 gradient-green">
                        <div class="flex items-center gap-3 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <h3 class="text-white text-lg font-semibold"><?php echo htmlspecialchars($selectedDay); ?></h3>
                                <p class="text-sm text-white opacity-90">
                                    <?php echo count($schedules); ?> jadwal praktik tersedia
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if (count($schedules) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs text-gray-600">Dokter</th>
                                        <th class="px-6 py-4 text-left text-xs text-gray-600">Poli</th>
                                        <th class="px-6 py-4 text-left text-xs text-gray-600">Jam Praktik</th>
                                        <th class="px-6 py-4 text-left text-xs text-gray-600">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm text-gray-800">
                                                <?php echo htmlspecialchars($schedule['nama_dokter']); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs">
                                                    <?php echo htmlspecialchars($schedule['nama_poli']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2 text-sm text-gray-700">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>
                                                        <?php echo formatTime($schedule['jam_mulai']); ?> - <?php echo formatTime($schedule['jam_selesai']); ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 <?php echo $schedule['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?> rounded-lg text-xs">
                                                    <?php echo $schedule['status'] === 'active' ? 'Aktif' : 'Tidak Aktif'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-gray-500">Tidak ada jadwal praktik untuk hari <?php echo htmlspecialchars($selectedDay); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Total Summary -->
            <div class="mt-6 bg-blue-50 rounded-2xl p-4 border border-blue-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm text-gray-700">
                            Total jadwal <?php echo $selectedDay === 'all' ? 'semua hari' : 'hari ' . htmlspecialchars($selectedDay); ?>:
                        </span>
                    </div>
                    <span class="text-lg text-gray-800 font-medium"><?php echo count($schedules); ?> jadwal</span>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>