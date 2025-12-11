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
       
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['role']    = $user['role'];

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

        <div class="flex justify-center mb-8">
            <div class="w-32 h-32 flex items-center justify-center">
                <img src="img/puskesmas.svg" alt="Logo Puskesmas" class="w-full h-full object-contain drop-shadow-lg">
            </div>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-gray-800 mb-2">Login </h1>
            <p class="text-gray-500 text-sm">Sistem Informasi Puskesmas</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

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


        <div class="text-center mt-16">
            <p class="text-gray-400 text-xs">Sistem Informasi Puskesmas</p>
        </div>
    </div>
</body>
</html>
