<?php

error_reporting(E_ALL);

if (PHP_SAPI !== 'cli') {
    die("This script must be ran from the command line.");
}

$settings = array(
    'countriesFolders' => array(
        __DIR__ . '/../logos',
    ),
    'countriesIgnorePatterns' => '/(Ω)/',
    'countriesRootPatterns' => '/.+\/logos/',
    'outputFilename' => '0_all_logos_mosaic.md',
    'cols' => 6,
    'flags' => array(),
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
        $simplifiedPath = str_replace($source . DIRECTORY_SEPARATOR, '', $file);
        $filename = basename($simplifiedPath);
        $allowedExtensionsPattern = '/\.(png)/i';

        if (!empty($filename) && preg_match($allowedExtensionsPattern, $filename)) {
            $key = preg_replace($allowedExtensionsPattern, '', $filename);
            $output['logos'][$key] = $filename;
        }
    }

    foreach ($output as &$countryArray) {
        ksort($countryArray);
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
        $list = "";
        $i = 0;

        foreach ($files as $fileKey => $file) {
            $matrix[intdiv($i, $settings['cols'])][] = $fileKey;
            $list .= "[$fileKey]:$file\n";
            $i++;
        }

        for ($j = 0; $j < count($matrix); $j++) {
            for ($i = 0; $i < $settings['cols']; $i++) {
                $table .= "| ![" . (($matrix[$j][$i]) ?? "space") . "] ";
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

        $outputContent .= "$table\n\n$list\n";

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
