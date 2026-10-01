<?php
/**
 * Router script for PHP Built-in Server
 * Apex Gaming Platform
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// 1. Static asset handling (css, js, images, svgs, fonts, etc.)
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Non-PHP static files
    if ($extension !== 'php') {
        $mimes = [
            'css'  => 'text/css; charset=UTF-8',
            'js'   => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
        ];
        
        if (isset($mimes[$extension])) {
            header('Content-Type: ' . $mimes[$extension]);
        }
        readfile($file);
        exit;
    }
}

// 2. Directory requests (e.g. /user/ or /admin/)
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (file_exists($index)) {
        require $index;
        exit;
    }
}

// 3. Direct PHP script execution
if (file_exists($file) && is_file($file)) {
    require $file;
    exit;
}

// 4. Fallback to index.php
require __DIR__ . '/index.php';
