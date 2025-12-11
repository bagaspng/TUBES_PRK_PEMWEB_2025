<?php
session_start();
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/config/mail.php'; // fungsi kirim email

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $error = 'Username wajib diisi.';
    } else {
        // Cek user berdasarkan username
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Akun tidak ditemukan.';
        } else if (empty($user['email'])) {
            $error = 'Akun ini tidak memiliki email. Hubungi admin.';
        } else {
            // Generate token reset
            $token = bin2hex(random_bytes(32));
            $expired = date('Y-m-d H:i:s', time() + 3600); // 1 jam

            // Simpan token di database
            $sql = "UPDATE users SET reset_token = ?, reset_expired = ? WHERE id_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $token, $expired, $user['id_user']);
            $stmt->execute();
            $stmt->close();

            // URL link reset otomatis
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
            $resetLink = $baseUrl . "/reset_password.php?token=$token";

            // Kirim email
            $sendMail = sendResetEmail(
                $user['email'],
                $user['username'],
                $resetLink
            );

            if ($sendMail) {
                $success = "Link reset password telah dikirim ke email Anda.";
            } else {
                $error = "Gagal mengirim email. Periksa pengaturan SMTP.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center px-6">

<div class="w-full max-w-sm bg-white shadow-lg p-6 rounded-2xl">

    <h1 class="text-xl font-semibold text-gray-800 mb-4 text-center">Lupa Password</h1>
    <p class="text-gray-600 text-sm text-center mb-6">
        Masukkan username Anda, kami akan mengirim link reset password ke email terdaftar.
    </p>

    <?php if ($error): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="space-y-4">
        <div>
            <input
                type="text"
                name="username"
                placeholder="Masukkan Username"
                required
                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
            />
        </div>

        <button
            type="submit"
            class="w-full bg-green-600 text-white py-3 rounded-xl hover:bg-green-700 transition"
        >
            Kirim Link Reset
        </button>
    </form>

    <div class="text-center mt-6">
        <a href="login.php" class="text-blue-600 text-sm hover:underline">Kembali ke Login</a>
    </div>

</div>

</body>
</html>
