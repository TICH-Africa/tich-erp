<?php

declare(strict_types=1);

$srcPath = __DIR__.'/../public/images/logo.png';
$src = imagecreatefrompng($srcPath);

if ($src === false) {
    fwrite(STDERR, "Unable to read logo.png\n");
    exit(1);
}

$w = imagesx($src);
$h = imagesy($src);

$minX = $w;
$minY = $h;
$maxX = 0;
$maxY = 0;
$found = false;

for ($y = 0; $y < (int) ($h * 0.75); $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgba = imagecolorat($src, $x, $y);
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;
        $a = ($rgba & 0x7F000000) >> 24;

        if ($a > 110) {
            continue;
        }

        // Bright TICH green (ring + foliage).
        if ($g < 110 || $g < $r + 20 || $g < $b + 20) {
            continue;
        }

        $found = true;
        $minX = min($minX, $x);
        $minY = min($minY, $y);
        $maxX = max($maxX, $x);
        $maxY = max($maxY, $y);
    }
}

if (! $found) {
    fwrite(STDERR, "Could not locate green emblem ring\n");
    exit(1);
}

$boxW = $maxX - $minX + 1;
$boxH = $maxY - $minY + 1;

// Inset slightly so only the emblem fills the frame (avoids empty canvas padding).
$insetX = (int) max(0, round($boxW * 0.02));
$insetY = (int) max(0, round($boxH * 0.02));
$srcX = $minX + $insetX;
$srcY = $minY + $insetY;
$srcW = max(1, $boxW - (2 * $insetX));
$srcH = max(1, $boxH - (2 * $insetY));

echo "Green ring {$boxW}x{$boxH} -> source rect {$srcW}x{$srcH} @ {$srcX},{$srcY}\n";

$outSize = 512;
$dst = imagecreatetruecolor($outSize, $outSize);
imagealphablending($dst, false);
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefilledrectangle($dst, 0, 0, $outSize, $outSize, $transparent);

// Non-uniform scale so an oval ring becomes a true circle in the square canvas.
imagealphablending($dst, true);
imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $outSize, $outSize, $srcW, $srcH);

$radius = ($outSize / 2) - 1;
$center = $outSize / 2;
imagealphablending($dst, false);

for ($y = 0; $y < $outSize; $y++) {
    for ($x = 0; $x < $outSize; $x++) {
        $dx = ($x + 0.5) - $center;
        $dy = ($y + 0.5) - $center;
        $dist = sqrt(($dx * $dx) + ($dy * $dy));

        if ($dist > $radius + 0.75) {
            imagesetpixel($dst, $x, $y, $transparent);
            continue;
        }

        if ($dist > $radius - 0.75) {
            $rgba = imagecolorat($dst, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            $a = ($rgba & 0x7F000000) >> 24;
            $edge = min(1, max(0, ($radius + 0.75 - $dist) / 1.5));
            $alpha = (int) round(127 - ((127 - $a) * $edge));
            imagesetpixel($dst, $x, $y, imagecolorallocatealpha($dst, $r, $g, $b, $alpha));
        }
    }
}

imagesavealpha($dst, true);

foreach ([
    __DIR__.'/../public/images/favicon.png',
    __DIR__.'/../public/images/logo-mark.png',
    __DIR__.'/../public/favicon.png',
] as $path) {
    imagepng($dst, $path, 6);
}

foreach ([32, 180] as $px) {
    $small = imagecreatetruecolor($px, $px);
    imagealphablending($small, false);
    imagesavealpha($small, true);
    imagefilledrectangle($small, 0, 0, $px, $px, $transparent);
    imagealphablending($small, true);
    imagecopyresampled($small, $dst, 0, 0, 0, 0, $px, $px, $outSize, $outSize);
    imagealphablending($small, false);
    imagesavealpha($small, true);
    imagepng($small, __DIR__.'/../public/images/favicon-'.$px.'.png', 6);
    imagedestroy($small);
}

$png32 = file_get_contents(__DIR__.'/../public/images/favicon-32.png');
$dir = pack('vvv', 0, 1, 1);
$entry = pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($png32), 22);
file_put_contents(__DIR__.'/../public/favicon.ico', $dir.$entry.$png32);

echo 'Wrote favicon.ico ('.filesize(__DIR__.'/../public/favicon.ico')." bytes)\n";
