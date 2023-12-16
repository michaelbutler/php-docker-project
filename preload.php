<?php

/*
 * PRODUCTION ONLY preload file which will be /app/preload.php in the container
 * Pre-loads PHP files into opcache.
 * See: ci-cd/prod.opcache.ini file
 */

$directory = new RecursiveDirectoryIterator(__DIR__ . '/src');
$fullTree = new RecursiveIteratorIterator($directory);
$phpFiles = new RegexIterator($fullTree, '/.+((?<!Test)+\.php$)/i', RecursiveRegexIterator::GET_MATCH);

foreach ($phpFiles as $key => $file) {
    opcache_compile_file($file[0]);
}
