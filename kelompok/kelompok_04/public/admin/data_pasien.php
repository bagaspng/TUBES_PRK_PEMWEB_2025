<?php
// public/admin/data_pasien.php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama           = trim($_POST['nama_lengkap'] ?? '');
        $nik            = trim($_POST['nik'] ?? '');
        $username       = trim($_POST['username'] ?? '');
        $tanggal_lahir  = trim($_POST['tanggal_lahir'] ?? '');
        $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $password       = trim($_POST['password'] ?? '');

        if ($nama === '' || $nik === '' || $username === '' || $tanggal_lahir === '' || $jenis_kelamin === '' || $email === '' || $password === '') {
            set_flash('error', 'Semua field bertanda * wajib diisi.');
        } else {
            $conn->begin_transaction();
            try {
                
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sqlUser = "INSERT INTO users (username, password_hash, email, role, status, created_at, updated_at)
                            VALUES (?, ?, ?, 'pasien', 'active', NOW(), NOW())";
                $stmtUser = $conn->prepare($sqlUser);
                $stmtUser->bind_param('sss', $username, $hash, $email);
                $stmtUser->execute();
                $id_user = $stmtUser->insert_id;
                $stmtUser->close();

                // Auto-generate No. RM dengan format RM-YYYYMMDD-{id_user}
                $no_rm = 'RM-' . date('Ymd') . '-' . $id_user;

                $sqlPasien = "INSERT INTO pasien (id_user, no_rm, nik, nama_lengkap, tanggal_lahir, jenis_kelamin, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmtPasien = $conn->prepare($sqlPasien);
                $stmtPasien->bind_param('isssss', $id_user, $no_rm, $nik, $nama, $tanggal_lahir, $jenis_kelamin);
                $stmtPasien->execute();
                $stmtPasien->close();

                $conn->commit();
                set_flash('success', 'Pasien baru berhasil ditambahkan dengan No. RM: ' . $no_rm);
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }

        header('Location: data_pasien.php');
        exit;
    }

    if ($action === 'update') {
        $id_pasien      = (int)($_POST['id_pasien'] ?? 0);
        $nama           = trim($_POST['nama_lengkap'] ?? '');
        $nik            = trim($_POST['nik'] ?? '');
        $username       = trim($_POST['username'] ?? '');
        $tanggal_lahir  = trim($_POST['tanggal_lahir'] ?? '');
        $jenis_kelamin  = trim($_POST['jenis_kelamin'] ?? '');
        $email          = trim($_POST['email'] ?? '');

        if (!$id_pasien || $nama === '' || $nik === '' || $username === '' || $tanggal_lahir === '' || $jenis_kelamin === '' || $email === '') {
            set_flash('error', 'Data tidak lengkap untuk update pasien.');
        } else {
            $conn->begin_transaction();
            try {

                $sqlGetUser = "SELECT id_user FROM pasien WHERE id_pasien = ?";
                $stmtGetUser = $conn->prepare($sqlGetUser);
                $stmtGetUser->bind_param('i', $id_pasien);
                $stmtGetUser->execute();
                $resUser = $stmtGetUser->get_result();
                $rowUser = $resUser->fetch_assoc();
                $stmtGetUser->close();

                if ($rowUser) {
                    $id_user = (int)$rowUser['id_user'];

                    $sqlUser = "UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id_user = ?";
                    $stmtUser = $conn->prepare($sqlUser);
                    $stmtUser->bind_param('ssi', $username, $email, $id_user);
                    $stmtUser->execute();
                    $stmtUser->close();

                    $sqlPasien = "UPDATE pasien 
                                  SET nik = ?, nama_lengkap = ?, tanggal_lahir = ?, jenis_kelamin = ?, updated_at = NOW()
                                  WHERE id_pasien = ?";
                    $stmtPasien = $conn->prepare($sqlPasien);
                    $stmtPasien->bind_param('ssssi', $nik, $nama, $tanggal_lahir, $jenis_kelamin, $id_pasien);
                    $stmtPasien->execute();
                    $stmtPasien->close();

                    $conn->commit();
                    set_flash('success', 'Data pasien berhasil diperbarui.');
                } else {
                    $conn->rollback();
                    set_flash('error', 'Data pasien tidak ditemukan.');
                }
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }

        header('Location: data_pasien.php');
        exit;
    }

    if ($action === 'delete') {
        $id_pasien = (int)($_POST['id_pasien'] ?? 0);

        if ($id_pasien) {
            $conn->begin_transaction();
            try {

                $sqlGetUser = "SELECT id_user FROM pasien WHERE id_pasien = ?";
                $stmtGetUser = $conn->prepare($sqlGetUser);
                $stmtGetUser->bind_param('i', $id_pasien);
                $stmtGetUser->execute();
                $resUser = $stmtGetUser->get_result();
                $rowUser = $resUser->fetch_assoc();
                $stmtGetUser->close();

                if ($rowUser) {
                    $id_user = (int)$rowUser['id_user'];

                    $sqlCheck = "SELECT COUNT(*) as c FROM rekam_medis WHERE id_pasien = ?";
                    $stmtCheck = $conn->prepare($sqlCheck);
                    $stmtCheck->bind_param('i', $id_pasien);
                    $stmtCheck->execute();
                    $resCheck = $stmtCheck->get_result()->fetch_assoc();
                    $stmtCheck->close();

                    if (($resCheck['c'] ?? 0) > 0) {
                        set_flash('error', 'Pasien tidak dapat dihapus karena memiliki rekam medis.');
                    } else {

                        $sqlAntrian = "DELETE FROM antrian WHERE id_pasien = ?";
                        $stmtAntrian = $conn->prepare($sqlAntrian);
                        $stmtAntrian->bind_param('i', $id_pasien);
                        $stmtAntrian->execute();
                        $stmtAntrian->close();

                        $sqlPasien = "DELETE FROM pasien WHERE id_pasien = ?";
                        $stmtPasien = $conn->prepare($sqlPasien);
                        $stmtPasien->bind_param('i', $id_pasien);
                        $stmtPasien->execute();
                        $stmtPasien->close();

                        $sqlUser = "UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id_user = ?";
                        $stmtUser = $conn->prepare($sqlUser);
                        $stmtUser->bind_param('i', $id_user);
                        $stmtUser->execute();
                        $stmtUser->close();

                        $conn->commit();
                        set_flash('success', 'Pasien berhasil dihapus.');
                    }
                }
            } catch (Exception $e) {
                $conn->rollback();
                set_flash('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
        }

        header('Location: data_pasien.php');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$params = [];
$types  = '';

$whereSql = '';
if ($search !== '') {
    $whereSql = "WHERE p.nama_lengkap LIKE ? OR p.nik LIKE ? OR p.no_rm LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
    $types  = 'sss';
}

$sqlList = "
    SELECT 
        p.id_pasien,
        p.no_rm,
        p.nik,
        p.nama_lengkap,
        p.tanggal_lahir,
        p.jenis_kelamin,
        u.email,
        u.username,
        TIMESTAMPDIFF(YEAR, p.tanggal_lahir, CURDATE()) as usia
    FROM pasien p
    JOIN users u ON p.id_user = u.id_user
    $whereSql
    ORDER BY p.nama_lengkap ASC
";

$patients = [];

if ($whereSql === '') {
    $res = $conn->query($sqlList);
} else {
    $stmt = $conn->prepare($sqlList);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
}

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $patients[] = $row;
    }
    $res->free();
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

function formatTanggal($tanggal) {
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
    $parts = explode('-', $tanggal);
    return $parts[2] . ' ' . $bulan[(int)$parts[1]] . ' ' . $parts[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pasien - Admin Puskesmas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100 font-sans">

<div class="min-h-screen flex">
    <?php
        $active = 'pasien';
        include __DIR__ . '/sidebar.php';
    ?>

    <div class="flex-1 flex flex-col">
   
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Data Pasien</h1>
                <p class="text-xs text-gray-500">Daftar pasien terdaftar di Puskesmas</p>
            </div>
            <div class="text-xs text-gray-500">
                Login sebagai: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <main class="flex-1 px-4 md:px-8 py-6 max-w-7xl mx-auto w-full">

            <?php if ($msg = get_flash('success')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-green-50 text-green-700 text-sm border border-green-100">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>
            <?php if ($msg = get_flash('error')): ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="mb-6 flex flex-col md:flex-row items-center gap-4">
              <form method="get" class="flex-1 w-full">
                <input
                  type="text"
                  name="q"
                  placeholder="Cari pasien berdasarkan nama, NIK, atau No. RM..."
                  value="<?php echo htmlspecialchars($search); ?>"
                  class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                >
              </form>

              <div class="w-full md:w-auto">
                <button
                  id="btnAddPasien"
                  type="button"
                  class="px-4 py-3 bg-green-500 text-white rounded-xl text-sm hover:bg-green-600">
                  Tambah Pasien
                </button>
              </div>
            </div>

            <div id="pasienModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40">
              <div class="bg-white rounded-2xl w-full max-w-2xl p-6 mx-4 max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                  <h2 id="pasienModalTitle" class="text-base font-semibold text-gray-800">Tambah Pasien</h2>
                  <button id="btnClosePasienModal" type="button" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <form id="pasienForm" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <input type="hidden" name="action" id="pasienFormAction" value="create">
                  <input type="hidden" name="id_pasien" id="pasienFormId" value="">

                  <div class="md:col-span-2">
                    <label class="block text-sm text-gray-700 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_lengkap" id="field_nama" required
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                  </div>

                  <div>
                    <label class="block text-sm text-gray-700 mb-2">NIK <span class="text-red-500">*</span></label>
                    <input type="text" name="nik" id="field_nik" required maxlength="16"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                  </div>

                  <div>
                    <label class="block text-sm text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="field_username" required
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p class="mt-1 text-xs text-gray-400">Username untuk login pasien</p>
                  </div>

                  <div id="noRmDisplay" class="hidden">
                    <label class="block text-sm text-gray-700 mb-2">No. Rekam Medis</label>
                    <input type="text" id="field_norm" readonly
                           class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 cursor-not-allowed">
                    <p class="mt-1 text-xs text-gray-400">Otomatis dibuat sistem</p>
                  </div>

                  <div>
                    <label class="block text-sm text-gray-700 mb-2">Tanggal Lahir <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_lahir" id="field_tgl" required
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                  </div>

                  <div>
                    <label class="block text-sm text-gray-700 mb-2">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <select name="jenis_kelamin" id="field_jk" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                      <option value="">-- Pilih --</option>
                      <option value="L">Laki-laki</option>
                      <option value="P">Perempuan</option>
                    </select>
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-sm text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="field_email" required
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                  </div>

                  <div id="passwordField" class="md:col-span-2">
                    <label class="block text-sm text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="field_password"
                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p class="mt-1 text-xs text-gray-400">Password untuk login pasien</p>
                  </div>

                  <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                    <button type="button" id="btnCancelPasien" class="px-4 py-3 bg-gray-100 text-gray-700 rounded-xl text-sm">Batal</button>
                    <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded-xl text-sm">Simpan</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">No. RM</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Username</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Nama Lengkap</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">NIK</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Tanggal Lahir</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Usia</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Jenis Kelamin</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Email</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($patients)): ?>
                                <?php foreach ($patients as $p): ?>
                                    <tr class="hover:bg-gray-50 text-sm">
                                        <td class="px-6 py-4 text-gray-800 font-medium">
                                            <?php echo htmlspecialchars($p['no_rm']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">
                                            <?php echo htmlspecialchars($p['username']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-800">
                                            <?php echo htmlspecialchars($p['nama_lengkap']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo htmlspecialchars($p['nik']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo formatTanggal($p['tanggal_lahir']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo (int)$p['usia']; ?> tahun
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo htmlspecialchars($p['email']); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                    class="px-3 py-2 text-xs rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 edit-pasien-btn"
                                                    data-id="<?php echo (int)$p['id_pasien']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($p['nama_lengkap'], ENT_QUOTES); ?>"
                                                    data-nik="<?php echo htmlspecialchars($p['nik'], ENT_QUOTES); ?>"
                                                    data-norm="<?php echo htmlspecialchars($p['no_rm'], ENT_QUOTES); ?>"
                                                    data-username="<?php echo htmlspecialchars($p['username'], ENT_QUOTES); ?>"
                                                    data-tgl="<?php echo htmlspecialchars($p['tanggal_lahir'], ENT_QUOTES); ?>"
                                                    data-jk="<?php echo htmlspecialchars($p['jenis_kelamin'], ENT_QUOTES); ?>"
                                                    data-email="<?php echo htmlspecialchars($p['email'], ENT_QUOTES); ?>">
                                                    Edit
                                                </button>
                                                <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pasien ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_pasien" value="<?php echo (int)$p['id_pasien']; ?>">
                                                    <button type="submit" class="px-3 py-2 text-xs rounded-lg bg-red-50 text-red-600 hover:bg-red-100">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center text-gray-500 text-sm">
                                        Tidak ada data pasien.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-3 text-xs text-gray-500 border-t border-gray-100">
                    Menampilkan <?php echo count($patients); ?> pasien
                    <?php if ($search): ?>
                        (hasil pencarian: "<span class="font-semibold"><?php echo htmlspecialchars($search); ?></span>")
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
(function () {
  const modal = document.getElementById('pasienModal');
  const btnAdd = document.getElementById('btnAddPasien');
  const btnClose = document.getElementById('btnClosePasienModal');
  const btnCancel = document.getElementById('btnCancelPasien');
  const form = document.getElementById('pasienForm');
  const formAction = document.getElementById('pasienFormAction');
  const formId = document.getElementById('pasienFormId');
  const title = document.getElementById('pasienModalTitle');
  const passwordField = document.getElementById('passwordField');
  const inputPassword = document.getElementById('field_password');

  function openModal() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    window.scrollTo(0,0);
  }
  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  const noRmDisplay = document.getElementById('noRmDisplay');

  function clearForm() {
    form.reset();
    formAction.value = 'create';
    formId.value = '';
    title.textContent = 'Tambah Pasien';
    passwordField.style.display = 'block';
    inputPassword.required = true;
    noRmDisplay.classList.add('hidden');
    document.getElementById('field_username').parentElement.classList.remove('hidden');
  }

  if (btnAdd) {
    btnAdd.addEventListener('click', function () {
      clearForm();
      openModal();
    });
  }
  if (btnClose) btnClose.addEventListener('click', closeModal);
  if (btnCancel) btnCancel.addEventListener('click', closeModal);

  document.querySelectorAll('.edit-pasien-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = btn.getAttribute('data-id');
      document.getElementById('field_nama').value = btn.getAttribute('data-nama') || '';
      document.getElementById('field_nik').value = btn.getAttribute('data-nik') || '';
      document.getElementById('field_norm').value = btn.getAttribute('data-norm') || '';
      document.getElementById('field_username').value = btn.getAttribute('data-username') || '';
      document.getElementById('field_tgl').value = btn.getAttribute('data-tgl') || '';
      document.getElementById('field_jk').value = btn.getAttribute('data-jk') || '';
      document.getElementById('field_email').value = btn.getAttribute('data-email') || '';
      formAction.value = 'update';
      formId.value = id;
      title.textContent = 'Edit Pasien';
      passwordField.style.display = 'none';
      inputPassword.required = false;
      noRmDisplay.classList.remove('hidden');
      document.getElementById('field_username').parentElement.classList.remove('hidden');
      openModal();
    });
  });

  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
})();
</script>

</body>
</html>