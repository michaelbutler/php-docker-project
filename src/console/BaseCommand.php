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

namespace MyApp\console;

use DI\Container;
use Symfony\Component\Console\Command\Command;

class BaseCommand extends Command
{
    public function getApp(): \MyApp\App
    {
        global $app;

        return $app;
    }

    public function getContainer(): Container
    {
        return $this->getApp()->getSlim()->getContainer();
    }

    /**
     * @param string $level error, warning, notice, info, etc. will actually be the php method called
     */
    protected function log(string $msg, string $level = 'error'): void
    {
        $this->getApp()->getLogger()->{$level}($msg);
    }
}
