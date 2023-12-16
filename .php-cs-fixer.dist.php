<?php

/*
 *   This file is part of php-docker-project
 *   Source: https://github.com/michaelbutler/php-docker-project
 *
 *   THIS HEADER MESSGAGE MAY BE MODIFIED IN .php-cs-fixer.dist.php
 *   in the project root folder.
 *
 *   (c) 2022-23 foo-example.com
 */

$year = date('y');

$header = <<<EOF
  This file is part of php-docker-project
  Source: https://github.com/michaelbutler/php-docker-project

  THIS HEADER MESSGAGE MAY BE MODIFIED IN .php-cs-fixer.dist.php
  in the project root folder.

  (c) 2022-{$year} foo-example.com
EOF;

$finder = PhpCsFixer\Finder::create()
    // ->exclude('tests/Data')
    ->in([
        './src',
        // './tests',
    ])
    ->append([__DIR__ . '/.php-cs-fixer.dist.php'])
    ->append([__DIR__ . '/public/index.php'])
;

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PSR2' => true,
    '@PhpCsFixer' => true,
    'protected_to_private' => false,
    'final_internal_class' => false,
    'header_comment' => ['header' => $header],
    'list_syntax' => ['syntax' => 'short'],
    'concat_space' => ['spacing' => 'one'],
])
    ->setRiskyAllowed(false)
    ->setFinder($finder);
