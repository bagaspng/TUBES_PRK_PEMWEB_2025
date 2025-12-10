<?php
// public/admin/jadwal_praktik.php

session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

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

$action = trim($_POST['action'] ?? '');

if ($action === 'create') {
    $_id_dokter = (int)($_POST['id_dokter'] ?? 0);
    $_hari = trim($_POST['hari'] ?? '');
    $_jam_mulai = trim($_POST['jam_mulai'] ?? '');
    $_jam_selesai = trim($_POST['jam_selesai'] ?? '');
    $_kuota = (int)($_POST['kuota_pasien'] ?? 0);
    $_status = trim($_POST['status'] ?? 'active');

    $error = [];

    if ($_id_dokter <= 0) $error[] = 'Dokter harus dipilih';
    if (!in_array($_hari, ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'])) $error[] = 'Hari tidak valid';
    if (empty($_jam_mulai)) $error[] = 'Jam mulai harus diisi';
    if (empty($_jam_selesai)) $error[] = 'Jam selesai harus diisi';
    if ($_jam_mulai >= $_jam_selesai) $error[] = 'Jam mulai harus lebih kecil dari jam selesai';
    if ($_kuota <= 0) $error[] = 'Kuota pasien harus lebih dari 0';

    if (empty($error)) {
        $poliStmt = $conn->prepare("SELECT id_poli FROM dokter WHERE id_dokter = ?");
        $poliStmt->bind_param('i', $_id_dokter);
        $poliStmt->execute();
        $resPoli = $poliStmt->get_result()->fetch_assoc();
        $poliStmt->close();
        if (!$resPoli) $error[] = 'Dokter tidak ditemukan';
        else $_id_poli = (int)$resPoli['id_poli'];
    }

    if (empty($error)) {
        try {
            $cekStmt = $conn->prepare("
                SELECT id_jadwal FROM jadwal_praktik 
                WHERE id_dokter = ? AND hari = ? AND jam_mulai = ? AND jam_selesai = ?
            ");
            $cekStmt->bind_param('isss', $_id_dokter, $_hari, $_jam_mulai, $_jam_selesai);
            $cekStmt->execute();
            $cekRes = $cekStmt->get_result();
            
            if ($cekRes->num_rows > 0) {
                set_flash('error', 'Jadwal dengan dokter, hari, dan jam yang sama sudah ada');
            } else {
                $insertStmt = $conn->prepare("
                    INSERT INTO jadwal_praktik 
                    (id_dokter, id_poli, hari, jam_mulai, jam_selesai, kuota_pasien, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $insertStmt->bind_param('iisssis', $_id_dokter, $_id_poli, $_hari, $_jam_mulai, $_jam_selesai, $_kuota, $_status);
                if ($insertStmt->execute()) set_flash('success', 'Jadwal praktik berhasil ditambahkan');
                else set_flash('error', 'Gagal menambahkan jadwal praktik: ' . $insertStmt->error);
                $insertStmt->close();
            }
            $cekStmt->close();
        } catch (Exception $e) {
            set_flash('error', 'Error: ' . $e->getMessage());
        }
    } else {
        set_flash('error', implode(', ', $error));
    }

    header('Location: jadwal_praktik.php?hari=' . urlencode($_hari === 'all' ? 'all' : $_hari));
    exit;
}

if ($action === 'update') {
    $_id_jadwal  = (int)($_POST['id_jadwal'] ?? 0);
    $_id_dokter  = (int)($_POST['id_dokter'] ?? 0);
    $_hari       = trim($_POST['hari'] ?? '');
    $_jam_mulai  = trim($_POST['jam_mulai'] ?? '');
    $_jam_selesai= trim($_POST['jam_selesai'] ?? '');
    $_kuota      = (int)($_POST['kuota_pasien'] ?? 0);
    $_status     = trim($_POST['status'] ?? 'active');

    $error = [];

    if ($_id_jadwal <= 0) $error[] = 'Jadwal tidak valid';
    if ($_id_dokter <= 0) $error[] = 'Dokter harus dipilih';
    if (!in_array($_hari, ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'])) $error[] = 'Hari tidak valid';
    if (empty($_jam_mulai)) $error[] = 'Jam mulai harus diisi';
    if (empty($_jam_selesai)) $error[] = 'Jam selesai harus diisi';
    if ($_jam_mulai >= $_jam_selesai) $error[] = 'Jam mulai harus lebih kecil dari jam selesai';
    if ($_kuota <= 0) $error[] = 'Kuota pasien harus lebih dari 0';

    if (empty($error)) {
        $poliStmt = $conn->prepare("SELECT id_poli FROM dokter WHERE id_dokter = ?");
        $poliStmt->bind_param('i', $_id_dokter);
        $poliStmt->execute();
        $resPoli = $poliStmt->get_result()->fetch_assoc();
        $poliStmt->close();
        if (!$resPoli) $error[] = 'Dokter tidak ditemukan';
        else $_id_poli = (int)$resPoli['id_poli'];
    }

    if (empty($error)) {
        try {
            $cekStmt = $conn->prepare("
                SELECT id_jadwal FROM jadwal_praktik 
                WHERE id_dokter = ? AND hari = ? AND jam_mulai = ? AND jam_selesai = ? AND id_jadwal != ?
            ");
            $cekStmt->bind_param('isssl', $_id_dokter, $_hari, $_jam_mulai, $_jam_selesai, $_id_jadwal);
            $cekStmt->execute();
            $cekRes = $cekStmt->get_result();
            
            if ($cekRes->num_rows > 0) {
                set_flash('error', 'Jadwal dengan dokter, hari, dan jam yang sama sudah ada');
            } else {
                $updateStmt = $conn->prepare("
                    UPDATE jadwal_praktik 
                    SET id_dokter = ?, id_poli = ?, hari = ?, jam_mulai = ?, jam_selesai = ?, kuota_pasien = ?, status = ?, updated_at = NOW() 
                    WHERE id_jadwal = ?
                ");
                $updateStmt->bind_param('iissssis', $_id_dokter, $_id_poli, $_hari, $_jam_mulai, $_jam_selesai, $_kuota, $_status, $_id_jadwal);
                if ($updateStmt->execute()) set_flash('success', 'Jadwal praktik berhasil diubah');
                else set_flash('error', 'Gagal mengubah jadwal praktik: ' . $updateStmt->error);
                $updateStmt->close();
            }
            $cekStmt->close();
        } catch (Exception $e) {
            set_flash('error', 'Error: ' . $e->getMessage());
        }
    } else {
        set_flash('error', implode(', ', $error));
    }

    header('Location: jadwal_praktik.php?hari=' . urlencode($_hari === 'all' ? 'all' : $_hari));
    exit;
}

if ($action === 'delete') {
    $_id_jadwal = (int)($_POST['id_jadwal'] ?? 0);
    $_hari = trim($_POST['hari'] ?? 'all');

    if ($_id_jadwal > 0) {
        try {
            $cekAntrian = $conn->prepare("SELECT COUNT(*) as cnt FROM antrian WHERE id_jadwal = ?");
            $cekAntrian->bind_param('i', $_id_jadwal);
            $cekAntrian->execute();
            $resAntrian = $cekAntrian->get_result()->fetch_assoc();

            if ($resAntrian['cnt'] > 0) {
                set_flash('error', 'Tidak dapat menghapus jadwal yang masih memiliki antrian');
            } else {
                $deleteStmt = $conn->prepare("DELETE FROM jadwal_praktik WHERE id_jadwal = ?");
                $deleteStmt->bind_param('i', $_id_jadwal);
                
                if ($deleteStmt->execute()) {
                    set_flash('success', 'Jadwal praktik berhasil dihapus');
                } else {
                    set_flash('error', 'Gagal menghapus jadwal praktik: ' . $deleteStmt->error);
                }
                $deleteStmt->close();
            }
            $cekAntrian->close();
        } catch (Exception $e) {
            set_flash('error', 'Error: ' . $e->getMessage());
        }
    }

    header('Location: jadwal_praktik.php?hari=' . urlencode($_hari));
    exit;
}


$selectedDay = trim($_GET['hari'] ?? 'all');
$validDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

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
        j.kuota_pasien,
        j.status,
        d.nama_dokter,
        d.id_dokter,
        d.spesialis,
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


$schedulesByDay = [];
foreach ($schedules as $schedule) {
    $day = $schedule['hari'];
    if (!isset($schedulesByDay[$day])) {
        $schedulesByDay[$day] = [];
    }
    $schedulesByDay[$day][] = $schedule;
}

$dokterList = [];
$dokterRes = $conn->query("SELECT d.id_dokter, d.nama_dokter, p.nama_poli FROM dokter d JOIN poli p ON d.id_poli = p.id_poli ORDER BY d.nama_dokter");
if ($dokterRes) {
    while ($row = $dokterRes->fetch_assoc()) {
        $dokterList[] = $row;
    }
    $dokterRes->free();
}

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
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 40;
        }
        .modal-overlay.active {
            display: block;
        }
        .modal-content {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            z-index: 50;
            max-height: 90vh;
            overflow-y: auto;
            width: 90%;
            max-width: 500px;
        }
        .modal-content.active {
            display: block;
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
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Jadwal Praktik Dokter</h1>
                <p class="text-xs text-gray-500">Kelola jadwal praktik dokter per hari</p>
            </div>
            <div class="text-xs text-gray-500">
                Login sebagai: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <main class="flex-1 px-4 md:px-8 py-6 max-w-7xl mx-auto w-full">

            <?php if ($msg = get_flash('success')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-100">
                    ✓ <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            <?php if ($msg = get_flash('error')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
                    ✗ <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span class="text-sm text-gray-700 font-medium">Filter Hari:</span>
                    <div class="flex flex-wrap gap-2">
                        <a href="jadwal_praktik.php?hari=all"
                           class="px-3 py-1 rounded-lg text-xs transition-colors <?php echo $selectedDay === 'all' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            Semua
                        </a>
                        <?php foreach ($validDays as $day): ?>
                            <a href="jadwal_praktik.php?hari=<?php echo urlencode($day); ?>"
                               class="px-3 py-1 rounded-lg text-xs transition-colors <?php echo $selectedDay === $day ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                <?php echo htmlspecialchars(substr($day, 0, 3)); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button onclick="openModal('create')" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm font-medium">
                    + Tambah Jadwal
                </button>
            </div>

            <div class="modal-overlay" id="modalOverlay"></div>
            <div class="modal-content" id="modalContent">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800" id="modalTitle">Tambah Jadwal Praktik</h2>
                        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" id="scheduleForm">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id_jadwal" id="field_id_jadwal">

                        <div class="space-y-4">
                            <!-- Dokter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Dokter <span class="text-red-500">*</span></label>
                                <select name="id_dokter" id="field_id_dokter" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">-- Pilih Dokter --</option>
                                    <?php foreach ($dokterList as $dok): ?>
                                        <option value="<?php echo $dok['id_dokter']; ?>">
                                            <?php echo htmlspecialchars($dok['nama_dokter']); ?> (<?php echo htmlspecialchars($dok['nama_poli']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Hari -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hari <span class="text-red-500">*</span></label>
                                <select name="hari" id="field_hari" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">-- Pilih Hari --</option>
                                    <?php foreach ($validDays as $day): ?>
                                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Jam Mulai -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Mulai <span class="text-red-500">*</span></label>
                                <input type="time" name="jam_mulai" id="field_jam_mulai" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>

                            <!-- Jam Selesai -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Selesai <span class="text-red-500">*</span></label>
                                <input type="time" name="jam_selesai" id="field_jam_selesai" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>

                            <!-- Kuota Pasien -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kuota Pasien <span class="text-red-500">*</span></label>
                                <input type="number" name="kuota_pasien" id="field_kuota" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" id="field_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="active">Aktif</option>
                                    <option value="unactive">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 flex gap-3">
                            <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button type="submit" class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Display -->
            <?php if ($selectedDay === 'all'): ?>
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
                                            <?php echo count($schedulesByDay[$day]); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <?php foreach ($schedulesByDay[$day] as $schedule): ?>
                                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 hover:border-green-200 transition">
                                                <div class="flex items-start justify-between mb-3">
                                                    <div>
                                                        <p class="text-sm text-gray-800 font-medium">
                                                            <?php echo htmlspecialchars($schedule['nama_dokter']); ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500">
                                                            <?php echo htmlspecialchars($schedule['spesialis']); ?>
                                                        </p>
                                                    </div>
                                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-lg">
                                                        <?php echo htmlspecialchars($schedule['nama_poli']); ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-600 mb-3">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>
                                                        <?php echo formatTime($schedule['jam_mulai']); ?> - <?php echo formatTime($schedule['jam_selesai']); ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 text-xs text-gray-600 mb-3">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                    <span>Kuota: <?php echo $schedule['kuota_pasien']; ?></span>
                                                </div>
                                                <div class="pt-3 border-t border-gray-200 flex gap-2">
                                                    <button onclick="editSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" class="flex-1 px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition">
                                                        Edit
                                                    </button>
                                                    <form method="POST" style="flex: 1;" onsubmit="return confirm('Yakin hapus jadwal ini?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id_jadwal" value="<?php echo $schedule['id_jadwal']; ?>">
                                                        <input type="hidden" name="hari" value="all">
                                                        <button type="submit" class="w-full px-2 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition">
                                                            Hapus
                                                        </button>
                                                    </form>
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
                            <button onclick="openModal('create')" class="text-green-600 hover:underline text-sm font-medium">
                                + Tambah jadwal sekarang
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="p-6 rounded-t-2xl border-b border-gray-100 gradient-green">
                        <div class="flex items-center gap-3 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <h3 class="text-white text-lg font-semibold"><?php echo htmlspecialchars($selectedDay); ?></h3>
                                <p class="text-sm text-white opacity-90">
                                    <?php echo count($schedules); ?> jadwal praktik
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if (count($schedules) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-600">Dokter</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-600">Spesialisasi</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-600">Poli</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-600">Jam</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-600">Kuota</th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-600">Status</th>
                                        <th class="px-6 py-4 text-center text-xs font-medium text-gray-600">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm text-gray-800">
                                                <?php echo htmlspecialchars($schedule['nama_dokter']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?php echo htmlspecialchars($schedule['spesialis']); ?>
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
                                            <td class="px-6 py-4 text-sm text-gray-700">
                                                <?php echo $schedule['kuota_pasien']; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-3 py-1 <?php echo $schedule['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?> rounded-lg text-xs">
                                                    <?php echo $schedule['status'] === 'active' ? 'Aktif' : 'Tidak Aktif'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button onclick="editSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition mr-2">
                                                    Edit
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus jadwal ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_jadwal" value="<?php echo $schedule['id_jadwal']; ?>">
                                                    <input type="hidden" name="hari" value="<?php echo htmlspecialchars($selectedDay); ?>">
                                                    <button type="submit" class="px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition">
                                                        Hapus
                                                    </button>
                                                </form>
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
                            <p class="text-gray-500 mb-4">Tidak ada jadwal praktik untuk hari <?php echo htmlspecialchars($selectedDay); ?></p>
                            <button onclick="openModal('create')" class="text-green-600 hover:underline text-sm font-medium">
                                + Tambah jadwal sekarang
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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

<script>
const modalOverlay = document.getElementById('modalOverlay');
const modalContent = document.getElementById('modalContent');
const scheduleForm = document.getElementById('scheduleForm');

function openModal(action) {
    document.getElementById('formAction').value = action;
    document.getElementById('modalTitle').textContent = action === 'create' ? 'Tambah Jadwal Praktik' : 'Edit Jadwal Praktik';
    
    scheduleForm.reset();
    document.getElementById('field_id_jadwal').value = '';
    
    modalOverlay.classList.add('active');
    modalContent.classList.add('active');
}

function closeModal() {
    modalOverlay.classList.remove('active');
    modalContent.classList.remove('active');
}

function editSchedule(schedule) {
    document.getElementById('formAction').value = 'update';
    document.getElementById('modalTitle').textContent = 'Edit Jadwal Praktik';
    
    document.getElementById('field_id_jadwal').value = schedule.id_jadwal;
    document.getElementById('field_id_dokter').value = schedule.id_dokter;
    document.getElementById('field_hari').value = schedule.hari;
    document.getElementById('field_jam_mulai').value = schedule.jam_mulai;
    document.getElementById('field_jam_selesai').value = schedule.jam_selesai;
    document.getElementById('field_kuota').value = schedule.kuota_pasien;
    document.getElementById('field_status').value = schedule.status;
    
    modalOverlay.classList.add('active');
    modalContent.classList.add('active');
}

modalOverlay.addEventListener('click', function(e) {
    if (e.target === modalOverlay) {
        closeModal();
    }
});

scheduleForm.addEventListener('submit', function(e) {
    const dokter = document.getElementById('field_id_dokter').value;
    const hari = document.getElementById('field_hari').value;
    const jamMulai = document.getElementById('field_jam_mulai').value;
    const jamSelesai = document.getElementById('field_jam_selesai').value;
    const kuota = document.getElementById('field_kuota').value;
    
    if (!dokter || !hari || !jamMulai || !jamSelesai || !kuota) {
        e.preventDefault();
        alert('Semua field wajib diisi');
        return false;
    }
    
    if (jamMulai >= jamSelesai) {
        e.preventDefault();
        alert('Jam mulai harus lebih kecil dari jam selesai');
        return false;
    }
});
</script>

</body>
</html>