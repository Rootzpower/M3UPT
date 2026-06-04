<?php

error_reporting(E_ALL);

if (PHP_SAPI !== 'cli') {
    die("This script must be ran from the command line.");
}

$settings = array(
    'countriesFolders' => array(
        __DIR__ . '/../logos',
    ),
    'outputFilename' => '0_all_logos_mosaic.md',
    'cols' => 6,
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

function organizeContent(array $logos, string $source): array
{
    $output = array();

    foreach ($logos as $file) {
        $filename = basename($file);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'])) {
            $key = preg_replace('/\.' . $ext . '$/i', '', $filename);
            $output['logos'][$key] = $filename;
        }
    }

    return $output;
}

function createMDFiles(array $logos, string $source): void
{
    global $settings;

    foreach ($logos as $files) {
        $outputFile = $source . DIRECTORY_SEPARATOR . $settings['outputFilename'];

        echo "Generating $outputFile\n";

        $outputContent = "# Logos\n\n";

        $table = "";
        $matrix = array();
        $lists = [];
        $i = 0;

        foreach ($files as $fileKey => $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            // PNG goes to the mosaic
            if ($ext === 'png') {
                $matrix[intdiv($i, $settings['cols'])][] = $fileKey;
                $i++;
            }

            // Only non-PNG files go to lists
            if ($ext !== 'png') {
                $lists[$ext][] = "[$fileKey]:$file";
            }
        }

        // Build mosaic table (PNG only)
        for ($j = 0; $j < count($matrix); $j++) {
            for ($i = 0; $i < $settings['cols']; $i++) {
                $logo = $matrix[$j][$i] ?? "space";
                $table .= '| <div align="center"><img src="' . $logo . '.png" width="120"></div> ';
                if ($i === $settings['cols'] - 1) {
                    $table .= "|\n";
                }
            }

            if ($j === 0) {
                for ($i = 0; $i < $settings['cols']; $i++) {
                    $table .= "|:---:";
                    if ($i === $settings['cols'] - 1) {
                        $table .= "|\n";
                    }
                }
            }
        }

        $outputContent .= "$table\n\n";

        // Add extension sections (ONLY if non-PNG exist)
        if (!empty($lists)) {
            foreach ($lists as $ext => $entries) {
                $outputContent .= "## " . strtoupper($ext) . "\n";
                foreach ($entries as $entry) {
                    $outputContent .= $entry . "\n";
                }
                $outputContent .= "\n";
            }
        }

        file_put_contents($outputFile, $outputContent);
    }
}

function generateAllLogosMosaics(): void
{
    global $settings;

    foreach ($settings['countriesFolders'] as $source) {
        $logos = listAllFiles($source);
        $logos = organizeContent($logos, $source);
        createMDFiles($logos, $source);
    }
}

generateAllLogosMosaics();
