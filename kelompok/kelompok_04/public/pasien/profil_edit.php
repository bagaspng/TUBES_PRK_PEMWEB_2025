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

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama_lengkap = trim($_POST['nama_lengkap']);
    $nik          = trim($_POST['nik']);
    $bpjs         = trim($_POST['bpjs']);
    $no_hp        = trim($_POST['no_hp']);
    $alamat       = trim($_POST['alamat']);

    if ($nama_lengkap === "" || $nik === "") {
        $error = "Nama lengkap dan NIK wajib diisi.";
    } else {
        $sqlUpdate = "UPDATE pasien SET 
                        nama_lengkap = ?, 
                        nik = ?, 
                        bpjs = ?, 
                        no_hp = ?, 
                        alamat = ?, 
                        updated_at = NOW()
                      WHERE id_pasien = ?";
        $stmtUpd = $conn->prepare($sqlUpdate);
        $stmtUpd->bind_param("sssssi",
            $nama_lengkap,
            $nik,
            $bpjs,
            $no_hp,
            $alamat,
            $pasien['id_pasien']
        );

        if ($stmtUpd->execute()) {
            header("Location: profil.php?success=1");
            exit;
        } else {
            $error = "Gagal memperbarui profil.";
        }
        $stmtUpd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-2xl mx-auto px-6 py-4 flex items-center gap-4">
            <a href="profil.php"
               class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 512 512">
                    <path d="M328 112 184 256l144 144" fill="none" stroke="currentColor"
                          stroke-width="48" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-800">Edit Profil</h1>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-6 py-8 space-y-6">

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded-lg text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">

            <div>
                <label class="text-sm text-gray-600">Nama Lengkap</label>
                <input type="text" name="nama_lengkap"
                       value="<?= htmlspecialchars($pasien['nama_lengkap']) ?>"
                       class="mt-1 w-full px-4 py-3 bg-gray-50 border rounded-xl focus:ring-green-500 focus:border-green-500" required>
            </div>

            <div>
                <label class="text-sm text-gray-600">NIK</label>
                <input type="text" name="nik"
                       value="<?= htmlspecialchars($pasien['nik']) ?>"
                       class="mt-1 w-full px-4 py-3 bg-gray-50 border rounded-xl focus:ring-green-500 focus:border-green-500" required>
            </div>

            <div>
                <label class="text-sm text-gray-600">BPJS</label>
                <input type="text" name="bpjs"
                       value="<?= htmlspecialchars($pasien['bpjs'] ?? '') ?>"
                       class="mt-1 w-full px-4 py-3 bg-gray-50 border rounded-xl focus:ring-green-500 focus:border-green-500">
            </div>

            <div>
                <label class="text-sm text-gray-600">Nomor Telepon</label>
                <input type="text" name="no_hp"
                       value="<?= htmlspecialchars($pasien['no_hp'] ?? '') ?>"
                       class="mt-1 w-full px-4 py-3 bg-gray-50 border rounded-xl focus:ring-green-500 focus:border-green-500">
            </div>

            <div>
                <label class="text-sm text-gray-600">Alamat</label>
                <textarea name="alamat"
                          class="mt-1 w-full px-4 py-3 bg-gray-50 border rounded-xl focus:ring-green-500 focus:border-green-500"
                          rows="3"><?= htmlspecialchars($pasien['alamat'] ?? '') ?></textarea>
            </div>

            <button type="submit"
                    class="w-full bg-gradient-to-r text-white py-3 rounded-xl hover:bg-green-600 transition shadow-md"
                    style="background-image: linear-gradient(to right, #45BC7D, #3aa668);">
                    
                Simpan Perubahan
            </button>
        </form>

    </div>

</body>
</html>
