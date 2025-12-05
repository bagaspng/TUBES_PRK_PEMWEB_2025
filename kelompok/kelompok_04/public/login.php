<?php
// public/login.php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers/auth.php';

// Jika sudah login, langsung redirect
if (isset($_SESSION['user_id'])) {
    redirect_by_role($_SESSION['role']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username'] ?? '');
    $password          = trim($_POST['password'] ?? '');

    if ($username_or_email === '' || $password === '') {
        $error = 'Username/email dan password wajib diisi.';
    } else {
        // Cek tabel users
        $sql  = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['role']    = $user['role'];

            redirect_by_role($user['role']);
        } else {
            $error = 'Username/email atau password salah.';
        }

        $stmt->close();
    }
}

include __DIR__ . '/partials/header.php';
?>

<h2>Login</h2>

<?php if ($error): ?>
    <div style="background:#ffe5e5;color:#b00020;padding:8px 12px;margin-bottom:12px;border-radius:4px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form method="post" action="login.php" style="max-width:400px;">
    <div style="margin-bottom:12px;">
        <label for="username">Username atau Email</label><br>
        <input type="text" id="username" name="username"
               style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;"
               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
    </div>
    <div style="margin-bottom:12px;">
        <label for="password">Kata Sandi</label><br>
        <input type="password" id="password" name="password"
               style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
    </div>
    <button type="submit"
            style="width:100%;padding:10px;border:none;border-radius:4px;
                   background:#45BC7D;color:white;font-weight:600;">
        Masuk
    </button>
    <p style="margin-top:12px;font-size:14px;">
        Belum punya akun?
        <a href="register.php" style="color:#496A9A;">Daftar sebagai pasien</a>
    </p>
</form>

<?php include __DIR__ . '/partials/footer.php'; ?>
