<?php

error_reporting(E_ALL);

if (PHP_SAPI !== 'cli') {
    die("This script must be ran from the command line.");
}

if (!extension_loaded('gd')) {
    die("GD extension is required.\n");
}

$settings = array(
    'countriesFolders' => array(
        __DIR__ . '/../logos',
    ),
    'outputFilename' => '0_all_logos_mosaic.png',
    'cols' => 6,
    'logoSize'   => 120,
    'padding'    => 12,
    'bgColor'    => [30, 30, 30],   // fundo de cada célula (cinzento escuro)
    'canvasBg'   => [18, 18, 18],   // fundo geral do mosaico
);

function listAllFiles(string $dir): array
{
    $array = array_diff(scandir($dir), array('.', '..'));

    foreach ($array as &$item) {
        $item = $dir . DIRECTORY_SEPARATOR . $item;
    }
    unset($item);

    foreach ($array as $item) {
        if (is_dir($item)) {
            $array = array_merge($array, listAllFiles($item));
        }
    }

    return $array;
}

function organizeContent(array $logos): array
{
    $output = array();

    foreach ($logos as $file) {
        $filename = basename($file);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($ext === 'png') {
            $key = preg_replace('/\.png$/i', '', $filename);
            $output[$key] = $file; // caminho completo
        }
    }

    ksort($output);

    return $output;
}

function generateMosaicPNG(array $logos, string $outputPath): void
{
    global $settings;

    $cols     = $settings['cols'];
    $size     = $settings['logoSize'];
    $pad      = $settings['padding'];
    $bgColor  = $settings['bgColor'];
    $canvasBg = $settings['canvasBg'];

    $cellSize = $size + $pad * 2;
    $rows     = (int) ceil(count($logos) / $cols);
    $width    = $cols * $cellSize;
    $height   = $rows * $cellSize;

    $canvas = imagecreatetruecolor($width, $height);
    $bgFill = imagecolorallocate($canvas, $canvasBg[0], $canvasBg[1], $canvasBg[2]);
    imagefill($canvas, 0, 0, $bgFill);

    $cellBg = imagecolorallocate($canvas, $bgColor[0], $bgColor[1], $bgColor[2]);

    $files = array_values($logos);
    $total = count($files);

    for ($i = 0; $i < $total; $i++) {
        $col = $i % $cols;
        $row = intdiv($i, $cols);

        $x = $col * $cellSize;
        $y = $row * $cellSize;

        // Fundo da célula
        imagefilledrectangle($canvas, $x, $y, $x + $cellSize - 1, $y + $cellSize - 1, $cellBg);

        // Carregar logo
        $logoPath = $files[$i];
        $logo = @imagecreatefrompng($logoPath);

        if ($logo === false) {
            continue;
        }

        // Redimensionar mantendo proporção
        $origW = imagesx($logo);
        $origH = imagesy($logo);

        $scale = min($size / $origW, $size / $origH);
        $newW  = (int) round($origW * $scale);
        $newH  = (int) round($origH * $scale);

        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);

        imagecopyresampled($resized, $logo, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        // Centrar na célula
        $destX = $x + $pad + intdiv($size - $newW, 2);
        $destY = $y + $pad + intdiv($size - $newH, 2);

        imagecopy($canvas, $resized, $destX, $destY, 0, 0, $newW, $newH);

        imagedestroy($logo);
        imagedestroy($resized);
    }

    imagepng($canvas, $outputPath, 6);
    imagedestroy($canvas);

    echo "Generated: $outputPath (" . count($files) . " logos, $rows rows)\n";
}

function generateAllLogosMosaics(): void
{
    global $settings;

    foreach ($settings['countriesFolders'] as $source) {
        $allFiles = listAllFiles($source);
        $logos    = organizeContent($allFiles);
        $output   = $source . DIRECTORY_SEPARATOR . $settings['outputFilename'];
        generateMosaicPNG($logos, $output);
    }
}

generateAllLogosMosaics();
