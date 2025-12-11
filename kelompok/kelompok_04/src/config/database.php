<?php

$host     = '127.0.0.1';  
$user     = 'root';
$password = '';            
$dbname   = 'puskesmas_db';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

date_default_timezone_set('Asia/Jakarta');
