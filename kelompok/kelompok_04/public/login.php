<?php
session_start();
require_once __DIR__ . '/../src/config/database.php';

$error = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'pasien':
            header('Location: pasien/dashboard.php');
            exit;
        case 'dokter':
            header('Location: dokter/dashboard.php');
            exit;
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // NIK / Username
    $identifier = trim($_POST['username'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $error = 'NIK / Username dan password wajib diisi.';
    } else {
 
        $sql = "SELECT *
                FROM users
                WHERE username = ? AND status = 'active'
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['role']    = $user['role'];

            // Redirect sesuai role
            switch ($user['role']) {
                case 'pasien':
                    header('Location: pasien/dashboard.php');
                    break;
                case 'dokter':
                    header('Location: dokter/dashboard.php');
                    break;
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                default:
                    header('Location: login.php');
                    break;
            }
            exit;
        } else {
            $error = 'NIK / Username atau password salah.';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Puskesmas Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-white flex items-center justify-center px-6">
    <div class="w-full max-w-sm">

        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <div class="w-24 h-24 bg-gradient-to-br from-[#45BC7D] to-[#3aa668] rounded-full flex items-center justify-center shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 text-white" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M256 256a112 112 0 1 0-112-112 112.13 112.13 0 0 0 112 112Zm0 32c-70.7 0-208 35.82-208 107.5 0 21.39 8.35 36.5 29.74 36.5h356.52C455.65 432 464 416.89 464 395.5 464 323.82 326.7 288 256 288Z"/>
                </svg>
            </div>
        </div>

        <!-- Title -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-gray-800 mb-2">Login Pasien</h1>
            <p class="text-gray-500 text-sm">Sistem Informasi Puskesmas</p>
        </div>

        <!-- Error message -->
        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" action="login.php" class="space-y-6">
            <div>
                <input
                    type="text"
                    name="username"
                    placeholder="NIK/Username"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent transition-all text-sm"
                    required
                />
            </div>

            <div>
                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#45BC7D] focus:border-transparent transition-all text-sm"
                    required
                />
            </div>

            <button
                type="submit"
                class="w-full bg-[#45BC7D] text-white py-4 rounded-xl hover:bg-[#3aa668] transition-colors shadow-md text-sm font-semibold"
            >
                Masuk
            </button>
        </form>

        <!-- Register Link -->
        <div class="text-center mt-8">
            <a href="register.php" class="text-[#496A9A] text-sm hover:underline">
                Daftar Akun
            </a>
        </div>
        <div class="text-right">
            <a href="forgot_password.php" class="text-[#496A9A] text-xs hover:underline">
                Lupa Password?
            </a>
        </div>


        <!-- Footer -->
        <div class="text-center mt-16">
            <p class="text-gray-400 text-xs">Sistem Informasi Puskesmas</p>
        </div>
    </div>
</body>
</html>
