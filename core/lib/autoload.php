<?php

/**
 * Autoloader for Ascio Core library.
 * PSR-4 compliant autoloader for the Ascio\Core namespace.
 */

spl_autoload_register(function ($class) {
    $prefix = 'Ascio\\Core\\';
    $baseDir = __DIR__ . '/';

    // Check if class uses our namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get relative class name
    $relativeClass = substr($class, $len);

    // Convert namespace separators to directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Load the file if it exists
    if (file_exists($file)) {
        require $file;
    }
});

// Load v3 service classes from SSL module (they're shared)
$v3Autoload = dirname(__DIR__) . '/../ssl/v3/service/autoload.php';
if (file_exists($v3Autoload)) {
    require_once $v3Autoload;
}
