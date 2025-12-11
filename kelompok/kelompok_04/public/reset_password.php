<?php
session_start();
require_once __DIR__ . '/../src/config/database.php';

$error = '';
$success = '';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token tidak ditemukan.");
}

$sql = "SELECT id_user, reset_expired FROM users WHERE reset_token = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Token tidak valid.");
}

if (strtotime($user['reset_expired']) < time()) {
    die("Token sudah kadaluwarsa. Silakan lakukan reset ulang.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($pass === '' || $pass2 === '') {
        $error = "Password tidak boleh kosong.";
    } elseif ($pass !== $pass2) {
        $error = "Password tidak cocok.";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $sql_update = "UPDATE users 
                       SET password_hash = ?, reset_token = NULL, reset_expired = NULL 
                       WHERE id_user = ?";
        $stmt2 = $conn->prepare($sql_update);
        $stmt2->bind_param("si", $hash, $user['id_user']);
        $stmt2->execute();

        $success = "Password berhasil diubah. <a href='login.php' class='text-blue-600 underline'>Login Sekarang</a>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center px-6">

<div class="w-full max-w-sm bg-white p-6 rounded-xl shadow-md">

    <h2 class="text-xl font-semibold mb-4 text-gray-800">Reset Password</h2>

    <?php if ($error): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-3 py-2 rounded">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="post" class="space-y-4">

        <input type="password" name="password" placeholder="Password Baru"
               class="w-full px-4 py-3 border rounded-lg bg-gray-50" required>

        <input type="password" name="password2" placeholder="Ulangi Password Baru"
               class="w-full px-4 py-3 border rounded-lg bg-gray-50" required>

        <button class="w-full bg-[#45BC7D] text-white py-3 rounded-lg hover:bg-[#3aa668]">
            Ubah Password
        </button>
    </form>
    <?php endif; ?>

</div>

</body>
</html>
