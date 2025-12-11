<?php
// public/admin/cetak_rujukan_pdf.php
session_start();

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID Rujukan tidak valid');
}

$idRujukan = (int)$_GET['id'];

$sqlRujukan = "
    SELECT 
        r.id_rujukan,
        r.faskes_tujuan,
        r.poli_tujuan,
        r.diagnosa,
        r.tanggal_rujukan,
        p.nama_lengkap AS nama_pasien,
        p.nik,
        p.no_rm,
        p.tanggal_lahir,
        p.jenis_kelamin,
        d.nama_dokter,
        d.kode_dokter,
        d.spesialis,
        po.nama_poli,
        rm.keluhan,
        rm.diagnosa AS diagnosa_rekam,
        rm.pemeriksaan,
        rm.resep_obat,
        rm.tensi,
        rm.suhu,
        rm.nadi,
        rm.rr,
        rm.tanggal_kunjungan
    FROM rujukan r
    JOIN rekam_medis rm ON r.id_rekam = rm.id_rekam
    JOIN pasien p ON rm.id_pasien = p.id_pasien
    JOIN dokter d ON r.id_dokter = d.id_dokter
    JOIN poli po ON d.id_poli = po.id_poli
    WHERE r.id_rujukan = ?
";

$stmt = $conn->prepare($sqlRujukan);
$stmt->bind_param('i', $idRujukan);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die('Data rujukan tidak ditemukan');
}

function hitungUmur($tanggalLahir) {
    if (!$tanggalLahir) return '-';
    $lahir = new DateTime($tanggalLahir);
    $sekarang = new DateTime();
    $umur = $sekarang->diff($lahir);
    return $umur->y . ' tahun';
}

function formatTanggalIndonesia($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

$logoPath = __DIR__ . '/../img/puskesmas.svg';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoContent = file_get_contents($logoPath);
    $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode($logoContent);
}

$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Rujukan</title>
    <style>
        @page {
            margin: 1.5cm 2cm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
        }
        .kop-surat {
            border-bottom: 4px solid #000;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .kop-surat table {
            width: 100%;
        }
        .logo {
            width: 80px;
            height: 80px;
        }
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .kop-info {
            text-align: left;
            padding-left: 15px;
        }
        .kop-info h1 {
            margin: 0;
            font-size: 24pt;
            font-weight: bold;
            color: #1a1a1a;
        }
        .kop-info p {
            margin: 3px 0;
            font-size: 10pt;
            color: #555;
        }
        .judul-surat {
            text-align: center;
            margin-bottom: 30px;
        }
        .judul-surat h2 {
            margin: 10px 0 5px 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .judul-surat p {
            margin: 0;
            font-size: 10pt;
            color: #666;
        }
        .isi-surat {
            text-align: justify;
            margin-bottom: 20px;
        }
        .isi-surat p {
            margin: 10px 0;
        }
        .data-pasien {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .data-pasien table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-pasien td {
            padding: 8px 0;
            vertical-align: top;
        }
        .data-pasien td:first-child {
            width: 35%;
            color: #666;
        }
        .data-pasien td:last-child {
            font-weight: 500;
            color: #1a1a1a;
        }
        .hasil-pemeriksaan {
            margin: 20px 0;
        }
        .hasil-pemeriksaan h3 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
            color: #1a1a1a;
        }
        .vital-signs {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .vital-signs table {
            width: 100%;
            border-collapse: collapse;
        }
        .vital-signs td {
            width: 25%;
            padding: 5px;
            text-align: center;
        }
        .vital-label {
            font-size: 9pt;
            color: #666;
            display: block;
            margin-bottom: 3px;
        }
        .vital-value {
            font-size: 11pt;
            font-weight: bold;
            color: #1a1a1a;
        }
        .keluhan-box, .pemeriksaan-box, .terapi-box {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
        }
        .keluhan-label {
            font-size: 9pt;
            color: #666;
            margin-bottom: 5px;
            display: block;
        }
        .diagnosa-box {
            background-color: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .diagnosa-box p {
            margin: 0;
            font-weight: 600;
            color: #1a1a1a;
        }
        .ttd-section {
            margin-top: 50px;
            text-align: right;
        }
        .ttd-box {
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        .ttd-tanggal {
            margin-bottom: 80px;
        }
        .ttd-nama {
            border-top: 1px solid #000;
            padding-top: 10px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .ttd-detail {
            font-size: 10pt;
            color: #666;
            margin-top: 3px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 9pt;
            color: #999;
        }
    </style>
</head>
<body>

    <div class="kop-surat">
        <table>
            <tr>
                <td style="width: 90px;">
                    <div class="logo">
                        <img src="' . $logoBase64 . '" alt="Logo Puskesmas">
                    </div>
                </td>
                <td class="kop-info">
                    <h1>PUSKESMAS</h1>
                    <p>Jl. Kesehatan No. 123, Bandung, Jawa Barat</p>
                    <p>Telp: (022) 1234567 | Email: puskesmas@kesehatan.go.id</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="judul-surat">
        <h2>Surat Rujukan</h2>
        <p>Nomor: ' . date('Y') . '/SR/' . str_pad($data['id_rujukan'], 4, '0', STR_PAD_LEFT) . '/' . date('m') . '</p>
    </div>

    <div class="isi-surat">
        <p>Kepada Yth,</p>
        <p style="font-weight: bold; margin-left: 20px;">
            ' . htmlspecialchars($data['poli_tujuan'] ?: 'Dokter Spesialis') . '<br>
            ' . htmlspecialchars($data['faskes_tujuan']) . '
        </p>

        <p>Dengan hormat,</p>
        <p>Bersama ini kami mohon bantuan pemeriksaan dan penanganan lebih lanjut terhadap pasien:</p>

        <div class="data-pasien">
            <table>
                <tr>
                    <td>Nama Lengkap</td>
                    <td>: ' . htmlspecialchars($data['nama_pasien']) . '</td>
                </tr>
                <tr>
                    <td>NIK</td>
                    <td>: ' . htmlspecialchars($data['nik']) . '</td>
                </tr>
                <tr>
                    <td>No. Rekam Medis</td>
                    <td>: ' . htmlspecialchars($data['no_rm']) . '</td>
                </tr>
                <tr>
                    <td>Tanggal Lahir / Umur</td>
                    <td>: ' . date('d/m/Y', strtotime($data['tanggal_lahir'])) . ' (' . hitungUmur($data['tanggal_lahir']) . ')</td>
                </tr>
                <tr>
                    <td>Jenis Kelamin</td>
                    <td>: ' . ($data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . '</td>
                </tr>
            </table>
        </div>

        <!-- Hasil Pemeriksaan -->
        <div class="hasil-pemeriksaan">
            <h3>Hasil Pemeriksaan:</h3>
            
            <!-- Vital Signs -->
            <div class="vital-signs">
                <table>
                    <tr>
                        <td>
                            <span class="vital-label">Tekanan Darah</span>
                            <span class="vital-value">' . htmlspecialchars($data['tensi']) . '</span>
                        </td>
                        <td>
                            <span class="vital-label">Suhu</span>
                            <span class="vital-value">' . htmlspecialchars($data['suhu']) . '</span>
                        </td>
                        <td>
                            <span class="vital-label">Nadi</span>
                            <span class="vital-value">' . htmlspecialchars($data['nadi']) . '</span>
                        </td>
                        <td>
                            <span class="vital-label">RR</span>
                            <span class="vital-value">' . htmlspecialchars($data['rr']) . '</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Keluhan -->
            <div class="keluhan-box">
                <span class="keluhan-label">Keluhan:</span>
                ' . nl2br(htmlspecialchars($data['keluhan'])) . '
            </div>

            ' . ($data['pemeriksaan'] ? '
            <div class="pemeriksaan-box">
                <span class="keluhan-label">Pemeriksaan:</span>
                ' . nl2br(htmlspecialchars($data['pemeriksaan'])) . '
            </div>
            ' : '') . '
        </div>

        <!-- Diagnosa -->
        <div class="hasil-pemeriksaan">
            <h3>Diagnosa:</h3>
            <div class="diagnosa-box">
                <p>' . nl2br(htmlspecialchars($data['diagnosa'])) . '</p>
            </div>
        </div>

        ' . ($data['resep_obat'] ? '
        <div class="hasil-pemeriksaan">
            <h3>Penanganan yang telah diberikan:</h3>
            <div class="terapi-box">
                ' . nl2br(htmlspecialchars($data['resep_obat'])) . '
            </div>
        </div>
        ' : '') . '

        <p style="margin-top: 30px;">
            Demikian surat rujukan ini kami buat untuk dapat ditindaklanjuti. 
            Atas perhatian dan kerjasamanya, kami ucapkan terima kasih.
        </p>
    </div>

    <!-- Tanda Tangan -->
    <div class="ttd-section">
        <div class="ttd-box">
            <div class="ttd-tanggal">
                Bandung, ' . formatTanggalIndonesia($data['tanggal_rujukan']) . '<br>
                Dokter yang Merujuk,
            </div>
            <div class="ttd-nama">
                ' . htmlspecialchars($data['nama_dokter']) . '
            </div>
            <div class="ttd-detail">
                ' . htmlspecialchars($data['spesialis']) . '<br>
                NIP: ' . htmlspecialchars($data['kode_dokter']) . '
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Dokumen ini dicetak pada ' . date('d/m/Y H:i') . ' WIB | Surat rujukan resmi dari Puskesmas
    </div>
</body>
</html>
';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');
$options->set('dpi', 96);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$filename = 'Surat_Rujukan_' . $data['no_rm'] . '_' . date('YmdHis') . '.pdf';

$dompdf->stream($filename, [
    'Attachment' => false  
]);

exit;