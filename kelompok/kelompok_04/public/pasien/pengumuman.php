<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'pasien') {
    header('Location: ../login.php');
    exit;
}

$sql = "SELECT id_pengumuman, judul, isi, tanggal
        FROM pengumuman
        WHERE status = 'publish'
        ORDER BY tanggal DESC";
$res = $conn->query($sql);

$artikel = [];
while ($row = $res->fetch_assoc()) {
    $row['ringkasan'] = mb_strimwidth(strip_tags($row['isi']), 0, 130, '...');
    $artikel[] = $row;
}

function getImage($id) {
    $images = [
        "https://images.unsplash.com/photo-1580281658627-7665a298f61a?q=80&w=1200",
        "https://images.unsplash.com/photo-1625134673337-519d4d10b313?q=80&w=1200",
        "https://images.unsplash.com/photo-1580281658627-7665a298f61a?q=80&w=1200",
        "https://images.unsplash.com/photo-1505751172876-fa1923c5c528?q=80&w=1200",
        "https://images.unsplash.com/photo-1514996937319-344454492b37?q=80&w=1200",
        "https://images.unsplash.com/photo-1557683316-973673baf926?q=80&w=1200",
    ];
    return $images[$id % count($images)];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Artikel Kesehatan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="px-6 py-4 flex items-center gap-4 max-w-2xl mx-auto">
            <a href="dashboard.php"
               class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-700" viewBox="0 0 512 512">
                    <path d="M328 112 184 256l144 144" fill="none" stroke="currentColor" stroke-width="48"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

            <h1 class="text-lg font-semibold text-gray-800">Artikel Kesehatan</h1>
        </div>
    </div>

    <div class="px-6 py-6 max-w-2xl mx-auto space-y-6">

        <?php if (!$artikel): ?>
            <p class="text-gray-500 text-center">Belum ada artikel.</p>
        <?php endif; ?>

        <?php foreach ($artikel as $idx => $a): ?>
            <a href="pengumuman_detail.php?id=<?php echo $a['id_pengumuman']; ?>"
               class="block bg-white rounded-2xl shadow-sm hover:shadow-lg transition overflow-hidden border border-gray-200">

                <div class="h-40 w-full overflow-hidden">
                    <img src="<?php echo getImage($idx); ?>"
                         class="w-full h-full object-cover" alt="Artikel"/>
                </div>

                <div class="p-4 space-y-2">

                    <p class="text-xs font-medium text-green-600">
                        <?php
                        $words = explode(" ", $a['judul']);
                        echo htmlspecialchars($words[0] . " " . ($words[1] ?? 'Info'));
                        ?>
                    </p>

                    <p class="text-xs text-gray-500">
                        <?php echo date("d M Y", strtotime($a['tanggal'])); ?>
                    </p>

                    <p class="text-base font-semibold text-gray-800 leading-tight">
                        <?php echo htmlspecialchars($a['judul']); ?>
                    </p>

                    <p class="text-sm text-gray-600 leading-snug">
                        <?php echo htmlspecialchars($a['ringkasan']); ?>
                    </p>

                    <p class="text-sm text-green-700 font-medium flex items-center gap-1">
                        Baca Selengkapnya
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 512 512">
                            <path d="M184 112l144 144-144 144" fill="none" stroke="currentColor"
                                  stroke-width="48" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </p>
                </div>

            </a>
        <?php endforeach; ?>

    </div>

</body>
</html>