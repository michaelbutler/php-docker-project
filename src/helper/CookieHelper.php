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

class CookieHelper
{
    public function __construct() {}

    /**
     * @param array $options Map of options: expires, path, domain, secure, httponly and samesite
     */
    public function setCookie(string $name, string $value = '', array $options = [])
    {
        $secure = true;
        if (App::$is_dev) {
            $secure = false;
        }
        $options['secure'] = $secure;
        if (!isset($options['domain'])) {
            $options['domain'] = getEnvValue('COOKIE_DOMAIN');
        }
        setcookie($name, $value, $options);
    }

    public function getCookies(): array
    {
        return $_COOKIE;
    }
}
