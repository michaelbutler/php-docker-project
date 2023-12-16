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

use MyApp\App;
use MyApp\exception\TemplateRenderException;
use MyApp\helper\Assets;
use MyApp\helper\StringUtil;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Psr7\Response;
use Slim\ResponseEmitter;

class HttpResponse
{
    public const SUBCODE_JSON_ERROR = 1;
}

/**
 * @param ResponseInterface $response   Current response obj for chaining
 * @param array             $data       Response data, will go in "response" sub element
 * @param int               $statusCode HTTP Status code
 * @param int               $subcode    Subcode, will go in "subcode" sub element
 * @param array             $errors     Errors, will go in "errors" sub element
 *
 * @return ResponseInterface Modified HTTP response
 */
function json_response(ResponseInterface $response, array $data, int $statusCode = 200, int $subcode = 0, array $errors = []): ResponseInterface
{
    $responseData = [
        'response' => $data,
        'subcode' => $subcode,
        'errors' => $errors,
    ];

    try {
        if (App::$is_dev) {
            $bodyString = \json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } else {
            $bodyString = \json_encode($responseData, JSON_THROW_ON_ERROR);
        }
    } catch (JsonException $e) {
        $responseData = [
            'response' => [],
            'subcode' => \HttpResponse::SUBCODE_JSON_ERROR,
            'errors' => [
                'Invalid JSON or non-UTF8 encoding',
            ],
        ];
        $response->getBody()->write(\json_encode($data));

        return $response->withAddedHeader('Content-Type', 'application/json')
            ->withStatus(400)
        ;
    }
    $response->getBody()->write($bodyString);

    return $response->withAddedHeader('Content-Type', 'application/json')
        ->withStatus($statusCode)
    ;
}

function assetPath(string $subPath): string
{
    return Assets::assetPath($subPath);
}

/**
 * Get the global App instance.
 */
function app(): App
{
    global $app;

    return $app;
}

function logError(string $msg): void
{
    $msg = str_replace("\n", '; ', $msg);
    error_log($msg);
}

/**
 * @return ResponseInterface
 */
function renderStatusPage(int $code, ResponseInterface $response)
{
    $response = $response->withStatus($code);
    $data = [
        'code' => $code,
        'reason' => $response->getReasonPhrase(),
    ];

    return renderHtmlResponse($data, 'status_page.tpl.php', $response, $code);
}

/**
 * Return a formatted HTML response, using $data as template data.
 *
 * @param array $data Data to pass to the templates
 */
function renderHtmlResponse(array $data, string $template, ResponseInterface $response, int $code): ResponseInterface
{
    $response = $response
        ->withStatus($code)
        ->withAddedHeader('Content-Type', 'text/html')
    ;

    set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($template) {
        ob_get_clean();
        ob_get_clean();
        $msg = sprintf(
            "Error occurred rendering the template {$template}: {$errstr} in file {$errfile}:{$errline}"
        );

        throw new TemplateRenderException($msg);
    }, E_ALL);

    ob_start();
    $path = app()->getConfig('template_dir') . '/' . $template;

    require $path;
    $bodyContent = ob_get_clean();

    $data['body_content'] = $bodyContent;

    ob_start();
    $path = app()->getConfig('template_dir') . '/layout.tpl.php';

    require $path;
    $fullHtml = ob_get_clean();

    restore_error_handler();

    $response->getBody()->write($fullHtml);

    return $response;
}

/**
 * https://www.pmg.com/blog/301-302-303-307-many-redirects.
 *
 * @return Response
 */
function getRedirectResponse(ResponseInterface $response, string $url, int $code = 303)
{
    return $response->withStatus($code)->withHeader('Location', $url);
}

/**
 * Output an exception (SAFELY!) to the browser. This function will only emit the first time,
 * any repeat calls will do nothing.
 * Due to calling exceptionToResponse(), the actual error may be converted to something generic,
 * to not give out sensitive details to the public.
 */
function outputExceptionToHttp(App $app, Exception $e)
{
    static $didOutput;

    if (true === $didOutput) {
        return;
    }
    $didOutput = true;

    $newResponse = exceptionToResponse($e);
    $emitter = new ResponseEmitter();
    $emitter->emit($newResponse);
}

function exceptionToResponse(Throwable $e): ResponseInterface
{
    $request = null;
    if ($e instanceof HttpException) {
        $status = $e->getCode();
        $descrip = $e->getDescription();
        $request = $e->getRequest();
    } else {
        // More details of this will already be logged elsewhere.
        $status = 500;
        $descrip = 'A server problem occurred.';
    }
    $errors = [];
    $errors[] = $descrip;
    if (App::$is_dev) {
        $errors[] = $e->getMessage();
    }

    // Try to render HTML response if the browser is expecting an HTML response.
    if ($request) {
        $type = $request->getHeaderLine('Accept');
        $types = explode(',', $type);
        foreach ($types as $i => $type) {
            if ($i > 2) {
                break;
            }
            if ('text/html' === $type) {
                $data = [
                    'errors' => $errors,
                    'code' => $status,
                ];

                return renderHtmlResponse($data, 'error_code.tpl.php', new \Slim\Psr7\Response($status), $status);
            }
        }
    }

    return json_response(
        new \Slim\Psr7\Response($status),
        [],
        $status,
        0,
        $errors
    );
}

/**
 * Make content safe for HTML output.
 *
 * @param mixed|string $text
 */
function h($text): string
{
    $text = (string) $text;

    return htmlentities($text, ENT_QUOTES, 'UTF-8');
}

/**
 * User-generated Content Empty. Same as empty() EXCEPT the string "0" is NOT-EMPTY. However, the number 0 IS empty.
 *
 * @param mixed $value
 */
function ugcEmpty(&$value): bool
{
    if (!isset($value)) {
        return true;
    }
    if ('0' === $value) {
        return false;
    }

    return empty($value);
}

/**
 * Determine if UGC input is valid UTF-8 (also checking for weird ASCII).
 *
 * @return bool True if UTF-8 and contains no weird ASCII
 */
function isUtf8(string $str): bool
{
    return StringUtil::isTypicalUtf8($str);
}

function isUtf8NoWhiteSpace(string $str): bool
{
    if (!isUtf8($str)) {
        return false;
    }
    if (preg_match('/\s/', $str)) {
        return false;
    }

    return true;
}

/**
 * Convert string to proper UTF-8, possibly corrupting it.
 */
function toUtf8(string $str): string
{
    return StringUtil::toUtf8($str);
}

/**
 * Get environment variable wrapper.
 *
 * @return mixed
 */
function getEnvValue(string $key)
{
    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }

    return getenv($key, true);
}

function getTrackers(): string
{
    if (App::$is_dev) {
        $analyticsId = 'G-ASDF123456';
    } else {
        $analyticsId = 'G-ASDF123456';
    }

    return <<<'TRACKERS'

<script>
/* Include any necessary trackers here */
</script>

TRACKERS;
}
