<?php
// src/helpers/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

function require_role(array $roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
        header('Location: ../login.php');
        exit;
    }
}

function redirect_by_role(string $role) {
    switch ($role) {
        case 'pasien':
            header('Location: /kelompok/kelompok_04/public/pasien/dashboard.php');
            break;
        case 'dokter':
            header('Location: /kelompok/kelompok_04/public/dokter/dashboard.php');
            break;
        case 'admin':
            header('Location: /kelompok/kelompok_04/public/admin/dashboard.php');
            break;
        default:
            header('Location: /kelompok/kelompok_04/public/login.php');
    }
    exit;
}
