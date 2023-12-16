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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as IResponse;
use Psr\Http\Message\ServerRequestInterface as IRequest;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

/**
 * Middleware for redirecting www.something.com to something.com (and other common global things).
 */
class SubdomainMiddleware
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function __invoke(IRequest $request, RequestHandler $handler): IResponse
    {
        $uri = $request->getUri();
        if ('www.something.com' === $uri->getHost()) {
            $uri = $uri->withHost('something.com')->withScheme('https');

            return getRedirectResponse(new Response(), (string) $uri);
        }

        return $handler->handle($request);
    }
}
