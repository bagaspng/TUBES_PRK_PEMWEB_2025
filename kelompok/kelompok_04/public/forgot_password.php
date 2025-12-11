<?php
session_start();
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/config/mail.php'; // fungsi kirim email

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cek user berdasarkan email
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Email tidak terdaftar dalam sistem.';
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

            // URL link reset dengan base URL dinamis
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $protocol . "://" . $host . dirname($_SERVER['PHP_SELF']);
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
        Masukkan email Anda, kami akan mengirim link reset password ke email tersebut.
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
            <label class="block text-sm text-gray-700 mb-2">Email</label>
            <input
                type="email"
                name="email"
                placeholder="contoh@email.com"
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
