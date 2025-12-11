<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pasien') {
    header('Location: ../login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Pengumuman tidak ditemukan.");
}

$sql = "SELECT * FROM pengumuman WHERE id_pengumuman = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$artikel = $res->fetch_assoc();
$stmt->close();

if (!$artikel) {
    die("Pengumuman tidak ditemukan.");
}

function getImage($artikel) {
    if (!empty($artikel['gambar'])) {
        return '../../' . $artikel['gambar'];
    }
    return 'https://images.unsplash.com/photo-1580281658627-7665a298f61a?q=80&w=1200';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($artikel['judul']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="px-6 py-4 flex items-center gap-4 max-w-2xl mx-auto">
            <a href="pengumuman.php"
               class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 512 512">
                    <path d="M328 112 184 256l144 144" fill="none" stroke="currentColor" stroke-width="48"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

            <h1 class="text-lg font-semibold text-gray-800">Detail Pengumuman</h1>
        </div>
    </div>

    <div class="max-w-2xl mx-auto pb-10">

        <div class="w-full h-56 sm:h-72 overflow-hidden rounded-none">
            <img src="<?php echo getImage($artikel); ?>"
                 class="w-full h-full object-cover">
        </div>

        <div class="px-6 py-6 space-y-4">

            <p class="text-sm font-medium text-green-600">
                <?php
                    $words = explode(" ", $artikel['judul']);
                    echo htmlspecialchars($words[0] . " " . ($words[1] ?? "Info"));
                ?>
            </p>

            <p class="text-xs text-gray-500 mb-1">
                <?php echo date("d M Y", strtotime($artikel['tanggal'])); ?>
            </p>

            <h2 class="text-2xl font-bold text-gray-900 leading-snug">
                <?php echo htmlspecialchars($artikel['judul']); ?>
            </h2>

            <div class="text-gray-700 text-sm leading-relaxed prose max-w-none">
                <?php echo nl2br($artikel['isi']); ?>
            </div>

        </div>

    </div>

</body>
</html>
