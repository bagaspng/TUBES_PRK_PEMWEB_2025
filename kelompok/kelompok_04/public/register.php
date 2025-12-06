<?php
// public/register.php

session_start();
require_once __DIR__ . '/../src/config/database.php';

$error   = '';
$success = '';

// Kalau sudah login, tidak perlu register lagi
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'pasien':
            header('Location: pasien/dashboard.php'); exit;
        case 'dokter':
            header('Location: dokter/dashboard.php'); exit;
        case 'admin':
            header('Location: admin/dashboard.php'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input
    $username      = trim($_POST['username'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $password2     = trim($_POST['password2'] ?? '');
    $nik           = trim($_POST['nik'] ?? '');
    $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');

    // Validasi sederhana
    if (
        $username === '' || $email === '' || $password === '' || $password2 === '' ||
        $nik === '' || $nama_lengkap === '' || $tanggal_lahir === '' || $jenis_kelamin === ''
    ) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($password !== $password2) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        // Cek username / email / NIK sudah dipakai
        // Cek users
        $sqlCheckUser = "SELECT id_user FROM users WHERE username = ? OR email = ? LIMIT 1";
        $stmtCheckUser = $conn->prepare($sqlCheckUser);
        $stmtCheckUser->bind_param('ss', $username, $email);
        $stmtCheckUser->execute();
        $stmtCheckUser->store_result();

        if ($stmtCheckUser->num_rows > 0) {
            $error = 'Username atau email sudah digunakan.';
        }
        $stmtCheckUser->close();

        // Cek NIK di tabel pasien
        if ($error === '') {
            $sqlCheckNik = "SELECT id_pasien FROM pasien WHERE nik = ? LIMIT 1";
            $stmtCheckNik = $conn->prepare($sqlCheckNik);
            $stmtCheckNik->bind_param('s', $nik);
            $stmtCheckNik->execute();
            $stmtCheckNik->store_result();

            if ($stmtCheckNik->num_rows > 0) {
                $error = 'NIK sudah terdaftar sebagai pasien.';
            }
            $stmtCheckNik->close();
        }

        // Jika tidak ada error, insert
        if ($error === '') {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role          = 'pasien';
            $now           = date('Y-m-d H:i:s');

            // Insert ke users
            $sqlUser = "INSERT INTO users (username, password_hash, email, role, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, 'active', ?, ?)";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param('ssssss', $username, $password_hash, $email, $role, $now, $now);

            if ($stmtUser->execute()) {
                $id_user = $stmtUser->insert_id;
                $stmtUser->close();

                // Generate nomor rekam medis
                $no_rm = 'RM-' . date('Ymd') . '-' . $id_user;

                // Insert ke pasien
                $sqlPasien = "INSERT INTO pasien (id_user, no_rm, nik, nama_lengkap, tanggal_lahir, jenis_kelamin, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtPasien = $conn->prepare($sqlPasien);
                $stmtPasien->bind_param(
                    'isssssss',
                    $id_user,
                    $no_rm,
                    $nik,
                    $nama_lengkap,
                    $tanggal_lahir,
                    $jenis_kelamin,
                    $now,
                    $now
                );

                if ($stmtPasien->execute()) {
                    $success = 'Registrasi berhasil. Silakan login dengan NIK dan password Anda.';
                    // Kosongkan form setelah sukses
                    $_POST = [];
                } else {
                    $error = 'Gagal menyimpan data pasien: ' . $stmtPasien->error;
                }
                $stmtPasien->close();

            } else {
                $error = 'Gagal membuat akun: ' . $stmtUser->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Pasien - Puskesmas Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-white flex items-center justify-center px-6">
    <div class="w-full max-w-md">

        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-gradient-to-br from-[#45BC7D] to-[#3aa668] rounded-full flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M256 256a112 112 0 1 0-112-112 112.13 112.13 0 0 0 112 112Zm0 32c-70.7 0-208 35.82-208 107.5 0 21.39 8.35 36.5 29.74 36.5h356.52C455.65 432 464 416.89 464 395.5 464 323.82 326.7 288 256 288Z"/>
                </svg>
            </div>
        </div>

        <!-- Title -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 mb-1">Registrasi Pasien</h1>
            <p class="text-gray-500 text-sm">Buat akun untuk mengakses layanan Puskesmas</p>
        </div>

        <!-- Alert -->
        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" action="register.php" class="space-y-4">
            <!-- Akun -->
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
                    <input
                        type="text"
                        name="username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                        required
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                        required
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
                    <input
                        type="password"
                        name="password"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                        required
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ulangi Password</label>
                    <input
                        type="password"
                        name="password2"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                        required
                    />
                </div>
            </div>

            <hr class="my-2 border-gray-200">

            <!-- Data Pasien -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">NIK</label>
                <input
                    type="text"
                    name="nik"
                    value="<?php echo htmlspecialchars($_POST['nik'] ?? ''); ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                    required
                />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Lengkap</label>
                <input
                    type="text"
                    name="nama_lengkap"
                    value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>"
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                    required
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Lahir</label>
                    <input
                        type="date"
                        name="tanggal_lahir"
                        value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                        required
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Kelamin</label>
                    <select
                        name="jenis_kelamin"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent text-sm"
                        required
                    >
                        <option value="">-- Pilih --</option>
                        <option value="L" <?php echo (($_POST['jenis_kelamin'] ?? '') === 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="P" <?php echo (($_POST['jenis_kelamin'] ?? '') === 'P') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
            </div>

            <button
                type="submit"
                class="w-full bg-[#45BC7D] text-white py-3 rounded-xl hover:bg-[#3aa668] transition-colors shadow-md text-sm font-semibold mt-2"
            >
                Daftar
            </button>
        </form>

        <!-- Link ke Login -->
        <div class="text-center mt-6">
            <p class="text-gray-500 text-xs">
                Sudah punya akun?
                <a href="login.php" class="text-[#496A9A] hover:underline">Masuk di sini</a>
            </p>
        </div>

        <!-- Footer -->
        <div class="text-center mt-10">
            <p class="text-gray-400 text-xs">Sistem Informasi Puskesmas</p>
        </div>
    </div>
</body>
</html>
