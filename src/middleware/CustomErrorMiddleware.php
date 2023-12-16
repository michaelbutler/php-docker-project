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

namespace MyApp\middleware;

use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Custom error middleware. This must be added last in the chain to work right.
 * Catches any exception that might be thrown and logs it and renders the proper response
 * to the browser.
 * Ignores logging of common unimportant errors such as 404 in production.
 */
class CustomErrorMiddleware
{
    public static $didLogError = false;

    public function __construct(
        private ContainerInterface $container,
        private bool $isDev
    ) {}

    public function __invoke(IRequest $request, RequestHandler $handler): IResponse
    {
        $logger = $this->container->get('logger');

        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $code = $e->getCode();
            $ignoreLogOnCodes = [400, 401, 403, 404, 301, 302, 303];

            if ($this->isDev || !in_array($code, $ignoreLogOnCodes, true)) {
                $exception = (string) $e;
                $exception = str_replace("\n", '; ', $exception);
                $logger->log(Logger::ERROR, $exception);
                self::$didLogError = true;
            }

            return exceptionToResponse($e);
        }
    }
}
