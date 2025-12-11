<?php
session_start();
require_once '../../src/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $no_hp = $_POST['no_hp'];

    $stmt_u = $conn->prepare("UPDATE users SET email = ? WHERE id_user = ?");
    $stmt_u->bind_param("si", $email, $id_user);
    
    $stmt_d = $conn->prepare("UPDATE dokter SET no_hp = ? WHERE id_user = ?");
    $stmt_d->bind_param("si", $no_hp, $id_user);

    if ($stmt_u->execute() && $stmt_d->execute()) {
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='profil_dokter.php';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal memperbarui profil.'); window.history.back();</script>";
        exit;
    }
}

$query = "SELECT d.*, u.email FROM dokter d JOIN users u ON d.id_user = u.id_user WHERE u.id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$dokter = $stmt->get_result()->fetch_assoc();

if (!$dokter) {
    die("<div class='p-10 text-red-500 font-bold text-center'>Error: Data dokter tidak ditemukan. Pastikan akun ID: $id_user sudah terhubung ke tabel dokter.</div>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Dokter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

<div class="flex h-screen overflow-hidden">
    
    <?php require_once 'sidebar.php'; ?>

    <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden bg-slate-50">
        <main class="w-full grow p-8 max-w-5xl mx-auto">
            
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-800">Profil Dokter</h2>
                <p class="text-slate-500 text-sm mt-1">Informasi profil dan data pribadi</p>
            </div>

            <div class="bg-emerald-500 rounded-2xl p-8 mb-6 shadow-md flex items-center gap-6">
                <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center border-4 border-white/10 shrink-0">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div class="text-white">
                    <h3 class="text-2xl font-bold mb-1"><?= htmlspecialchars($dokter['nama_dokter']) ?></h3>
                    <p class="text-emerald-50 font-medium text-lg"><?= htmlspecialchars($dokter['spesialis']) ?></p>
                    <p class="text-emerald-100 text-sm mt-2 opacity-80 uppercase tracking-wide">NIP: <?= htmlspecialchars($dokter['kode_dokter']) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Informasi Kontak</h3>

                <form id="profileForm" method="POST">
                    <div class="space-y-8">
                        
                        <div class="flex items-start gap-5">
                            <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 shrink-0 border border-slate-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="flex-1 border-b border-slate-100 pb-2">
                                <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Email</label>
                                <input type="email" name="email" id="inputEmail" required 
                                       value="<?= htmlspecialchars($dokter['email']) ?>" readonly 
                                       class="w-full text-slate-800 font-medium text-lg bg-transparent border-none p-0 focus:ring-0 focus:border-b-2 focus:border-emerald-500 transition-all outline-none" 
                                       placeholder="Email dokter">
                            </div>
                        </div>

                        <div class="flex items-start gap-5">
                            <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 shrink-0 border border-slate-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            </div>
                            <div class="flex-1 border-b border-slate-100 pb-2">
                                <label class="block text-xs text-slate-400 font-bold uppercase mb-1">Nomor Telepon</label>
                                <input type="text" name="no_hp" id="inputHp" required 
                                       value="<?= htmlspecialchars($dokter['no_hp']) ?>" readonly 
                                       class="w-full text-slate-800 font-medium text-lg bg-transparent border-none p-0 focus:ring-0 focus:border-b-2 focus:border-emerald-500 transition-all outline-none" 
                                       placeholder="Nomor HP">
                            </div>
                        </div>

                    </div>

                    <div class="mt-10">
                        <button type="button" id="btnEdit" onclick="enableEdit()" 
                                class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            Edit Profil
                        </button>

                        <button type="submit" id="btnSave" class="hidden w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition shadow-md flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>
</div>

<script>
function enableEdit() {
    document.getElementById('btnEdit').classList.add('hidden');
    document.getElementById('btnSave').classList.remove('hidden');
    document.getElementById('btnSave').classList.add('flex');

    const email = document.getElementById('inputEmail');
    email.removeAttribute('readonly');
    email.focus();
    email.classList.add('border-emerald-500', 'bg-slate-50', 'px-2');

    const hp = document.getElementById('inputHp');
    hp.removeAttribute('readonly');
    hp.classList.add('border-emerald-500', 'bg-slate-50', 'px-2');
}
</script>

</body>
</html>