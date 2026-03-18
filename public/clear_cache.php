<?php
// Temporary cache clearing script
// Delete this file after use

// Clear all caches
$basePath = dirname(__DIR__);

// Clear views cache
$viewsPath = $basePath . '/storage/framework/views';
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
}

// Clear config cache
$configCache = $basePath . '/bootstrap/cache/config.php';
if (file_exists($configCache)) unlink($configCache);

// Clear routes cache
$routesCache = $basePath . '/bootstrap/cache/routes-v7.php';
if (file_exists($routesCache)) unlink($routesCache);

// Clear app cache
$cachePath = $basePath . '/storage/framework/cache/data';
if (is_dir($cachePath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cachePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isFile()) unlink($item->getPathname());
    }
}

echo "Cache cleared successfully at " . date('Y-m-d H:i:s');
