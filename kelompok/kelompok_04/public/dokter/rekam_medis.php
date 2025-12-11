<?php
session_start();
require_once '../../src/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$q_dokter = $conn->query("SELECT id_dokter, nama_dokter FROM dokter WHERE id_user = '$id_user'");
$data_dokter = $q_dokter->fetch_assoc();
$id_dokter_login = $data_dokter['id_dokter'];

if (isset($_GET['ajax_search'])) {
    $keyword = $_GET['keyword'] ?? '';
    
    $sql = "SELECT rm.*, p.nama_lengkap, p.nik, po.nama_poli 
            FROM rekam_medis rm
            JOIN pasien p ON rm.id_pasien = p.id_pasien
            JOIN poli po ON rm.id_poli = po.id_poli
            WHERE rm.id_dokter = '$id_dokter_login'";

    if (!empty($keyword)) {
        $safe_q = $conn->real_escape_string($keyword);
        $sql .= " AND (p.nama_lengkap LIKE '%$safe_q%' OR p.nik LIKE '%$safe_q%' OR rm.diagnosa LIKE '%$safe_q%')";
    }

    $sql .= " ORDER BY rm.tanggal_kunjungan DESC, rm.created_at DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($rm = $result->fetch_assoc()) {
            $tgl = date('d M Y', strtotime($rm['tanggal_kunjungan']));
            
            echo '
            <a href="detail_rekam_medis.php?id='.$rm['id_rekam'].'" class="block bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md hover:border-emerald-200 transition cursor-pointer group relative mb-4">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-slate-800 group-hover:text-emerald-600 transition">'.$rm['nama_lengkap'].'</h3>
                            <span class="bg-blue-50 text-blue-600 text-[10px] uppercase font-bold px-2 py-1 rounded tracking-wide border border-blue-100">'.$rm['nama_poli'].'</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-3 font-mono">NIK: '.$rm['nik'].'</p>
                        <div class="flex items-center text-sm text-slate-600 gap-3">
                            <span class="font-medium text-slate-500">'.$tgl.'</span>
                            <span class="w-1.5 h-1.5 bg-slate-300 rounded-full"></span>
                            <span class="font-medium text-slate-800 line-clamp-1">'.$rm['diagnosa'].'</span>
                        </div>
                    </div>
                    <div class="text-slate-300 group-hover:text-emerald-500 transition transform group-hover:translate-x-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
            </a>
            ';
        }
    } else {
        echo '<div class="text-center p-8 text-slate-400">Data rekam medis tidak ditemukan.</div>';
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekam Medis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">
    
    <?php require_once 'sidebar.php'; ?>

    <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden bg-slate-50">
        
        <main class="w-full grow p-8 max-w-5xl mx-auto">
            
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-800">Rekam Medis Pasien</h2>
                <p class="text-slate-500 mt-1">Riwayat pemeriksaan pasien Anda</p>
            </div>

            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-6">
                <div class="relative">
                    <svg class="w-5 h-5 absolute left-3 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    
                    <input type="text" 
                           id="searchInput" 
                           oninput="searchLive()" 
                           placeholder="Cari pasien (Nama, NIK, Diagnosa)..." 
                           class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition text-sm">
                </div>
            </div>

            <div id="resultsContainer" class="space-y-4">
                <?php
                $sql_default = "SELECT rm.*, p.nama_lengkap, p.nik, po.nama_poli 
                                FROM rekam_medis rm
                                JOIN pasien p ON rm.id_pasien = p.id_pasien
                                JOIN poli po ON rm.id_poli = po.id_poli
                                WHERE rm.id_dokter = '$id_dokter_login' 
                                ORDER BY rm.tanggal_kunjungan DESC, rm.created_at DESC";
                
                $res_default = $conn->query($sql_default);
                
                if ($res_default && $res_default->num_rows > 0):
                    while($rm = $res_default->fetch_assoc()):
                ?>
                    <a href="detail_rekam_medis.php?id=<?= $rm['id_rekam'] ?>" class="block bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md hover:border-emerald-200 transition cursor-pointer group relative mb-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-bold text-slate-800 group-hover:text-emerald-600 transition"><?= htmlspecialchars($rm['nama_lengkap']) ?></h3>
                                    <span class="bg-blue-50 text-blue-600 text-[10px] uppercase font-bold px-2 py-1 rounded tracking-wide border border-blue-100"><?= htmlspecialchars($rm['nama_poli']) ?></span>
                                </div>
                                <p class="text-xs text-slate-400 mb-3 font-mono">NIK: <?= htmlspecialchars($rm['nik']) ?></p>
                                <div class="flex items-center text-sm text-slate-600 gap-3">
                                    <span class="font-medium text-slate-500"><?= date('d M Y', strtotime($rm['tanggal_kunjungan'])) ?></span>
                                    <span class="w-1.5 h-1.5 bg-slate-300 rounded-full"></span>
                                    <span class="font-medium text-slate-800 line-clamp-1"><?= htmlspecialchars($rm['diagnosa']) ?></span>
                                </div>
                            </div>
                            <div class="text-slate-300 group-hover:text-emerald-500 transition transform group-hover:translate-x-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </div>
                        </div>
                    </a>
                <?php 
                    endwhile;
                else:
                    echo '<div class="text-center p-12 bg-white rounded-xl border border-slate-200 text-slate-400 italic">Belum ada data rekam medis untuk pasien Anda.</div>';
                endif; 
                ?>
            </div>
        </main>
    </div>
</div>

<script>
let typingTimer;
const doneTypingInterval = 300;

function searchLive() {
    clearTimeout(typingTimer);
    const input = document.getElementById('searchInput');
    const container = document.getElementById('resultsContainer');
    
    container.style.opacity = '0.5';

    typingTimer = setTimeout(function() {
        const keyword = input.value;
        fetch(`rekam_medis.php?ajax_search=1&keyword=${keyword}`)
            .then(response => response.text())
            .then(data => {
                container.innerHTML = data;
                container.style.opacity = '1';
            })
            .catch(error => {
                console.error('Error:', error);
                container.style.opacity = '1';
            });
    }, doneTypingInterval);
}
</script>

</body>
</html>