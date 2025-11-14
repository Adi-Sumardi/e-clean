<?php
/**
 * E-Clean PWA Icon Generator
 * Run: php generate-pwa-icons.php
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$outputDir = __DIR__ . '/public/pwa/';

// Create output directory if not exists
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

foreach ($sizes as $size) {
    $filename = "icon-{$size}x{$size}.png";
    $filepath = $outputDir . $filename;
    
    // Create image
    $img = imagecreatetruecolor($size, $size);
    
    // Enable alpha blending
    imagealphablending($img, false);
    imagesavealpha($img, true);
    
    // Create gradient background (Indigo)
    $startColor = imagecolorallocate($img, 99, 102, 241); // #6366f1
    $endColor = imagecolorallocate($img, 79, 70, 229);    // #4f46e5
    
    // Draw gradient
    for ($i = 0; $i < $size; $i++) {
        $ratio = $i / $size;
        $r = 99 + ($ratio * (79 - 99));
        $g = 102 + ($ratio * (70 - 102));
        $b = 241 + ($ratio * (229 - 241));
        $color = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, $i, $size, $i + 1, $color);
    }
    
    // Add white text "EC"
    $white = imagecolorallocate($img, 255, 255, 255);
    $fontSize = $size * 0.35;
    $fontFile = __DIR__ . '/storage/fonts/Arial.ttf'; // Fallback to default
    
    // Text center position
    $text = 'EC';
    $x = $size / 2;
    $y = $size / 2 + ($fontSize * 0.3);
    
    // Try to use TrueType font if available, otherwise use default
    if (function_exists('imagettftext') && file_exists($fontFile)) {
        imagettftext($img, $fontSize, 0, $x - ($fontSize * 0.6), $y, $white, $fontFile, $text);
    } else {
        // Fallback: use built-in font
        $font = 5; // Largest built-in font
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        imagestring($img, $font, ($size - $textWidth) / 2, ($size - $textHeight) / 2, $text, $white);
    }
    
    // Add small circle badge (cleaning symbol)
    $badgeSize = $size * 0.15;
    $badgeX = $size * 0.75;
    $badgeY = $size * 0.25;
    $emerald = imagecolorallocate($img, 16, 185, 129); // #10b981
    imagefilledellipse($img, $badgeX, $badgeY, $badgeSize, $badgeSize, $emerald);
    
    // Save PNG
    imagepng($img, $filepath, 9);
    imagedestroy($img);
    
    echo "✅ Created: {$filename}\n";
}

echo "\n🎉 All PWA icons generated successfully in /public/pwa/\n";
echo "📱 You can now test PWA installation!\n";
