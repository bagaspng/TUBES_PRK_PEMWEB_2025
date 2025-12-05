// src/helpers/auth.php
<?php
session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /kelompok/kelompok_04/public/login.php');
        exit;
    }
}

function require_role($roles = []) {
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        header('Location: /kelompok/kelompok_04/public/login.php');
        exit;
    }
}
