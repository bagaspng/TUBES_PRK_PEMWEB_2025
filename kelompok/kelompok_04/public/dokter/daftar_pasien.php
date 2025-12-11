<?php
session_start();
require_once '../../src/config/database.php';

if (isset($_GET['ajax_search'])) {
    $keyword = $_GET['keyword'] ?? '';
    $status  = $_GET['status'] ?? '';
    
    $sql = "SELECT a.*, p.nama_lengkap, p.tanggal_lahir, po.nama_poli 
            FROM antrian a 
            JOIN pasien p ON a.id_pasien = p.id_pasien 
            JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal
            JOIN dokter d ON j.id_dokter = d.id_dokter
            JOIN poli po ON d.id_poli = po.id_poli
            WHERE DATE(a.waktu_daftar) = CURDATE()"; 

    if (!empty($keyword)) {
        $safe_key = $conn->real_escape_string($keyword);
        $sql .= " AND (p.nama_lengkap LIKE '%$safe_key%' OR a.nomor_antrian LIKE '%$safe_key%')";
    }

    if (!empty($status)) {
        $safe_status = $conn->real_escape_string($status);
        $sql .= " AND a.status = '$safe_status'";
    }

    $sql .= " ORDER BY a.waktu_daftar DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($p = $result->fetch_assoc()) {
            $umur = date_diff(date_create($p['tanggal_lahir']), date_create('today'))->y;
            $no_antrian = 'A-' . str_pad($p['nomor_antrian'], 3, '0', STR_PAD_LEFT);
            
            $statusClass = 'bg-slate-100 text-slate-600';
            if($p['status'] == 'menunggu') $statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-100';
            if($p['status'] == 'diperiksa') $statusClass = 'bg-emerald-100 text-emerald-800 border border-emerald-100';
            if($p['status'] == 'selesai') $statusClass = 'bg-green-100 text-green-800 border border-green-100';
            
            $btnAksi = '';
            if ($p['status'] == 'menunggu') {
                $btnAksi = '<a href="pemeriksaan.php?id='.$p['id_antrian'].'" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2 rounded-lg text-xs font-bold transition shadow-sm inline-block">Periksa</a>';
            } elseif ($p['status'] == 'diperiksa') {
                $btnAksi = '<a href="pemeriksaan.php?id='.$p['id_antrian'].'" class="bg-slate-700 hover:bg-slate-800 text-white px-5 py-2 rounded-lg text-xs font-bold transition shadow-sm inline-block">Lanjutkan</a>';
            } else {
                $btnAksi = '<span class="text-slate-400 text-xs italic font-medium">Selesai</span>';
            }

            echo '
            <tr class="hover:bg-slate-50 transition border-b border-slate-50 last:border-b-0">
                <td class="px-6 py-4 font-medium text-slate-800">'.$no_antrian.'</td>
                <td class="px-6 py-4 font-semibold text-slate-700">'.$p['nama_lengkap'].'</td>
                <td class="px-6 py-4 text-slate-500">'.$umur.' tahun</td>
                <td class="px-6 py-4 text-slate-500">'.$p['nama_poli'].'</td>
                <td class="px-6 py-4">
                    <span class="'.$statusClass.' px-3 py-1.5 rounded-md text-xs font-semibold">
                        '.ucfirst($p['status']).'
                    </span>
                </td>
                <td class="px-6 py-4">
                    '.$btnAksi.'
                </td>
            </tr>';
        }
    } else {
        echo '<tr><td colspan="6" class="p-8 text-center text-slate-400 italic">Data pasien tidak ditemukan.</td></tr>';
    }
    exit;
}

require_once 'header.php';
require_once 'sidebar.php';
?>

<main class="flex-1 overflow-y-auto p-8 h-screen bg-slate-50">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Daftar Pasien</h2>
            <p class="text-slate-500 mt-1">Kelola daftar pasien yang terdaftar hari ini</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-6 flex flex-col md:flex-row gap-4">
        <div class="relative flex-1">
            <svg class="w-5 h-5 absolute left-3 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" 
                   id="inputKeyword" 
                   oninput="searchLive()" 
                   placeholder="Cari pasien berdasarkan nama atau nomor antrian..." 
                   class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 transition text-sm">
        </div>
        
        <select id="inputStatus" onchange="searchLive()" class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
            <option value="">Semua Status</option>
            <option value="menunggu">Menunggu</option>
            <option value="diperiksa">Diperiksa</option>
            <option value="selesai">Selesai</option>
        </select>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4 font-semibold">No. Antrian</th>
                    <th class="px-6 py-4 font-semibold">Nama Pasien</th>
                    <th class="px-6 py-4 font-semibold">Umur</th>
                    <th class="px-6 py-4 font-semibold">Poli</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody id="tabelBody" class="divide-y divide-slate-100 text-sm">
                <?php 
                $sql_init = "SELECT a.*, p.nama_lengkap, p.tanggal_lahir, po.nama_poli 
                             FROM antrian a 
                             JOIN pasien p ON a.id_pasien = p.id_pasien 
                             JOIN jadwal_praktik j ON a.id_jadwal = j.id_jadwal
                             JOIN dokter d ON j.id_dokter = d.id_dokter
                             JOIN poli po ON d.id_poli = po.id_poli
                             WHERE DATE(a.waktu_daftar) = CURDATE()  
                             ORDER BY a.waktu_daftar DESC"; // 
                $res_init = $conn->query($sql_init);

                if ($res_init->num_rows > 0):
                    while($p = $res_init->fetch_assoc()):
                        $umur = date_diff(date_create($p['tanggal_lahir']), date_create('today'))->y;
                        
                        $statusClass = 'bg-slate-100 text-slate-600';
                        if($p['status'] == 'menunggu') $statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-100';
                        if($p['status'] == 'diperiksa') $statusClass = 'bg-emerald-100 text-emerald-800 border border-emerald-100';
                        if($p['status'] == 'selesai') $statusClass = 'bg-green-100 text-green-800 border border-green-100';
                ?>
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-medium text-slate-800">A-<?= str_pad($p['nomor_antrian'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td class="px-6 py-4 font-semibold text-slate-700"><?= $p['nama_lengkap'] ?></td>
                    <td class="px-6 py-4 text-slate-500"><?= $umur ?> tahun</td>
                    <td class="px-6 py-4 text-slate-500"><?= $p['nama_poli'] ?></td>
                    <td class="px-6 py-4">
                        <span class="<?= $statusClass ?> px-3 py-1.5 rounded-md text-xs font-semibold">
                            <?= ucfirst($p['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php 
                        if ($p['status'] == 'menunggu') {
                            echo '<a href="pemeriksaan.php?id='.$p['id_antrian'].'" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2 rounded-lg text-xs font-bold transition shadow-sm inline-block">Periksa</a>';
                        } elseif ($p['status'] == 'diperiksa') {
                            echo '<a href="pemeriksaan.php?id='.$p['id_antrian'].'" class="bg-slate-700 hover:bg-slate-800 text-white px-5 py-2 rounded-lg text-xs font-bold transition shadow-sm inline-block">Lanjutkan</a>';
                        } else {
                            echo '<span class="text-slate-400 text-xs italic font-medium">Selesai</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" class="p-8 text-center text-slate-400 italic">Belum ada antrian pasien hari ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
let typingTimer;
const doneTypingInterval = 200; 

function searchLive() {
    clearTimeout(typingTimer);
    const keyword = document.getElementById('inputKeyword').value;
    const status  = document.getElementById('inputStatus').value;
    const tbody   = document.getElementById('tabelBody');
    tbody.style.opacity = '0.5';

    typingTimer = setTimeout(function() {
        fetch(`daftar_pasien.php?ajax_search=1&keyword=${keyword}&status=${status}`)
            .then(response => response.text())
            .then(data => {
                tbody.innerHTML = data;
                tbody.style.opacity = '1';
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.style.opacity = '1';
            });
    }, doneTypingInterval);
}
</script>

</body>
</html>