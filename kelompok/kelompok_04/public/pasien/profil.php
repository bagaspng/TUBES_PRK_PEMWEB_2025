<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pasien') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$sql = "SELECT p.*, u.email 
        FROM pasien p
        JOIN users u ON p.id_user = u.id_user
        WHERE p.id_user = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pasien = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pasien) {
    die("Data pasien tidak ditemukan.");
}

$pasien['bpjs'] = $pasien['bpjs'] ?? "0001234567890";
$pasien['no_hp'] = $pasien['no_hp'] ?? "-";
$pasien['alamat'] = $pasien['alamat'] ?? "-";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Profil Saya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-2xl mx-auto px-6 py-4 flex items-center gap-4">
            <a href="dashboard.php"
               class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 512 512">
                    <path d="M328 112 184 256l144 144" fill="none"
                          stroke="currentColor" stroke-width="48" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-800">Profil Saya</h1>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-6 py-8 space-y-8">

        <div class="bg-green-500 text-white rounded-2xl p-6 flex items-center gap-4 shadow-md">

            <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="currentColor" viewBox="0 0 512 512">
                    <path d="M256 256a112 112 0 1 0-112-112 112.13 112.13 0 0 0 112 112Zm0 32
                             c-70.7 0-208 35.82-208 107.5C48 417.89 56.35 432 77.74 432h356.52
                             C455.65 432 464 417.89 464 395.5 464 323.82 326.7 288 256 288Z"/>
                </svg>
            </div>

            <div>
                <p class="text-lg font-semibold">
                    <?= htmlspecialchars($pasien['nama_lengkap']) ?>
                </p>
                <p class="text-sm opacity-90">NIK: <?= htmlspecialchars($pasien['nik']) ?></p>
                <p class="text-sm opacity-90">BPJS: <?= htmlspecialchars($pasien['bpjs']) ?></p>
            </div>

        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 space-y-6">

            <h2 class="text-gray-700 font-semibold text-sm">Informasi Kontak</h2>

            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" viewBox="0 0 512 512">
                        <path d="M391.17 351.72c-15.29 0-33.12-2.78-53.16-8.26
                                 c-8.35-2.27-17.8-.55-23.32 5l-33.12 25.11
                                 c-50.1-26.83-90.74-67.46-117.57-117.57l25.11-33.12
                                 c5.82-5.82 7.64-14.6 5-23.32c-5.48-20-8.26-37.87-8.26-53.16
                                 c0-12.6-10.2-22.8-22.8-22.8H96.28c-12.6 0-22.8 10.2-22.8 22.8
                                 C73.48 296.06 215.94 438.52 391.17 438.52
                                 c12.6 0 22.8-10.2 22.8-22.8v-66.12c0-12.6-10.2-22.8-22.8-22.8Z"/>
                    </svg>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Nomor Telepon</p>
                    <p class="text-sm text-gray-800"><?= htmlspecialchars($pasien['no_hp']) ?></p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" viewBox="0 0 512 512">
                        <path d="M256 32C167.64 32 96 103.64 96 192c0 112 160 288 160 288
                                 s160-176 160-288C416 103.64 344.36 32 256 32Zm0 256
                                 a64 64 0 1 1 64-64a64 64 0 0 1-64 64Z"/>
                    </svg>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Alamat</p>
                    <p class="text-sm text-gray-800"><?= htmlspecialchars($pasien['alamat']) ?></p>
                </div>
            </div>

        </div>

        <a href="profil_edit.php"
           class="block text-center bg-green-500 text-white py-3 rounded-xl shadow-md hover:bg-green-600 transition">
            Edit Profil
        </a>

        <button
            type="button"
            onclick="openLogoutModal()"
            class="w-full bg-red-50 border-2 border-red-100 text-red-600 py-4 rounded-xl hover:bg-red-100 transition-colors flex items-center justify-center gap-2 text-sm">
            <span class="w-5 h-5 rounded-full border border-red-400 flex items-center justify-center text-xs">&#8592;</span>
            <span>Keluar</span>
        </button>
        <p class="text-center text-xs text-gray-400 mt-6">
            Sistem Puskesmas v1.0.0
        </p>
       
<?php
    $logoutAction = '../logout.php'; 
    include __DIR__ . '/../partials/logout_modal.php';
?>
<script>
function openEditModal() { document.getElementById('editModal').classList.remove('hidden'); }
function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }
</script>



    </div>

</body>
</html>
