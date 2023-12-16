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

namespace MyApp\helper;

use MyApp\App;

class Assets
{
    public static function outputCssFiles(): string
    {
        if (App::$is_dev) {
            $path = assetPath('/dist/styles/main.css');
            $css = <<<HEREDOC
<link rel="stylesheet" type="text/css" href="{$path}" media="all" />
HEREDOC;

            return $css;
        }

        // Production files have paths with content hash in them (dynamic)
        $config = app()->getConfig('config_dir') . '/asset_config.php';
        $data = require $config;
        $retval = '';
        foreach ($data['css'] as $path) {
            $retval .= sprintf(
                '<link rel="stylesheet" type="text/css" href="%s" media="all" />' . PHP_EOL,
                $path
            );
        }

        return $retval;
    }

    public static function outputJsFiles(): string
    {
        if (App::$is_dev) {
            $paths = [
                assetPath('/dist/js/runtime.bundle.js'),
                assetPath('/dist/js/main.bundle.js'),
            ];
        } else {
            // Production files have paths with content hash in them (dynamic)
            $config = app()->getConfig('config_dir') . '/asset_config.php';
            $data = require $config;
            $paths = $data['scripts'];
        }
        $retval = '';
        foreach ($paths as $path) {
            $retval .= sprintf(
                '<script defer src="%s"></script>' . PHP_EOL,
                $path
            );
        }

        return $retval;
    }

    public static function assetPath(string $subPath): string
    {
        if (App::$is_dev) {
            // Bust cache in dev
            $prefix = app()->getConfig('public_dir');
            $hash = md5_file($prefix . $subPath);

            return $subPath . '?_h=' . $hash;
        }

        return $subPath;
    }
}
