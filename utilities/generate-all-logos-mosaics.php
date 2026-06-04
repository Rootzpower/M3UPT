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
    'outputFilename' => '0_all_logos_mosaic.md',
    'thumbsDir'      => '0_thumbs',
    'cols'           => 6,
    'thumbSize'      => 120,
    'bgColor'        => [30, 30, 30],
    'padding'        => 10,
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
            $output[$key] = $file;
        }
    }
    ksort($output);
    return $output;
}

function generateThumb(string $srcPath, string $destPath): void
{
    global $settings;
    $size   = $settings['thumbSize'];
    $pad    = $settings['padding'];
    $bg     = $settings['bgColor'];
    $canvas = $size + $pad * 2;

    $img = @imagecreatefrompng($srcPath);
    if ($img === false) return;

    $thumb  = imagecreatetruecolor($canvas, $canvas);
    $bgFill = imagecolorallocate($thumb, $bg[0], $bg[1], $bg[2]);
    imagefill($thumb, 0, 0, $bgFill);

    $origW = imagesx($img);
    $origH = imagesy($img);
    $scale = min($size / $origW, $size / $origH);
    $newW  = (int) round($origW * $scale);
    $newH  = (int) round($origH * $scale);

    $resized = imagecreatetruecolor($newW, $newH);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
    imagefill($resized, 0, 0, $transparent);
    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $destX = $pad + intdiv($size - $newW, 2);
    $destY = $pad + intdiv($size - $newH, 2);
    imagecopy($thumb, $resized, $destX, $destY, 0, 0, $newW, $newH);

    imagepng($thumb, $destPath, 6);
    imagedestroy($img);
    imagedestroy($resized);
    imagedestroy($thumb);
}

function createMDFiles(array $logos, string $source): void
{
    global $settings;

    $thumbsPath = $source . DIRECTORY_SEPARATOR . $settings['thumbsDir'];
    if (!is_dir($thumbsPath)) {
        mkdir($thumbsPath, 0755, true);
    }

    $outputFile = $source . DIRECTORY_SEPARATOR . $settings['outputFilename'];
    echo "Generating $outputFile\n";

    $outputContent = "# Logos\n\n";
    $table  = "";
    $matrix = array();
    $i = 0;

    foreach ($logos as $fileKey => $filePath) {
        $matrix[intdiv($i, $settings['cols'])][] = array('key' => $fileKey, 'path' => $filePath);
        $i++;
    }

    for ($j = 0; $j < count($matrix); $j++) {
        for ($i = 0; $i < $settings['cols']; $i++) {
            $item = $matrix[$j][$i] ?? null;
            if ($item) {
                $thumbFile = $settings['thumbsDir'] . '/' . $item['key'] . '.png';
                $thumbFull = $source . DIRECTORY_SEPARATOR . $settings['thumbsDir'] . DIRECTORY_SEPARATOR . $item['key'] . '.png';
                generateThumb($item['path'], $thumbFull);
                $table .= '| !['  . $item['key'] . '](' . $thumbFile . ') ';
            } else {
                $table .= '| ';
            }
        }
        $table .= "|\n";

        if ($j === 0) {
            for ($i = 0; $i < $settings['cols']; $i++) {
                $table .= "|:---:";
            }
            $table .= "|\n";
        }
    }

    $outputContent .= "$table\n";
    file_put_contents($outputFile, $outputContent);
    echo "Done. " . count($logos) . " logos processed.\n";
}

function generateAllLogosMosaics(): void
{
    global $settings;
    foreach ($settings['countriesFolders'] as $source) {
        $allFiles = listAllFiles($source);
        $thumbsDir = $source . DIRECTORY_SEPARATOR . $settings['thumbsDir'];
        $allFiles = array_filter($allFiles, function($f) use ($thumbsDir) {
            return strpos($f, $thumbsDir) === false;
        });
        $logos = organizeContent($allFiles);
        createMDFiles($logos, $source);
    }
}

generateAllLogosMosaics();
