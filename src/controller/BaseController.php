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

namespace MyApp\controller;

use MyApp\service\Cache;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
{
    public function __construct(
        protected ContainerInterface $container
    ) {}

    /**
     * @param string $template Template name like mypage.tpl.php, path under templates/
     * @param array  $data     Hash map of data; templates will actually use `$data['foo']`...
     * @param int    $code     http status code
     */
    protected function renderHtmlResponse(string $template, array $data, ResponseInterface $response, int $code = 200): ResponseInterface
    {
        return renderHtmlResponse($data, $template, $response, $code);
    }

    protected function getCache(): Cache
    {
        return $this->container->get('cache');
    }
}
