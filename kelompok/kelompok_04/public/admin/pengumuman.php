<?php
// public/admin/pengumuman.php

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
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $status = trim($_POST['status'] ?? 'draft');
    $id_admin = (int)$_SESSION['user_id'];
    $gambarPath = null;

    $error = [];

    if (empty($judul)) $error[] = 'Judul harus diisi';
    if (empty($isi)) $error[] = 'Isi pengumuman harus diisi';
    if (!in_array($status, ['draft', 'publish'])) $error[] = 'Status tidak valid';

    if (!empty($_FILES['gambar']['name'])) {
        $folder = __DIR__ . '/../../uploads/pengumuman/';
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; 

        if (!in_array($_FILES['gambar']['type'], $allowedTypes)) {
            $error[] = 'Format gambar harus JPG, PNG, atau GIF';
        } elseif ($_FILES['gambar']['size'] > $maxSize) {
            $error[] = 'Ukuran gambar maksimal 2MB';
        } else {
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gambar']['name']);
            $dest = $folder . $fileName;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                $gambarPath = 'uploads/pengumuman/' . $fileName;
            } else {
                $error[] = 'Gagal mengupload gambar';
            }
        }
    }

    if (empty($error)) {
        try {
            $insertStmt = $conn->prepare("
                INSERT INTO pengumuman 
                (judul, isi, gambar, tanggal, status, id_admin, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insertStmt->bind_param('sssssi', $judul, $isi, $gambarPath, $tanggal, $status, $id_admin);
            
            if ($insertStmt->execute()) {
                set_flash('success', 'Pengumuman berhasil ditambahkan');
            } else {
                set_flash('error', 'Gagal menambahkan pengumuman: ' . $insertStmt->error);
            }
            $insertStmt->close();
        } catch (Exception $e) {
            set_flash('error', 'Error: ' . $e->getMessage());
        }
    } else {
        set_flash('error', implode(', ', $error));
    }

    header('Location: pengumuman.php');
    exit;
}

if ($action === 'update') {
    $id_pengumuman = (int)($_POST['id_pengumuman'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $status = trim($_POST['status'] ?? 'draft');
    $gambarPath = trim($_POST['gambar_existing'] ?? '');

    $error = [];

    if ($id_pengumuman <= 0) $error[] = 'Pengumuman tidak valid';
    if (empty($judul)) $error[] = 'Judul harus diisi';
    if (empty($isi)) $error[] = 'Isi pengumuman harus diisi';
    if (!in_array($status, ['draft', 'publish'])) $error[] = 'Status tidak valid';

    if (!empty($_FILES['gambar']['name'])) {
        $folder = __DIR__ . '/../../uploads/pengumuman/';
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; 

        if (!in_array($_FILES['gambar']['type'], $allowedTypes)) {
            $error[] = 'Format gambar harus JPG, PNG, atau GIF';
        } elseif ($_FILES['gambar']['size'] > $maxSize) {
            $error[] = 'Ukuran gambar maksimal 2MB';
        } else {
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['gambar']['name']);
            $dest = $folder . $fileName;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                if (!empty($gambarPath) && file_exists(__DIR__ . '/../../' . $gambarPath)) {
                    unlink(__DIR__ . '/../../' . $gambarPath);
                }
                $gambarPath = 'uploads/pengumuman/' . $fileName;
            } else {
                $error[] = 'Gagal mengupload gambar';
            }
        }
    }

    if (empty($error)) {
        try {
            $updateStmt = $conn->prepare("
                UPDATE pengumuman 
                SET judul = ?, isi = ?, gambar = ?, tanggal = ?, status = ?, updated_at = NOW() 
                WHERE id_pengumuman = ?
            ");
            $updateStmt->bind_param('sssssi', $judul, $isi, $gambarPath, $tanggal, $status, $id_pengumuman);
            
            if ($updateStmt->execute()) {
                set_flash('success', 'Pengumuman berhasil diperbarui');
            } else {
                set_flash('error', 'Gagal memperbarui pengumuman: ' . $updateStmt->error);
            }
            $updateStmt->close();
        } catch (Exception $e) {
            set_flash('error', 'Error: ' . $e->getMessage());
        }
    } else {
        set_flash('error', implode(', ', $error));
    }

    header('Location: pengumuman.php');
    exit;
}

if ($action === 'delete') {
    $id_pengumuman = (int)($_POST['id_pengumuman'] ?? 0);

    if ($id_pengumuman > 0) {
        try {
            $getStmt = $conn->prepare("SELECT gambar FROM pengumuman WHERE id_pengumuman = ?");
            $getStmt->bind_param('i', $id_pengumuman);
            $getStmt->execute();
            $result = $getStmt->get_result()->fetch_assoc();
            $getStmt->close();

            $deleteStmt = $conn->prepare("DELETE FROM pengumuman WHERE id_pengumuman = ?");
            $deleteStmt->bind_param('i', $id_pengumuman);
            
            if ($deleteStmt->execute()) {
                if (!empty($result['gambar']) && file_exists(__DIR__ . '/../../' . $result['gambar'])) {
                    unlink(__DIR__ . '/../../' . $result['gambar']);
                }
                set_flash('success', 'Pengumuman berhasil dihapus');
            } else {
                set_flash('error', 'Gagal menghapus pengumuman: ' . $deleteStmt->error);
            }
            $deleteStmt->close();
        } catch (Exception $e) {
            set_flash('error', 'Error: ' . $e->getMessage());
        }
    }

    header('Location: pengumuman.php');
    exit;
}


$search = trim($_GET['q'] ?? '');
$params = [];
$types = '';

$whereSql = '';
if ($search !== '') {
    $whereSql = "WHERE p.judul LIKE ? OR p.isi LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like];
    $types = 'ss';
}

$sqlList = "
    SELECT 
        p.id_pengumuman,
        p.judul,
        p.isi,
        p.gambar,
        p.tanggal,
        p.status,
        p.created_at,
        u.username as admin_name
    FROM pengumuman p
    JOIN users u ON p.id_admin = u.id_user
    $whereSql
    ORDER BY p.tanggal DESC, p.created_at DESC
";

$announcements = [];

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
        $announcements[] = $row;
    }
    $res->free();
}
if (isset($stmt) && $stmt) $stmt->close();


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


function formatTanggal($tanggal) {
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    $parts = explode('-', $tanggal);
    return $parts[2] . ' ' . $bulan[(int)$parts[1]] . ' ' . $parts[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengumuman - Admin Puskesmas</title>
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
            max-width: 600px;
        }
        .modal-content.active {
            display: block;
        }
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<div class="min-h-screen flex">
    <?php
        $active = 'pengumuman';
        include __DIR__ . '/sidebar.php';
    ?>

    <div class="flex-1 flex flex-col">
        
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Pengumuman</h1>
                <p class="text-xs text-gray-500">Kelola pengumuman untuk pasien</p>
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

            <div class="mb-6 flex flex-col md:flex-row items-center gap-4">
                <form method="get" class="flex-1 w-full">
                    <input
                        type="text"
                        name="q"
                        placeholder="Cari pengumuman..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                    >
                </form>

                <div class="w-full md:w-auto">
                    <button
                        onclick="openModal('create')"
                        type="button"
                        class="px-6 py-3 bg-green-500 text-white rounded-xl text-sm hover:bg-green-600 flex items-center gap-2 w-full md:w-auto justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Pengumuman</span>
                    </button>
                </div>
            </div>

            <?php include __DIR__ . '/modals/modal_pengumuman.php'; ?>

            <div class="space-y-4">
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                            <div class="flex gap-4">
                                <?php if (!empty($announcement['gambar'])): ?>
                                    <div class="flex-shrink-0">
                                        <img src="../../<?php echo htmlspecialchars($announcement['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($announcement['judul']); ?>"
                                             class="w-32 h-32 object-cover rounded-lg">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h3 class="text-gray-800 font-semibold"><?php echo htmlspecialchars($announcement['judul']); ?></h3>
                                                <span class="px-3 py-1 text-xs rounded-lg font-medium <?php echo $announcement['status'] === 'publish' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                                    <?php echo ucfirst($announcement['status']); ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo htmlspecialchars($announcement['isi']); ?></p>
                                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                                <span>
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                    <?php echo formatTanggal($announcement['tanggal']); ?>
                                                </span>
                                                <span>
                                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    <?php echo htmlspecialchars($announcement['admin_name']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 ml-4">
                                            <button type="button"
                                                    onclick="editAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)"
                                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus pengumuman ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id_pengumuman" value="<?php echo $announcement['id_pengumuman']; ?>">
                                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <p class="text-gray-500 mb-2">Belum ada pengumuman</p>
                        <button onclick="openModal('create')" class="text-green-600 hover:underline text-sm font-medium">
                            + Tambah pengumuman pertama
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="mt-6 bg-blue-50 rounded-2xl p-4 border border-blue-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="text-sm text-gray-700">
                            Total pengumuman:
                        </span>
                    </div>
                    <span class="text-lg text-gray-800 font-medium"><?php echo count($announcements); ?></span>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>