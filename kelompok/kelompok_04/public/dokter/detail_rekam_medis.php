<?php
session_start();
require_once '../../src/config/database.php';
require_once 'header.php';
require_once 'sidebar.php';

$id_rekam = $_GET['id'] ?? null;

if(!$id_rekam) {
    echo "<script>window.location='rekam_medis.php';</script>";
    exit;
}

$query = "SELECT rm.*, p.*, po.nama_poli, a.nomor_antrian 
          FROM rekam_medis rm
          JOIN pasien p ON rm.id_pasien = p.id_pasien
          JOIN poli po ON rm.id_poli = po.id_poli
          LEFT JOIN antrian a ON rm.id_antrian = a.id_antrian
          WHERE rm.id_rekam = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_rekam);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if(!$data) {
    echo "<script>alert('Data tidak ditemukan'); window.location='rekam_medis.php';</script>";
    exit;
}

$q_rujukan = $conn->query("SELECT * FROM rujukan WHERE id_rekam = '$id_rekam'");
$rujukan = $q_rujukan->fetch_assoc();

$umur = date_diff(date_create($data['tanggal_lahir']), date_create('today'))->y;
?>

<main class="flex-1 overflow-y-auto bg-slate-50 relative pb-32">
    <div class="p-8 max-w-5xl mx-auto">
        
        <a href="rekam_medis.php" class="inline-flex items-center text-slate-500 hover:text-slate-800 mb-6 transition cursor-pointer">
            <div class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center mr-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </div>
            Kembali ke Daftar
        </a>

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Detail Rekam Medis</h2>
            <p class="text-slate-500">Data pemeriksaan yang telah selesai</p>
        </div>

        <div class="bg-emerald-500 rounded-2xl p-6 text-white shadow-lg mb-8 relative z-10 overflow-hidden">
             <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
             
             <div class="relative z-10 flex justify-between items-start">
                <div>
                    <h3 class="text-2xl font-bold"><?= $data['nama_lengkap'] ?></h3>
                    <div class="flex gap-6 mt-4 text-emerald-50 text-sm">
                        <div><span class="opacity-70 text-xs uppercase font-bold">No. Antrian</span><br><strong class="text-white text-lg">A-<?= str_pad($data['nomor_antrian'], 3, '0', STR_PAD_LEFT) ?></strong></div>
                        <div><span class="opacity-70 text-xs uppercase font-bold">Poli</span><br><strong class="text-white"><?= $data['nama_poli'] ?></strong></div>
                        <div><span class="opacity-70 text-xs uppercase font-bold">Umur</span><br><strong class="text-white"><?= $umur ?> Tahun</strong></div>
                        <div><span class="opacity-70 text-xs uppercase font-bold">Gender</span><br><strong class="text-white"><?= $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></strong></div>
                    </div>
                </div>
                <div class="bg-white/20 px-4 py-2 rounded-lg text-sm border border-white/10">
                    <?= date('d F Y', strtotime($data['tanggal_kunjungan'])) ?>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <h4 class="font-bold text-slate-800 mb-4">Tanda Vital</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Tekanan Darah</label>
                        <input type="text" value="<?= $data['td_sistolik'] ?> / <?= $data['td_diastolik'] ?> mmHg" readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700">                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Suhu Tubuh</label>
                        <input type="text" value="<?= $data['suhu'] ?> °C" readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Nadi</label>
                        <input type="text" value="<?= $data['nadi'] ?> bpm" readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Pernapasan</label>
                        <input type="text" value="<?= $data['rr'] ?> x/menit" readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700">
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                <h4 class="font-bold text-slate-800">Data Pemeriksaan</h4>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Keluhan Pasien</label>
                    <div class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 min-h-[80px]">
                        <?= nl2br($data['keluhan']) ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Diagnosa</label>
                    <div class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 min-h-[60px] font-semibold">
                        <?= nl2br($data['diagnosa']) ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Resep Obat</label>
                    <div class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 min-h-[80px]">
                        <?= nl2br($data['resep_obat']) ?>
                    </div>
                </div>
            </div>

            <?php if ($rujukan): ?>
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-blue-500">
                <h4 class="font-bold text-slate-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Data Rujukan
                </h4>
                <div class="grid gap-4">
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Rumah Sakit Tujuan</label>
                        <input type="text" value="<?= $rujukan['faskes_tujuan'] ?>" readonly class="w-full bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-sm text-blue-900 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Poli Tujuan</label>
                        <textarea readonly class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700"><?= $rujukan['poli_tujuan'] ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>
</body>
</html>