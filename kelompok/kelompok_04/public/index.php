<?php
// public/index.php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/helpers/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

redirect_by_role($_SESSION['role']);
