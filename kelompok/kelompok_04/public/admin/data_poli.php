<?php
// public/admin/data_poli.php

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

    // Tambah poli
    if ($action === 'create') {
        $nama        = trim($_POST['nama_poli'] ?? '');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $created_at  = date('Y-m-d H:i:s');
        $updated_at  = $created_at;

        if ($nama === '') {
            set_flash('error', 'Nama poli wajib diisi.');
        } else {
            $sql = "INSERT INTO poli (nama_poli, deskripsi, created_at, updated_at)
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssss', $nama, $deskripsi, $created_at, $updated_at);
            if ($stmt->execute()) {
                set_flash('success', 'Poli baru berhasil ditambahkan.');
            } else {
                set_flash('error', 'Gagal menambahkan poli.');
            }
            $stmt->close();
        }

        header('Location: data_poli.php');
        exit;
    }

    if ($action === 'update') {
        $id_poli     = (int)($_POST['id_poli'] ?? 0);
        $nama        = trim($_POST['nama_poli'] ?? '');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $updated_at  = date('Y-m-d H:i:s');

        if (!$id_poli || $nama === '') {
            set_flash('error', 'Data tidak lengkap untuk update poli.');
        } else {
            $sql = "UPDATE poli 
                    SET nama_poli = ?, deskripsi = ?, updated_at = ?
                    WHERE id_poli = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sssi', $nama, $deskripsi, $updated_at, $id_poli);
            if ($stmt->execute()) {
                set_flash('success', 'Data poli berhasil diperbarui.');
            } else {
                set_flash('error', 'Gagal memperbarui data poli.');
            }
            $stmt->close();
        }

        header('Location: data_poli.php');
        exit;
    }

    if ($action === 'delete') {
        $id_poli = (int)($_POST['id_poli'] ?? 0);

        if ($id_poli) {

            $sqlCheck1 = "SELECT COUNT(*) AS c FROM dokter WHERE id_poli = ?";
            $stmt1 = $conn->prepare($sqlCheck1);
            $stmt1->bind_param('i', $id_poli);
            $stmt1->execute();
            $res1 = $stmt1->get_result()->fetch_assoc();
            $stmt1->close();

            $sqlCheck2 = "SELECT COUNT(*) AS c FROM jadwal_praktik WHERE id_poli = ?";
            $stmt2 = $conn->prepare($sqlCheck2);
            $stmt2->bind_param('i', $id_poli);
            $stmt2->execute();
            $res2 = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if (($res1['c'] ?? 0) > 0 || ($res2['c'] ?? 0) > 0) {
                set_flash('error', 'Poli tidak dapat dihapus karena masih digunakan oleh dokter atau jadwal praktik.');
            } else {
                $sqlDel = "DELETE FROM poli WHERE id_poli = ?";
                $stmtDel = $conn->prepare($sqlDel);
                $stmtDel->bind_param('i', $id_poli);
                if ($stmtDel->execute()) {
                    set_flash('success', 'Poli berhasil dihapus.');
                } else {
                    set_flash('error', 'Gagal menghapus poli.');
                }
                $stmtDel->close();
            }
        }

        header('Location: data_poli.php');
        exit;
    }
}

$search = trim($_GET['q'] ?? '');
$params = [];
$types  = '';

$whereSql = '';
if ($search !== '') {
    $whereSql = "WHERE p.nama_poli LIKE ? OR p.deskripsi LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like];
    $types  = 'ss';
}

$sqlList = "
    SELECT 
        p.id_poli,
        p.nama_poli,
        p.deskripsi,
        COALESCE(
            CONCAT(
                DATE_FORMAT(MIN(j.jam_mulai), '%H:%i'),
                ' - ',
                DATE_FORMAT(MAX(j.jam_selesai), '%H:%i')
            ),
            ''
        ) AS jam_operasional
    FROM poli p
    LEFT JOIN jadwal_praktik j ON j.id_poli = p.id_poli
    $whereSql
    GROUP BY p.id_poli, p.nama_poli, p.deskripsi
    ORDER BY p.nama_poli ASC
";

$polis = [];

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
        $polis[] = $row;
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Poli - Admin Puskesmas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" />
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100 font-sans">

<div class="min-h-screen flex">
    <?php
        $active = 'poli';
        include __DIR__ . '/sidebar.php';
    ?>

    <div class="flex-1 flex flex-col">
        <header class="w-full px-4 md:px-8 py-4 bg-white border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">Data Poli</h1>
                <p class="text-xs text-gray-500">Kelola data poli di Puskesmas</p>
            </div>
            <div class="text-xs text-gray-500">
                Login sebagai: <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <main class="flex-1 px-4 md:px-8 py-6 max-w-7xl mx-auto">

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
                  placeholder="Cari poli..."
                  value="<?php echo htmlspecialchars($search); ?>"
                  class="w-full pl-4 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                >
              </form>

              <div class="w-full md:w-auto">
                <button
                  id="btnAddPoli"
                  type="button"
                  class="w-full md:w-auto px-4 py-3 bg-green-500 text-white rounded-xl text-sm hover:bg-green-600 transition-colors">
                  <?php echo render_icon('plus', 'fa', 'mr-2'); ?>
                  Tambah Poli
                </button>
              </div>
            </div>

            <!-- Modal Poli -->
            <div id="poliModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40">
              <div class="bg-white rounded-2xl w-full max-w-2xl p-6 mx-4">
                <div class="flex items-center justify-between mb-4">
                  <h2 id="poliModalTitle" class="text-base font-semibold text-gray-800">Tambah Poli</h2>
                  <button id="btnClosePoliModal" type="button" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                </div>

                <form id="poliForm" method="post" class="space-y-4">
                  <input type="hidden" name="action" id="poliFormAction" value="create">
                  <input type="hidden" name="id_poli" id="poliFormId" value="">

                  <div>
                    <label class="block text-sm text-gray-700 mb-2">
                        Nama Poli <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="nama_poli"
                        id="field_poli_nama"
                        value=""
                        placeholder="Contoh: Poli Umum"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"
                        required
                    >
                  </div>

                  <div>
                    <label class="block text-sm text-gray-700 mb-2">
                        Deskripsi
                    </label>
                    <textarea
                        name="deskripsi"
                        id="field_poli_deskripsi"
                        rows="3"
                        placeholder="Deskripsi singkat tentang poli"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 text-sm resize-none"
                    ></textarea>
                  </div>

                  <div class="flex justify-end gap-3 pt-2">
                    <button type="button" id="btnCancelPoli" class="px-4 py-3 bg-gray-100 text-gray-700 rounded-xl text-sm hover:bg-gray-200 transition-colors">Batal</button>
                    <button type="submit" class="px-6 py-3 bg-green-500 text-white rounded-xl text-sm hover:bg-green-600 transition-colors">Simpan</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-100"></div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Nama Poli</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Deskripsi</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Jam Operasional</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Status</th>
                                <th class="px-6 py-4 text-left text-xs text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($polis)): ?>
                                <?php foreach ($polis as $p): ?>
                                    <tr class="hover:bg-gray-50 text-sm">
                                        <td class="px-6 py-4 text-gray-800">
                                            <?php echo render_icon('building', 'fa', 'mr-2 text-green-500'); ?>
                                            <?php echo htmlspecialchars($p['nama_poli']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php echo htmlspecialchars($p['deskripsi']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <?php if ($p['jam_operasional']): ?>
                                                <?php echo render_icon('clock', 'fa', 'mr-1 text-gray-400'); ?>
                                                <?php echo htmlspecialchars($p['jam_operasional']); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs">
                                                <?php echo render_icon('check', 'fa', 'mr-1'); ?>
                                                Aktif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                    class="inline-flex items-center px-3 py-2 text-xs rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 edit-poli-btn"
                                                    data-id="<?php echo (int)$p['id_poli']; ?>"
                                                    data-nama="<?php echo htmlspecialchars($p['nama_poli'], ENT_QUOTES); ?>"
                                                    data-deskripsi="<?php echo htmlspecialchars($p['deskripsi'] ?? '', ENT_QUOTES); ?>">
                                                    <?php echo render_icon('edit', 'fa', 'mr-1'); ?>
                                                    Edit
                                                </button>
                                                <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus poli ini?');" class="inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_poli" value="<?php echo (int)$p['id_poli']; ?>">
                                                    <button type="submit" class="inline-flex items-center px-3 py-2 text-xs rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                                        <?php echo render_icon('trash', 'fa', 'mr-1'); ?>
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 text-sm">
                                        Tidak ada data poli.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-3 text-xs text-gray-500 border-top border-gray-100">
                    Menampilkan <?php echo count($polis); ?> poli
                    <?php if ($search): ?>
                        (hasil pencarian: "<span class="font-semibold"><?php echo htmlspecialchars($search); ?></span>")
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing poli modal...');
    
    const modal = document.getElementById('poliModal');
    const btnAdd = document.getElementById('btnAddPoli');
    const btnClose = document.getElementById('btnClosePoliModal');
    const btnCancel = document.getElementById('btnCancelPoli');
    const form = document.getElementById('poliForm');
    const formAction = document.getElementById('poliFormAction');
    const formId = document.getElementById('poliFormId');
    const title = document.getElementById('poliModalTitle');
    const fieldNama = document.getElementById('field_poli_nama');
    const fieldDeskripsi = document.getElementById('field_poli_deskripsi');

    function openModal() {
        console.log('Opening modal...');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.scrollTo(0,0);
    }
    
    function closeModal() {
        console.log('Closing modal...');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function clearForm() {
        console.log('Clearing form...');
        form.reset();
        formAction.value = 'create';
        formId.value = '';
        title.textContent = 'Tambah Poli';
    }

    // Event listener untuk tombol tambah
    if (btnAdd) {
        btnAdd.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add button clicked');
            clearForm();
            openModal();
        });
    }

    // Event listener untuk tombol close
    if (btnClose) {
        btnClose.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    // Event listener untuk tombol cancel
    if (btnCancel) {
        btnCancel.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal();
        });
    }

    // Event listener untuk tombol edit
    const editButtons = document.querySelectorAll('.edit-poli-btn');
    console.log('Found edit buttons:', editButtons.length);
    
    editButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Edit button clicked');
            
            const id = btn.getAttribute('data-id');
            const nama = btn.getAttribute('data-nama') || '';
            const deskripsi = btn.getAttribute('data-deskripsi') || '';
            
            console.log('Edit data:', {id, nama, deskripsi});
            
            fieldNama.value = nama;
            fieldDeskripsi.value = deskripsi;
            formAction.value = 'update';
            formId.value = id;
            title.textContent = 'Edit Poli';
            openModal();
        });
    });

    // Close modal jika klik di luar
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Check URL untuk modal create
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('modal') === 'create') {
        clearForm();
        openModal();
    }
});
</script>

</body>
</html>