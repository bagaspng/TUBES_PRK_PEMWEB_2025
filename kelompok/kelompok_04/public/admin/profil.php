<?php
// public/admin/profile.php

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/icon_helper.php';

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

$idUser = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nama === '' || $email === '') {
        set_flash('error', 'Nama dan email wajib diisi.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Format email tidak valid.');
    } else {
        $sql = "UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssi', $nama, $email, $idUser);
        if ($stmt->execute()) {
            set_flash('success', 'Profil berhasil diperbarui.');
        } else {
            set_flash('error', 'Terjadi kesalahan saat memperbarui profil.');
        }
        $stmt->close();
    }

    header('Location: profil.php');
    exit;
}

$sqlUser = "SELECT username, email, role FROM users WHERE id_user = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param('i', $idUser);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$user    = $resUser->fetch_assoc();
$stmtUser->close();

$namaAdmin   = $user['username'] ?? 'Admin';
$emailAdmin  = $user['email'] ?? 'admin@puskesmas.id';
$roleAdmin   = ucfirst($user['role'] ?? 'admin');

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Admin - Puskesmas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100 font-sans">

<div class="min-h-screen flex">
    <?php
        $active = 'profil';
        include __DIR__ . '/sidebar.php';
    ?>

    <main class="flex-1 items-center pb-6 w-full" style="max-width: 80rem;">
        <!-- Topbar -->
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Profil Admin</h1>
                <p class="text-xs text-gray-500">Informasi profil administrator</p>
            </div>
            <div class="text-xs text-gray-500">
                Login sebagai: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($namaAdmin); ?></span>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-6 py-8 space-y-8">
            <!-- Flash message -->
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

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-gray-800 mb-2 text-xl">Profil Admin</h1>
                <p class="text-sm text-gray-500">Informasi profil administrator</p>
            </div>

            <div class="rounded-2xl p-6 text-white mb-6 shadow-md bg-gradient-to-r"
  style="background-image: linear-gradient(to right, #45BC7D, #3aa668);">
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="text-3xl font-semibold">
                            <?php echo strtoupper(substr($namaAdmin, 0, 1)); ?>
                        </span>
                    </div>
                    <div>
                        <h2 class="text-white text-lg mb-1">
                            <?php echo htmlspecialchars($namaAdmin); ?>
                        </h2>
                        <p class="text-sm text-white/90">Administrator</p>
                        <p class="text-sm text-white/80 mt-1">
                            Username: <?php echo htmlspecialchars($namaAdmin); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <h3 class="text-gray-800 mb-4 text-base font-semibold">Informasi Kontak</h3>

                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center">
                            <span class="text-gray-600 text-sm font-semibold">@</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 mb-1">Email</p>
                            <p class="text-sm text-gray-800">
                                <?php echo htmlspecialchars($emailAdmin); ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center">
                            <span class="text-gray-600 text-sm font-semibold">R</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 mb-1">Role</p>
                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($roleAdmin); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3 mb-8">
                <button
                    type="button"
                    onclick="openEditModal()"
                    class="w-full text-white py-4 rounded-xl bg-gradient-to-r transition-colors flex items-center justify-center gap-2 text-sm"
                    style="background-image: linear-gradient(to right, #45BC7D, #3aa668);"
                >
                    
                    <span>Edit Profil</span>
                </button>

                <button
                    type="button"
                    onclick="openLogoutModal()"
                    class="w-full bg-red-50 border-2 border-red-100 text-red-600 py-4 rounded-xl hover:bg-red-100 transition-colors flex items-center justify-center gap-2 text-sm"
                >
            
                    <span>Keluar</span>
                </button>
            </div>
        </main>
    </div>
</div>

<div id="editModal"
     class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-6 hidden bg-black bg-opacity-40">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h2 class="text-gray-800 text-base font-semibold">Edit Profil</h2>
            <button
                type="button"
                onclick="closeEditModal()"
                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"
            >
                <span class="text-gray-600 text-xl">&times;</span>
            </button>
        </div>

        <form method="post" class="p-6 space-y-5">
            <input type="hidden" name="action" value="update_profile">

            <div>
                <label class="block text-sm text-gray-700 mb-2">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="nama"
                    value="<?php echo htmlspecialchars($namaAdmin); ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D]"
                    required
                >
                <p class="mt-1 text-xs text-gray-400">
                    Nama ini juga digunakan sebagai username login.
                </p>
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-2">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    name="email"
                    value="<?php echo htmlspecialchars($emailAdmin); ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D]"
                    required
                >
            </div>

            <div class="flex gap-3 pt-4">
                <button
                    type="button"
                    onclick="closeEditModal()"
                    class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl hover:bg-gray-200 transition-colors text-sm"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="flex-1 bg-gradient-to-r text-white py-3 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm"
                    style="background-image: linear-gradient(to right, #45BC7D, #3aa668);"
                >
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
    $logoutAction = '../logout.php'; 
    include __DIR__ . '/../partials/logout_modal.php';
?>
<script>
function openEditModal() { document.getElementById('editModal').classList.remove('hidden'); }
function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }
</script>
</body>
</html>
