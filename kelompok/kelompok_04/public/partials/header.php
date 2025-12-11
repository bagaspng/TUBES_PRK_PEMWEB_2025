<?php
// public/partials/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Puskesmas Digital</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/kelompok/kelompok_04/public/css/style.css">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #f5f7fa;
        }
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e3e6ec;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-brand {
            font-weight: 600;
            color: #45BC7D;
            text-decoration: none;
        }
        .navbar-right a {
            margin-left: 12px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }
        .container {
            max-width: 960px;
            margin: 24px auto;
            padding: 0 16px;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="/kelompok/kelompok_04/public/index.php" class="navbar-brand">Puskesmas Digital</a>
    <div class="navbar-right">
        <?php if (isset($_SESSION['role'])): ?>
            <span style="font-size: 14px;">Role: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
            <a href="/kelompok/kelompok_04/public/logout.php">Logout</a>
        <?php else: ?>
            <a href="/kelompok/kelompok_04/public/login.php">Login</a>
            <a href="/kelompok/kelompok_04/public/register.php">Daftar</a>
        <?php endif; ?>
    </div>
</nav>
<div class="container">
