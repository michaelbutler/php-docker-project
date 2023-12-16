#!/usr/bin/env php
<?php declare(strict_types=1);

/*
 * runner.php - the CLI command runner entrypoint.
 * This should be the entry point script for all CLI tasks.
 * This file is a lot similar to the web version: public/index.php.
 *
 * E.g.:
 * php ci-cd/runner.php hello:world
 *
 */

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);

/*
 * Standard Bootstrap Stuff
 */

$config = [
    'root_dir' => dirname(__DIR__),
    'template_dir' => dirname(__DIR__) . '/templates',
    'public_dir' => dirname(__DIR__) . '/public',
    'config_dir' => dirname(__DIR__) . '/config',
];

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
$dotenv->safeLoad();
error_clear_last(); // Clear file load E_NOTICE

$app = new \MyApp\App($config);

$GLOBALS['app'] = $app;

/*
 * Don't forget to register commands in the registerCommands() method
 */

$app->bootstrapCli();
$cliApplication = $app->registerCommands();
$code = $app->runCommands($cliApplication);

exit($code);
