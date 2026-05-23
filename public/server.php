<?php

/**
 * Router for PHP's built-in server (Railway/Docker).
 * Serve existing files under /public; everything else goes to Laravel.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if ($uri !== '/' && $uri !== '' && file_exists(__DIR__.$uri)) {
    return false;
}

require_once __DIR__.'/index.php';
