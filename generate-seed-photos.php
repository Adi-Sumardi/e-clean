<?php

/**
 * Generator foto placeholder realistis untuk seed data presentasi.
 * Menggunakan GD library untuk membuat gambar berlabel per domain & lokasi.
 */

$basePath = __DIR__ . '/storage/app/public';

$dirs = [
    "$basePath/activity-reports/before",
    "$basePath/activity-reports/after",
    "$basePath/laporan-satpam",
    "$basePath/laporan-ob/before",
    "$basePath/laporan-ob/after",
    "$basePath/laporan-toko",
    "$basePath/guest-complaints",
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// ─── Palette & config ────────────────────────────────────────────────────────

$W = 800; $H = 600;

$themes = [
    'kebersihan' => [
        'bg'        => [240, 248, 255],   // alice blue
        'accent'    => [34, 139, 34],     // forest green
        'accent2'   => [144, 238, 144],
        'icon'      => '🧹',
        'label'     => 'KEBERSIHAN',
    ],
    'satpam' => [
        'bg'        => [240, 240, 255],   // lavender
        'accent'    => [25, 25, 112],     // midnight blue
        'accent2'   => [100, 149, 237],
        'icon'      => '🛡️',
        'label'     => 'KEAMANAN',
    ],
    'ob' => [
        'bg'        => [255, 250, 240],   // floral white
        'accent'    => [210, 105, 30],    // chocolate
        'accent2'   => [255, 200, 100],
        'icon'      => '🧴',
        'label'     => 'OFFICE BOY',
    ],
    'toko' => [
        'bg'        => [255, 240, 255],   // lavender blush
        'accent'    => [128, 0, 128],     // purple
        'accent2'   => [221, 160, 221],
        'icon'      => '🛒',
        'label'     => 'TOKO',
    ],
    'complaint' => [
        'bg'        => [255, 245, 238],   // seashell
        'accent'    => [220, 20, 60],     // crimson
        'accent2'   => [255, 160, 160],
        'icon'      => '📸',
        'label'     => 'DOKUMENTASI',
    ],
];

// ─── Core image generator ─────────────────────────────────────────────────────

function generatePhoto(string $domain, string $lokasi, string $status, string $detail, int $W, int $H, array $themes): \GdImage
{
    $t = $themes[$domain];
    $img = imagecreatetruecolor($W, $H);

    // Background gradient (top to bottom)
    $bg  = $t['bg'];
    $acc = $t['accent'];
    $a2  = $t['accent2'];

    for ($y = 0; $y < $H; $y++) {
        $r = (int)($bg[0] + ($y / $H) * 20);
        $g = (int)($bg[1] + ($y / $H) * 10);
        $b = (int)($bg[2] - ($y / $H) * 15);
        $c = imagecolorallocate($img, min(255,$r), min(255,$g), min(255,$b));
        imageline($img, 0, $y, $W, $y, $c);
    }

    // Tile/floor pattern (bottom half)
    $tileColor = imagecolorallocatealpha($img, $acc[0], $acc[1], $acc[2], 110);
    $tileSize  = 60;
    for ($x = 0; $x < $W; $x += $tileSize) {
        for ($y = $H / 2; $y < $H; $y += $tileSize) {
            imagerectangle($img, $x + 2, (int)$y + 2, $x + $tileSize - 2, (int)$y + $tileSize - 2, $tileColor);
        }
    }

    // Wall accent bar (top quarter)
    $wallColor = imagecolorallocatealpha($img, $a2[0], $a2[1], $a2[2], 80);
    imagefilledrectangle($img, 0, 0, $W, $H / 4, $wallColor);

    // Status badge (top-left)
    $isBefore  = $status === 'sebelum';
    $badgeBg   = $isBefore
        ? imagecolorallocate($img, 220, 38, 38)
        : imagecolorallocate($img, 22, 163, 74);
    $badgeText = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, 20, 20, 200, 64, $badgeBg);
    imagestring($img, 5, 35, 36, $status === 'sebelum' ? '◉ SEBELUM' : '✓ SESUDAH', $badgeText);

    // Domain badge (top-right)
    $domainBg = imagecolorallocate($img, $acc[0], $acc[1], $acc[2]);
    $domainTxt= imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, $W - 220, 20, $W - 20, 64, $domainBg);
    imagestring($img, 4, $W - 215, 36, $t['label'], $domainTxt);

    // Center illustration block
    $blockColor = imagecolorallocate($img, $a2[0], $a2[1], $a2[2]);
    $blockW = 320; $blockH = 180;
    $bx = ($W - $blockW) / 2;
    $by = ($H / 2 - $blockH) / 2 + 40;
    imagefilledrectangle($img, (int)$bx, (int)$by, (int)$bx + $blockW, (int)$by + $blockH, $blockColor);

    // Draw domain-specific "furniture" shapes
    $shapeColor = imagecolorallocate($img, $acc[0], $acc[1], $acc[2]);
    $cx = $W / 2; $cy = ($H / 2 - 40) / 2 + 40;

    switch ($domain) {
        case 'kebersihan':
            // Mop stick
            imageline($img, (int)($cx - 80), (int)($by + 20), (int)($cx + 80), (int)($by + $blockH - 20), $shapeColor);
            imagefilledellipse($img, (int)($cx - 80), (int)($by + 20), 30, 30, $shapeColor);
            // Bucket
            imagefilledrectangle($img, (int)($cx + 50), (int)($by + $blockH - 60), (int)($cx + 100), (int)($by + $blockH - 10), $shapeColor);
            break;
        case 'satpam':
            // Gate/barrier
            imagefilledrectangle($img, (int)($cx - 120), (int)($by + 40), (int)($cx + 120), (int)($by + 60), $shapeColor);
            imagefilledrectangle($img, (int)($cx - 10), (int)($by + 60), (int)($cx + 10), (int)($by + $blockH - 10), $shapeColor);
            // CCTV
            imagefilledellipse($img, (int)($cx - 100), (int)($by + 30), 40, 30, $shapeColor);
            break;
        case 'ob':
            // Table rectangle
            imagefilledrectangle($img, (int)($cx - 120), (int)($by + 60), (int)($cx + 120), (int)($by + 80), $shapeColor);
            // Chairs
            imagefilledrectangle($img, (int)($cx - 100), (int)($by + 80), (int)($cx - 70), (int)($by + $blockH - 10), $shapeColor);
            imagefilledrectangle($img, (int)($cx + 70), (int)($by + 80), (int)($cx + 100), (int)($by + $blockH - 10), $shapeColor);
            // Cup
            imagefilledellipse($img, (int)$cx, (int)($by + 50), 30, 20, $shapeColor);
            break;
        case 'toko':
            // Shelves
            for ($si = 0; $si < 3; $si++) {
                imagefilledrectangle($img, (int)($cx - 110), (int)($by + 30 + $si * 45), (int)($cx + 110), (int)($by + 45 + $si * 45), $shapeColor);
                // Items on shelf
                for ($ii = 0; $ii < 5; $ii++) {
                    imagefilledrectangle($img, (int)($cx - 100 + $ii * 44), (int)($by + 10 + $si * 45), (int)($cx - 68 + $ii * 44), (int)($by + 30 + $si * 45), $blockColor);
                }
            }
            break;
        case 'complaint':
            // Warning triangle
            $pts = [(int)$cx, (int)($by+20), (int)($cx-80), (int)($by+$blockH-20), (int)($cx+80), (int)($by+$blockH-20)];
            imagefilledpolygon($img, $pts, $shapeColor);
            $innerColor = imagecolorallocate($img, 255, 255, 255);
            imagestring($img, 5, (int)($cx-8), (int)($by+$blockH/2-12), '!', $innerColor);
            break;
    }

    // Lokasi label (center bottom of illustration)
    $txtColor = imagecolorallocate($img, $acc[0], $acc[1], $acc[2]);
    $centered = (int)(($W - strlen($lokasi) * imagefontwidth(4)) / 2);
    imagestring($img, 4, $centered, (int)($H / 2 + 10), $lokasi, $txtColor);

    // Detail text
    $detailColor = imagecolorallocate($img, 80, 80, 80);
    $dcentered = (int)(($W - strlen($detail) * imagefontwidth(3)) / 2);
    imagestring($img, 3, $dcentered, (int)($H / 2 + 40), $detail, $detailColor);

    // Timestamp
    $tsColor = imagecolorallocate($img, 150, 150, 150);
    $ts = date('d/m/Y H:i') . ' WIB';
    imagestring($img, 2, $W - 170, $H - 30, $ts, $tsColor);

    // Watermark
    $wmColor = imagecolorallocatealpha($img, $acc[0], $acc[1], $acc[2], 100);
    imagestring($img, 2, 20, $H - 30, 'E-CLEAN System © Kopkar YAPI', $wmColor);

    // Bottom border accent
    $borderColor = imagecolorallocate($img, $acc[0], $acc[1], $acc[2]);
    imagefilledrectangle($img, 0, $H - 8, $W, $H, $borderColor);

    return $img;
}

// ─── Photo definitions ────────────────────────────────────────────────────────

$photos = [];

// KEBERSIHAN — 5 lokasi × 2 status × up to 3 shots
$kebLokasi = [
    ['toilet_l1',   'Toilet Lantai 1',         'Pembersihan & desinfeksi WC dan wastafel'],
    ['lobi',        'Lobi Utama',               'Mopping & lap kaca pintu masuk'],
    ['pantry',      'Pantry Lantai 2',          'Bersihkan meja, sink & microwave'],
    ['ruang_rapat', 'Ruang Rapat Besar',        'Vacuum karpet & lap meja rapat'],
    ['mushola',     'Mushola',                  'Bersihkan sajadah & wudhu area'],
    ['kantin',      'Kantin / Ruang Makan',     'Lap meja makan & sapu area'],
    ['gudang',      'Gudang Umum',              'Sapu & tata barang'],
    ['parkir',      'Area Parkir',              'Sapu area & buang sampah'],
    ['toilet_l2',   'Toilet Lantai 2',          'Desinfeksi & refill sabun'],
];
foreach ($kebLokasi as [$key, $nama, $detail]) {
    for ($i = 1; $i <= 2; $i++) {
        foreach (['sebelum', 'sesudah'] as $st) {
            $photos[] = [
                'path'   => "activity-reports/" . ($st === 'sebelum' ? 'before' : 'after') . "/seed_keb_{$key}_{$i}_{$st}.jpg",
                'domain' => 'kebersihan',
                'lokasi' => $nama,
                'status' => $st,
                'detail' => $detail,
            ];
        }
    }
}

// SATPAM
$satLokasi = [
    ['pos_depan',  'Pos Satpam Depan',    'Pemantauan & patroli pintu masuk'],
    ['pos_belakang','Pos Satpam Belakang','Pemantauan akses belakang gedung'],
    ['parkir',     'Area Parkir',         'Patroli kendaraan & ketertiban'],
    ['lobi',       'Lobi - Pos Tamu',     'Registrasi & screening tamu'],
    ['cctv_room',  'Ruang Monitor CCTV',  'Pemantauan 24 titik CCTV aktif'],
];
foreach ($satLokasi as [$key, $nama, $detail]) {
    for ($i = 1; $i <= 2; $i++) {
        $photos[] = [
            'path'   => "laporan-satpam/seed_satpam_{$key}_{$i}.jpg",
            'domain' => 'satpam',
            'lokasi' => $nama,
            'status' => 'dokumentasi',
            'detail' => $detail,
        ];
    }
}

// OFFICE BOY
$obLokasi = [
    ['setup_rapat',  'Ruang Rapat - Setup',    'Setup kursi, proyektor & papan tulis'],
    ['serving',      'Ruang Rapat - Serving',  'Sajikan konsumsi peserta rapat'],
    ['pantry',       'Pantry - Persiapan',     'Buat kopi/teh & siapkan snack'],
    ['arsip',        'Ruang Arsip',            'Antar & ambil dokumen arsip'],
    ['lobby_service','Lobi - Layanan Tamu',    'Sambut & arahkan tamu'],
];
foreach ($obLokasi as [$key, $nama, $detail]) {
    for ($i = 1; $i <= 2; $i++) {
        foreach (['sebelum', 'sesudah'] as $st) {
            $photos[] = [
                'path'   => "laporan-ob/" . ($st === 'sebelum' ? 'before' : 'after') . "/seed_ob_{$key}_{$i}_{$st}.jpg",
                'domain' => 'ob',
                'lokasi' => $nama,
                'status' => $st,
                'detail' => $detail,
            ];
        }
    }
}

// TOKO
$tokoLokasi = [
    ['kasir',     'Area Kasir',        'Transaksi & pengelolaan kas toko'],
    ['display_a', 'Display Rak A',     'Penataan produk & cek stok'],
    ['display_b', 'Display Rak B',     'Penataan snack & minuman'],
    ['gudang',    'Gudang Toko',       'Penerimaan & cek stok barang'],
    ['etalase',   'Etalase Produk',    'Bersihkan & susun produk unggulan'],
];
foreach ($tokoLokasi as [$key, $nama, $detail]) {
    for ($i = 1; $i <= 2; $i++) {
        $photos[] = [
            'path'   => "laporan-toko/seed_toko_{$key}_{$i}.jpg",
            'domain' => 'toko',
            'lokasi' => $nama,
            'status' => 'dokumentasi',
            'detail' => $detail,
        ];
    }
}

// GUEST COMPLAINTS
$compLokasi = [
    ['toilet',    'Toilet Lantai 1',  'Kondisi yang dilaporkan tamu'],
    ['lobi',      'Lobi Utama',       'Tumpahan yang perlu dibersihkan'],
    ['parkir',    'Area Parkir',      'Kendaraan tidak pada tempatnya'],
    ['kantin',    'Area Kantin',      'Kondisi tidak higienis'],
];
foreach ($compLokasi as [$key, $nama, $detail]) {
    $photos[] = [
        'path'   => "guest-complaints/seed_comp_{$key}.jpg",
        'domain' => 'complaint',
        'lokasi' => $nama,
        'status' => 'dokumentasi',
        'detail' => $detail,
    ];
}

// ─── Generate ─────────────────────────────────────────────────────────────────

$generated = 0;
$skipped   = 0;

foreach ($photos as $p) {
    $fullPath = "$basePath/{$p['path']}";

    if (file_exists($fullPath)) {
        $skipped++;
        continue;
    }

    $img = generatePhoto($p['domain'], $p['lokasi'], $p['status'], $p['detail'], $W, $H, $themes);
    imagejpeg($img, $fullPath, 88);
    imagedestroy($img);
    $generated++;
    echo "✅ {$p['path']}\n";
}

echo "\n🎉 Selesai! Generated: $generated | Skipped (sudah ada): $skipped\n";
echo "📁 Total foto: " . count($photos) . " definisi\n";
