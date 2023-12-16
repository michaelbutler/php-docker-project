<?php

declare(strict_types=1);

/*
 *   This file is part of php-docker-project
 *   Source: https://github.com/michaelbutler/php-docker-project
 *
 *   THIS HEADER MESSGAGE MAY BE MODIFIED IN .php-cs-fixer.dist.php
 *   in the project root folder.
 *
 *   (c) 2022-23 foo-example.com
 */

// Web entrypoint.

error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

$_starttime = microtime(true);
define('REQUEST_TIME_START', $_starttime);

$config = [
    'root_dir' => dirname(__DIR__),
    'template_dir' => dirname(__DIR__) . '/templates',
    'public_dir' => dirname(__DIR__) . '/public',
    'config_dir' => dirname(__DIR__) . '/config',
];

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
$dotenv->safeLoad();
error_clear_last(); // Clear file load E_NOTICE if .env is not present

$app = new \MyApp\App($config);

$GLOBALS['app'] = $app;

// Serve the web request
$app->runWeb();
