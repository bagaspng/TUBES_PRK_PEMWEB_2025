<?php
// public/logout.php
require_once __DIR__ . '/../src/helpers/auth.php';

session_unset();
session_destroy();

header('Location: \TUBES_PRK_PEMWEB_2025\kelompok\kelompok_04\public\login.php');
exit;
