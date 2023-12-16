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

use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;

class HelloWorldController extends BaseController
{
    public function actionHelloWorld(IRequest $request, IResponse $response): IResponse
    {
        return $this->renderHtmlResponse('helloworld.tpl.php', [], $response);
    }
}
