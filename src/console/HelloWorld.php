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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;

#[AsCommand(name: 'hello:world')]
class HelloWorld extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setDescription('Dummy command to print hello world.')
            ->setHelp('')
        ;
    }

    protected function execute(Input $input, Output $output): int
    {
        $output->writeln('');
        $output->writeln('Hello world!');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
