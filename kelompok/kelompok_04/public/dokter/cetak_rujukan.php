<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

// Cek Login
if (!isset($_SESSION['user_id'])) { die("Akses ditolak"); }

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM dokter WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$dokter = $stmt->get_result()->fetch_assoc();

$id_pasien    = $_POST['id_pasien'] ?? 0;
$faskes_tujuan = $_POST['rs_tujuan'] ?? '-'; 
$alasan       = $_POST['alasan'] ?? '-';

if($id_pasien == 0) { die("Error: Pasien belum dipilih."); }

$q_rekam = $conn->query("SELECT id_rekam FROM rekam_medis WHERE id_pasien = '$id_pasien' ORDER BY created_at DESC LIMIT 1");
$data_rekam = $q_rekam->fetch_assoc();

if (!$data_rekam) {
    die("Error: Pasien ini belum memiliki riwayat Rekam Medis. Periksa pasien terlebih dahulu sebelum membuat rujukan manual.");
}

$id_rekam = $data_rekam['id_rekam'];
$id_dokter = $dokter['id_dokter'];

$stmt_insert = $conn->prepare("INSERT INTO rujukan (id_rekam, id_dokter, faskes_tujuan, poli_tujuan, diagnosa, alasan_rujukan, tanggal_rujukan, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), NOW(), NOW())");

$stmt_insert->bind_param("iissss", $id_rekam, $id_dokter, $faskes_tujuan, $poli_tujuan, $diagnosa, $alasan);
$stmt_insert->execute();

$qp = $conn->query("SELECT * FROM pasien WHERE id_pasien = '$id_pasien'");
$pasien = $qp->fetch_assoc();

$lahir = new DateTime($pasien['tanggal_lahir']);
$today = new DateTime();
$umur = $today->diff($lahir)->y;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Surat Rujukan</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; margin: 0; padding: 20px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; }
        
        .kop-surat { text-align: center; border-bottom: 3px double black; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat h2 { margin: 0; font-size: 16pt; text-transform: uppercase; }
        .kop-surat p { margin: 2px 0; font-size: 10pt; }
        
        .nomor-surat { text-align: center; margin-bottom: 30px; }
        .isi-surat { text-align: justify; line-height: 1.6; }
        
        table { width: 100%; margin-left: 20px; margin-bottom: 10px; }
        td { vertical-align: top; padding: 2px; }
        .label { width: 140px; }
        
        .ttd-area { float: right; width: 250px; text-align: center; margin-top: 50px; }
        .ttd-area p { margin-bottom: 80px; }
        
        @media print {
            @page { margin: 2cm; }
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="container">
        <div class="kop-surat">
            <h2>PEMERINTAH KOTA BANDAR LAMPUNG</h2>
            <h2>UPTD PUSKESMAS SEHAT SENTOSA</h2>
            <p>Jl. Jendral Sudirman No. 123, Bandar Lampung. Telp: (0721) 123456</p>
        </div>

        <div class="nomor-surat">
            <h3 style="text-decoration: underline; margin-bottom: 5px;">SURAT RUJUKAN PUSKESMAS</h3>
            <span>Nomor: 445/Ref-<?= date('ymd') ?>/PKM/<?= date('Y') ?></span>
        </div>

        <div class="isi-surat">
            <p>Kepada Yth,<br>
            <strong>Dokter Spesialis <?= htmlspecialchars($poli_tujuan) ?></strong><br>
            di <?= htmlspecialchars($faskes_tujuan) ?></p>

            <p>Dengan hormat,</p>
            <p>Mohon pemeriksaan dan penanganan lebih lanjut terhadap pasien:</p>

            <table>
                <tr>
                    <td class="label">Nama Lengkap</td>
                    <td>: <strong><?= $pasien['nama_lengkap'] ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Nomor RM</td>
                    <td>: <?= $pasien['no_rm'] ?></td>
                </tr>
                <tr>
                    <td class="label">NIK</td>
                    <td>: <?= $pasien['nik'] ?></td>
                </tr>
                <tr>
                    <td class="label">Umur / JK</td>
                    <td>: <?= $umur ?> Tahun / <?= ($pasien['jenis_kelamin']=='L'?'Laki-laki':'Perempuan') ?></td>
                </tr>
                <tr>
                    <td class="label">Alamat</td>
                    <td>: Jl. Pasien No. 123 (Data Alamat Belum Ada di DB)</td>
                </tr>
            </table>

            <p>Berdasarkan pemeriksaan awal kami, pasien tersebut didiagnosa sementara:</p>
            
            <table>
                <tr>
                    <td class="label">Diagnosa</td>
                    <td>: <strong><?= htmlspecialchars($diagnosa) ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Alasan Rujukan</td>
                    <td>: <?= htmlspecialchars($alasan) ?></td>
                </tr>
            </table>

            <p>Demikian surat rujukan ini kami buat untuk dapat dipergunakan sebagaimana mestinya. Atas kerjasamanya kami ucapkan terima kasih.</p>
        </div>

        <div class="ttd-area">
            <p>Bandar Lampung, <?= date('d F Y') ?><br>Dokter Pemeriksa,</p>
            
            <br>
            <span style="font-weight: bold; text-decoration: underline;"><?= $dokter['nama_dokter'] ?></span><br>
            <span>NIP. 198501122010012001</span>
        </div>
    </div>

</body>
</html>