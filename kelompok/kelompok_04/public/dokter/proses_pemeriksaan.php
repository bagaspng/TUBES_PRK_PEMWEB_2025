<?php
session_start();
require_once '../../src/config/database.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit;
}

$id_antrian = $_POST['id_antrian'];
$id_pasien  = $_POST['id_pasien'];
$td_sistolik= $_POST['td_sistolik'];
$td_diastolik= $_POST['td_diastolik'];
$suhu       = $_POST['suhu'];
$nadi       = $_POST['nadi'];
$rr         = $_POST['rr'];
$keluhan    = $_POST['keluhan'];
$diagnosa   = $_POST['diagnosa'];
$resep      = $_POST['resep'];
$tindakan   = $_POST['tindakan']; 

$rs_tujuan      = $_POST['rs_tujuan'] ?? '';
$alasan_rujukan = $_POST['alasan_rujukan'] ?? '';

$id_user = $_SESSION['user_id'];
$q_dokter = $conn->query("SELECT id_dokter, id_poli FROM dokter WHERE id_user = '$id_user'");
$data_dokter = $q_dokter->fetch_assoc();
$id_dokter = $data_dokter['id_dokter'];
$id_poli   = $data_dokter['id_poli'];

$tgl_sekarang = date('Y-m-d H:i:s');
$tgl_kunjungan = date('Y-m-d');
$action = $_POST['action'];

try {
    $conn->begin_transaction();

    $check = $conn->query("SELECT id_rekam FROM rekam_medis WHERE id_antrian = '$id_antrian'");
    
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $id_rekam = $row['id_rekam'];
        
        $stmt = $conn->prepare("UPDATE rekam_medis SET 
            td_sistolik=?, suhu=?, nadi=?, rr=?, 
            keluhan=?, diagnosa=?, resep_obat=?, pemeriksaan=?, 
            updated_at=?
            WHERE id_rekam=?");
        $stmt->bind_param("ssssssssssi", 
            $td_sistolik,$td_diastolik, $suhu, $nadi, $rr, 
            $keluhan, $diagnosa, $resep, $tindakan, 
            $tgl_sekarang, $id_rekam
        );
    } else {
        $stmt = $conn->prepare("INSERT INTO rekam_medis 
            (id_antrian, id_pasien, id_dokter, id_poli, tanggal_kunjungan, td_sistolik, td_diastolik, suhu, nadi, rr, keluhan, diagnosa, resep_obat, pemeriksaan, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiissssssssssss", 
            $id_antrian, $id_pasien, $id_dokter, $id_poli, $tgl_kunjungan,
            $td_sistolik,$td_diastolik,$suhu, $nadi, $rr, 
            $keluhan, $diagnosa, $resep, $tindakan, 
            $tgl_sekarang, $tgl_sekarang
        );
    }
    $stmt->execute();
    
    if ($check->num_rows == 0) {
        $id_rekam = $conn->insert_id;
    }

    $check_rujukan = $conn->query("SELECT id_rujukan FROM rujukan WHERE id_rekam = '$id_rekam'");
    $rujukan_exist = $check_rujukan->num_rows > 0;

    if (!empty($rs_tujuan)) {
        if ($rujukan_exist) {
            $stmt_r = $conn->prepare("UPDATE rujukan SET faskes_tujuan=?, diagnosa=?, alasan_rujukan=?, updated_at=? WHERE id_rekam=?");
            $stmt_r->bind_param("ssssi", $rs_tujuan, $diagnosa, $alasan_rujukan, $tgl_sekarang, $id_rekam);
        } else {
            $poli_tujuan_default = 'Dokter Umum';
            $stmt_r = $conn->prepare("INSERT INTO rujukan (id_rekam, id_dokter, faskes_tujuan, poli_tujuan, diagnosa, alasan_rujukan, tanggal_rujukan, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_r->bind_param("iisssssss", $id_rekam, $id_dokter, $rs_tujuan, $poli_tujuan_default, $diagnosa, $alasan_rujukan, $tgl_kunjungan, $tgl_sekarang, $tgl_sekarang);
        }
        $stmt_r->execute();
    } elseif ($rujukan_exist) {
        $conn->query("DELETE FROM rujukan WHERE id_rekam = '$id_rekam'");
    }

    if ($action === 'selesai') {
        $conn->query("UPDATE antrian SET status = 'selesai', updated_at = '$tgl_sekarang' WHERE id_antrian = '$id_antrian'");
        $redirect_url = "dashboard.php";
        $message = "Pemeriksaan selesai!";
    } else {
        $conn->query("UPDATE antrian SET status = 'diperiksa', updated_at = '$tgl_sekarang' WHERE id_antrian = '$id_antrian' AND status = 'menunggu'");
        $redirect_url = "pemeriksaan.php?id=" . $id_antrian;
        $message = "Data berhasil disimpan sementara.";
    }

    $conn->commit();
    echo "<script>alert('$message'); window.location='$redirect_url';</script>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Gagal menyimpan data: " . $e->getMessage() . "'); window.history.back();</script>";
}
?>