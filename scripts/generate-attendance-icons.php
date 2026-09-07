<?php

function drawIcon(int $size, string $path): void
{
    $img = imagecreatetruecolor($size, $size);
    $green = imagecolorallocate($img, 16, 185, 129);
    $white = imagecolorallocate($img, 255, 255, 255);

    imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $green);

    $cx = (int) round($size / 2);
    $cy = (int) round($size / 2);
    $clockR = (int) round($size * 0.29);

    imagesetthickness($img, max(2, (int) round($size * 0.055)));
    imageellipse($img, $cx, $cy, $clockR * 2, $clockR * 2, $white);
    imageline($img, $cx, $cy, $cx, $cy - (int) round($clockR * 0.68), $white);
    imageline($img, $cx, $cy, $cx + (int) round($clockR * 0.52), $cy + (int) round($clockR * 0.24), $white);
    imagefilledellipse($img, $cx, $cy, max(8, (int) round($size * 0.07)), max(8, (int) round($size * 0.07)), $white);

    imagepng($img, $path);
    imagedestroy($img);
}

$base = dirname(__DIR__) . '/public/pwa/attendance';
drawIcon(192, $base . '/icon-192.png');
drawIcon(512, $base . '/icon-512.png');

echo "Attendance PWA icons generated.\n";
