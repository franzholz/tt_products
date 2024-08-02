<?php

declare(strict_types = 1);

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

function scanLocalDir(string $dir): array
{
    $foundXmlFiles = [];
    $dir = realpath($dir);
    if (is_dir($dir)) {
        /** @var array<int, string> $possibleMatches */
        $possibleMatches = scandir($dir);
        foreach ($possibleMatches as $possibleMatch) {
            $fullPath = $dir . '/' . $possibleMatch;
            if (is_file($fullPath)) {
                if (in_array(pathinfo($fullPath, PATHINFO_EXTENSION), ['xlf', 'xml'])) {
                    $foundXmlFiles[] = realpath($fullPath);
                }
            } elseif (!in_array($possibleMatch, ['.', '..']) && is_dir($fullPath)) {
                $foundXmlFiles = array_merge(
                    $foundXmlFiles,
                    scanLocalDir(
                        $fullPath
                    )
                );
            }
        }
    }
    return $foundXmlFiles;
}

$xmlFiles = array_unique(scanLocalDir(__DIR__ . '/../../packages/'));

$errorFiles = [];

foreach ($xmlFiles as $xmlFile) {
    $loadedFile = @simplexml_load_file($xmlFile);
    if (!$loadedFile) {
        $errorFiles[] = $xmlFile;
    }
}

if (count($errorFiles) === 0) {
    exit(0);
}

foreach ($errorFiles as $errorFile) {
    echo 'Misspelled XML found: ' . $errorFile . PHP_EOL;
}

exit(1);
