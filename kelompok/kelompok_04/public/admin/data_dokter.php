<?php
// public/admin/data_dokter.php

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/helpers/icon_helper.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}
function get_flash($type) {
    if (!empty($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

$poliklinik = [];
$resPoli = $conn->query("SELECT id_poli, nama_poli FROM poli ORDER BY nama_poli ASC");
if ($resPoli) {
    while ($row = $resPoli->fetch_assoc()) {
        $poliklinik[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama        = trim($_POST['nama_dokter'] ?? '');
        $kode        = trim($_POST['kode_dokter'] ?? '');
        $spesialis   = trim($_POST['spesialis'] ?? '');
        $no_hp       = trim($_POST['no_hp'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $username    = trim($_POST['username'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $id_poli     = (int)($_POST['id_poli'] ?? 0);

        if ($nama === '' || $kode === '' || $spesialis === '' || $no_hp === '' || $email === '' || $username === '' || $password === '' || !$id_poli) {
            set_flash('error', 'Semua field bertanda * wajib diisi.');
        } else {
            $conn->begin_transaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sqlUser = "INSERT INTO users (username, password_hash, email, role, status, created_at, updated_at)
                            VALUES (?, ?, ?, 'dokter', 'active', NOW(), NOW())";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->bind_param('sss', $username, $hash, $email);
                $stmtUser->execute();
                $id_user = $stmtUser->insert_id;
                $stmtUser->close();

                $sqlDokter = "INSERT INTO dokter (id_user, id_poli, kode_dokter, nama_dokter, spesialis, no_hp, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmtDok = $conn->prepare($sqlDokter);
                $stmtDok->bind_param('iissss', $id_user, $id_poli, $kode, $nama, $spesialis, $no_hp);
                $stmtDok->execute();
                $stmtDok->close();

                $conn->commit();
                set_flash('success', 'Dokter baru berhasil ditambahkan. Username: ' . $username . ' | Password: ' . $password);
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }

        header('Location: data_dokter.php');
        exit;
    }

    if ($action === 'update') {
        $id_dokter   = (int)($_POST['id_dokter'] ?? 0);
        $nama        = trim($_POST['nama_dokter'] ?? '');
        $kode        = trim($_POST['kode_dokter'] ?? '');
        $spesialis   = trim($_POST['spesialis'] ?? '');
        $no_hp       = trim($_POST['no_hp'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $username    = trim($_POST['username'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $id_poli     = (int)($_POST['id_poli'] ?? 0);

        if (!$id_dokter || $nama === '' || $kode === '' || $spesialis === '' || $no_hp === '' || $email === '' || $username === '' || !$id_poli) {
            set_flash('error', 'Semua field bertanda * wajib diisi.');
        } else {
            $conn->begin_transaction();
            try {
                $sqlGet = "SELECT id_user FROM dokter WHERE id_dokter = ?";
                $stmtGet = $conn->prepare($sqlGet);
                $stmtGet->bind_param('i', $id_dokter);
                $stmtGet->execute();
                $resGet = $stmtGet->get_result();
                $rowGet = $resGet->fetch_assoc();
                $stmtGet->close();

                if ($rowGet) {
                    $id_user = (int)$rowGet['id_user'];

                    if ($password !== '') {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $sqlUser = "UPDATE users SET username = ?, password_hash = ?, email = ?, updated_at = NOW() WHERE id_user = ?";
                        $stmtUser = $conn->prepare($sqlUser);
                        $stmtUser->bind_param('sssi', $username, $hash, $email, $id_user);
                    } else {
                        $sqlUser = "UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id_user = ?";
                        $stmtUser = $conn->prepare($sqlUser);
                        $stmtUser->bind_param('ssi', $username, $email, $id_user);
                    }
                    $stmtUser->execute();
                    $stmtUser->close();

                    $sqlDok = "UPDATE dokter
                               SET id_poli = ?, kode_dokter = ?, nama_dokter = ?, spesialis = ?, no_hp = ?, updated_at = NOW()
                               WHERE id_dokter = ?";
                    $stmtDok = $conn->prepare($sqlDok);
                    $stmtDok->bind_param('issssi', $id_poli, $kode, $nama, $spesialis, $no_hp, $id_dokter);
                    $stmtDok->execute();
                    $stmtDok->close();

                    $conn->commit();
                    $msg = 'Data dokter berhasil diperbarui.';
                    if ($password !== '') {
                        $msg .= ' Password baru: ' . $password;
                    }
                    set_flash('success', $msg);
                } else {
                    $conn->rollback();
                    set_flash('error', 'Data dokter tidak ditemukan.');
                }
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }

        header('Location: data_dokter.php');
        exit;
    }

    if ($action === 'delete') {
        $id_dokter = (int)($_POST['id_dokter'] ?? 0);

        if ($id_dokter) {
            $conn->begin_transaction();
            try {
                $sqlGet = "SELECT id_user FROM dokter WHERE id_dokter = ?";
                $stmtGet = $conn->prepare($sqlGet);
                $stmtGet->bind_param('i', $id_dokter);
                $stmtGet->execute();
                $resGet = $stmtGet->get_result();
                $rowGet = $resGet->fetch_assoc();
                $stmtGet->close();

                if ($rowGet) {
                    $id_user = (int)$rowGet['id_user'];

                    $sqlJadwal = "DELETE FROM jadwal_praktik WHERE id_dokter = ?";
                    $stmtJ = $conn->prepare($sqlJadwal);
                    $stmtJ->bind_param('i', $id_dokter);
                    $stmtJ->execute();
                    $stmtJ->close();

                    $sqlDok = "DELETE FROM dokter WHERE id_dokter = ?";
                    $stmtD = $conn->prepare($sqlDok);
                    $stmtD->bind_param('i', $id_dokter);
                    $stmtD->execute();
                    $stmtD->close();

                    $sqlUser = "UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id_user = ?";
                    $stmtU = $conn->prepare($sqlUser);
                    $stmtU->bind_param('i', $id_user);
                    $stmtU->execute();
                    $stmtU->close();
                }

                $conn->commit();
                set_flash('success', 'Dokter berhasil dihapus.');
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }

        header('Location: data_dokter.php');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$searchSql = '';
$params = [];
$types = '';

if ($search !== '') {
    $searchSql = "WHERE d.nama_dokter LIKE ? OR d.spesialis LIKE ? OR p.nama_poli LIKE ? OR u.username LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}

$sqlList = "
    SELECT 
        d.id_dokter,
        d.id_poli,
        d.kode_dokter,
        d.nama_dokter,
        d.spesialis,
        d.no_hp,
        u.email,
        u.username,
        p.nama_poli,
        GROUP_CONCAT(
            CONCAT(j.hari, ' ', DATE_FORMAT(j.jam_mulai, '%H:%i'), '-', DATE_FORMAT(j.jam_selesai, '%H:%i'))
            SEPARATOR '; '
        ) AS jadwal
    FROM dokter d
    JOIN users u ON d.id_user = u.id_user
    JOIN poli p ON d.id_poli = p.id_poli
    LEFT JOIN jadwal_praktik j ON j.id_dokter = d.id_dokter
    $searchSql
    GROUP BY d.id_dokter, d.kode_dokter, d.nama_dokter, d.spesialis, d.no_hp, u.email, u.username, p.nama_poli
    ORDER BY d.nama_dokter ASC
";

$doctors = [];
if ($searchSql === '') {
    $result = $conn->query($sqlList);
} else {
    $stmt = $conn->prepare($sqlList);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    $result->free();
}
if (isset($stmt) && $stmt) $stmt->close();

$adminName = 'Admin';
if (isset($_SESSION['user_id'])) {
    $idUser = (int)$_SESSION['user_id'];
    $qAdmin = $conn->prepare("SELECT username FROM users WHERE id_user = ?");
    $qAdmin->bind_param('i', $idUser);
    $qAdmin->execute();
    $resA = $qAdmin->get_result()->fetch_assoc();
    if ($resA) $adminName = $resA['username'];
    $qAdmin->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Dokter - Admin Puskesmas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<div class="min-h-screen flex">
    <?php
        $active = 'dokter';
        include __DIR__ . '/sidebar.php';
    ?>

    <div class="flex-1 flex flex-col">
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Data Dokter</h1>
                <p class="text-xs text-gray-500">Kelola data dokter di Puskesmas</p>
            </div>
            <div class="text-xs text-gray-500">
                Login sebagai: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <main class="flex-1 px-4 md:px-8 py-6 max-w-7xl mx-auto w-full">

            <?php if ($msg = get_flash('success')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-100 flex items-center gap-2">
                    <?= render_icon('check', 'fa', 'text-base', 'Success') ?>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($msg = get_flash('error')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100 flex items-center gap-2">
                    <?= render_icon('warning', 'fa', 'text-base', 'Error') ?>
                    <span><?php echo htmlspecialchars($msg); ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-6 flex flex-col md:flex-row items-center gap-4">
              <form method="get" class="flex-1 w-full">
                <div class="relative">
                    <?= render_icon('search', 'fa', 'absolute left-4 top-1/2 -translate-y-1/2 text-gray-400', 'Search') ?>
                    <input
                      type="text"
                      name="q"
                      placeholder="Cari dokter berdasarkan nama, username, spesialisasi atau poli..."
                      value="<?php echo htmlspecialchars($search); ?>"
                      class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm"
                    >
                </div>
              </form>

              <div class="w-full md:w-auto">
                <button
                  id="btnAddDokter"
                  type="button"
                  class="w-full md:w-auto px-5 py-3 bg-green-500 text-white rounded-xl text-sm hover:bg-green-600 transition flex items-center justify-center gap-2 font-medium">
                  <?= render_icon('plus', 'fa', 'text-base', 'Tambah') ?>
                  <span>Tambah Dokter</span>
                </button>
              </div>
            </div>

            <?php include __DIR__ . '/modals/modal_dokter.php'; ?>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Nama Dokter</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">NIP / Kode</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Username Login</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Spesialisasi</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Poli</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Jadwal Praktik</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Kontak</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($doctors)): ?>
                                <?php foreach ($doctors as $doctor): ?>
                                    <tr class="hover:bg-gray-50 text-sm">
                                        <td class="px-6 py-4 text-gray-800 font-medium">
                                            <?php echo htmlspecialchars($doctor['nama_dokter']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo htmlspecialchars($doctor['kode_dokter']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-lg text-xs font-medium">
                                                <?php echo htmlspecialchars($doctor['username']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo htmlspecialchars($doctor['spesialis']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class=" text-purple-700 py-1 rounded-lg text-xs">
                                                <?php echo htmlspecialchars($doctor['nama_poli']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 text-xs">
                                            <?php echo htmlspecialchars($doctor['jadwal'] ?: '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 text-xs">
                                            <div class="flex items-center gap-1 mb-1">
                                                <?= render_icon('phone', 'fa', 'text-xs text-gray-400', 'Phone') ?>
                                                <span><?php echo htmlspecialchars($doctor['no_hp']); ?></span>
                                            </div>
                                            <div class="flex items-center gap-1 text-gray-500">
                                                <?= render_icon('envelope', 'fa', 'text-xs text-gray-400', 'Email') ?>
                                                <span><?php echo htmlspecialchars($doctor['email']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button"
                                                    class="px-3 py-2 text-xs rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition flex items-center gap-1 edit-btn"
                                                    data-id="<?php echo (int)$doctor['id_dokter']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($doctor['nama_dokter'], ENT_QUOTES); ?>"
                                                    data-kode="<?php echo htmlspecialchars($doctor['kode_dokter'], ENT_QUOTES); ?>"
                                                    data-spesialis="<?php echo htmlspecialchars($doctor['spesialis'], ENT_QUOTES); ?>"
                                                    data-email="<?php echo htmlspecialchars($doctor['email'], ENT_QUOTES); ?>"
                                                    data-nohp="<?php echo htmlspecialchars($doctor['no_hp'], ENT_QUOTES); ?>"
                                                    data-username="<?php echo htmlspecialchars($doctor['username'], ENT_QUOTES); ?>"
                                                    data-id-poli="<?php echo (int)($doctor['id_poli'] ?? 0); ?>">
                                                    <?= render_icon('edit', 'fa', 'text-xs', 'Edit') ?>
                                                    <span>Edit</span>
                                                </button>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus dokter ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_dokter" value="<?php echo (int)$doctor['id_dokter']; ?>">
                                                    <button type="submit"
                                                            class="px-3 py-2 text-xs rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition flex items-center gap-1">
                                                        <?= render_icon('trash', 'fa', 'text-xs', 'Hapus') ?>
                                                        <span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500 text-sm">
                                        <div class="flex flex-col items-center gap-3">
                                            <?= render_icon('user-doctor', 'fa', 'text-5xl text-gray-300', 'No Data') ?>
                                            <p class="font-medium">Tidak ada data dokter</p>
                                            <?php if ($search): ?>
                                                <p class="text-xs">Hasil pencarian untuk "<span class="font-semibold"><?php echo htmlspecialchars($search); ?></span>" tidak ditemukan</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 text-sm text-gray-600 border-t border-gray-100 flex items-center gap-2">
                    <?= render_icon('user-doctor', 'fa', 'text-base text-gray-400', 'Total') ?>
                    <span>
                        Menampilkan <span class="font-semibold"><?php echo count($doctors); ?></span> dokter
                        <?php if ($search): ?>
                            (hasil pencarian untuk: <span class="font-semibold">"<?php echo htmlspecialchars($search); ?>"</span>)
                        <?php endif; ?>
                    </span>
                </div>
            </div>

        </main>
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Loaded - Initializing modal handlers');
    
    const modal = document.getElementById('dokterModal');
    const btnAdd = document.getElementById('btnAddDokter');
    const btnClose = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancelForm');
    const form = document.getElementById('dokterForm');
    const formAction = document.getElementById('formAction');
    const formId = document.getElementById('formId');
    const modalTitle = document.getElementById('modalTitle');
    const passwordField = document.getElementById('field_password');
    const passwordRequired = document.getElementById('passwordRequired');
    const passwordHint = document.getElementById('passwordHint');

    function openModal() {
        console.log('Opening modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        console.log('Closing modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function clearForm() {
        form.reset();
        formAction.value = 'create';
        formId.value = '';
        modalTitle.textContent = 'Tambah Dokter';
        passwordField.required = true;
        if (passwordRequired) passwordRequired.style.display = 'inline';
        if (passwordHint) passwordHint.textContent = 'Password untuk login dokter';
    }

    if (btnAdd) {
        btnAdd.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add button clicked');
            clearForm();
            openModal();
        });
    }

    if (btnClose) {
        btnClose.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('modal') === 'create') {
        clearForm();
        openModal();
    }

    const editButtons = document.querySelectorAll('.edit-btn');
    console.log('Found ' + editButtons.length + ' edit buttons');
    
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Edit button clicked');
            
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama') || '';
            const kode = btn.getAttribute('data-kode') || '';
            const spesialis = btn.getAttribute('data-spesialis') || '';
            const email = btn.getAttribute('data-email') || '';
            const nohp = btn.getAttribute('data-nohp') || '';
            const username = btn.getAttribute('data-username') || '';
            const idPoli = btn.getAttribute('data-id-poli') || '';
            
            console.log('Editing doctor:', { id, nama, kode });
            
            document.getElementById('field_nama').value = nama;
            document.getElementById('field_kode').value = kode;
            document.getElementById('field_spesialis').value = spesialis;
            document.getElementById('field_email').value = email;
            document.getElementById('field_nohp').value = nohp;
            document.getElementById('field_username').value = username;
            document.getElementById('field_poli').value = idPoli;
            document.getElementById('field_password').value = '';
            
            formAction.value = 'update';
            formId.value = id;
            modalTitle.textContent = 'Edit Dokter';
            passwordField.required = false;
            
            if (passwordRequired) passwordRequired.style.display = 'none';
            if (passwordHint) passwordHint.textContent = 'Kosongkan jika tidak ingin mengubah password';
            
            openModal();
        });
    });

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
});
</script>

</body>
</html>