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

namespace MyApp\service;

use MyApp\exception\CurlException;

/**
 * Wrapper around cURL. Use a new class each request you make.
 */
class CurlClient
{
    private const HTTP_USER_AGENT = 'Custom PHP Curl Client';
    private $headers;

    private $connectTimeoutMs = 1000;

    private $overallTimeoutMs = 25000;

    private bool $follow_redirects = false;

    /** @var string Parse strategy on responses. Auto = look at content-type. Otherwise, return raw string body */
    private string $parseResponse = 'auto';

    private int $responseCode;

    /** @var array Map of response headers */
    private array $respHeaders = [];

    /** @var string USERNAME:PASSWORD string to send as basic auth */
    private string $basicAuth = '';

    /**
     * @param string $method Method to send (e.g. GET, POST, PUT, PATCH)
     */
    public function __construct(protected string $url, protected string $method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Make a GET request.
     */
    public function doGet()
    {
        return $this->doCurlRequest(null, 'auto', 'GET');
    }

    /**
     * @param mixed  $body         Raw string body or array of params to be encoded for a POST request
     *                             Valid types: auto, raw (text/plain), json, form
     * @param string $body_format  Format of the body
     * @param string $verbOverride Use this HTTP verb
     *
     * @return array|string Array if auto parsing response is on. raw string otherwise.
     *
     * @throws CurlException
     */
    public function doPost($body, string $body_format = 'auto', string $verbOverride = 'POST')
    {
        return $this->doCurlRequest($body, $body_format, $verbOverride);
    }

    public function setHeader(string $headerName, string $value): void
    {
        $this->validateHeaderString($headerName, $value);
        $this->headers[$headerName] = $value;
    }

    public function getConnectTimeoutMs(): int
    {
        return $this->connectTimeoutMs;
    }

    public function setConnectTimeoutMs(int $connectTimeoutMs): CurlClient
    {
        $this->connectTimeoutMs = $connectTimeoutMs;

        return $this;
    }

    public function getOverallTimeoutMs(): int
    {
        return $this->overallTimeoutMs;
    }

    public function setOverallTimeoutMs(int $overallTimeoutMs): CurlClient
    {
        $this->overallTimeoutMs = $overallTimeoutMs;

        return $this;
    }

    /**
     * @param bool $follow_redirects whether to follow through on redirects or stop
     *
     * @return CurlClient
     */
    public function setFollowRedirects(bool $follow_redirects)
    {
        $this->follow_redirects = $follow_redirects;

        return $this;
    }

    /**
     * @param string $parseResponse Decide how to parse response body. "auto" means use Content-Type, so for example return an array if JSON. Anything else, returns raw body string.
     */
    public function setParseResponse(string $parseResponse): CurlClient
    {
        $this->parseResponse = $parseResponse;

        return $this;
    }

    /**
     * Get the most recent received HTTP status code.
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function setBasicAuth(string $basicAuth): CurlClient
    {
        $this->basicAuth = $basicAuth;

        return $this;
    }

    /**
     * Execute curl and return result.
     *
     * @param \CurlHandle|resource $ch Curl handle
     *
     * @return mixed
     */
    protected function doCurl($ch)
    {
        return curl_exec($ch);
    }

    /**
     * Shared internal function for doing any curl request.
     *
     * @param mixed  $body        Body, array, string, or null
     * @param string $body_format Format to put body in
     * @param string $verb        HTTP Verb to use: GET, POST, PATCH, PUT, etc
     *
     * @return mixed|string
     *
     * @throws CurlException
     */
    private function doCurlRequest(mixed $body = null, string $body_format = 'auto', string $verb = '')
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->getConnectTimeoutMs());
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->getOverallTimeoutMs());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->follow_redirects ? 1 : 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::HTTP_USER_AGENT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ('' !== $this->basicAuth) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->basicAuth);
        }

        if ('GET' === $verb) {
            // fall through
        } elseif ('POST' === $verb) {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ('PUT' === $verb) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ('PATCH' === $verb) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        } elseif ('DELETE' === $verb) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if (null !== $body) {
            if ('json' === $body_format) {
                if (is_array($body)) {
                    $body = \json_encode($body);
                } elseif (!is_string($body)) {
                    throw new \InvalidArgumentException('Body must be string or array');
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                $this->setHeader('Content-Type', 'application/json');
            } elseif ('raw' === $body_format) {
                if (!is_string($body)) {
                    throw new \InvalidArgumentException('Body must be string for "raw" mode');
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                $this->setHeader('Content-Type', 'text/plain');
            } elseif ('urlencoded' === $body_format) {
                // e.g. to support PayPal
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
                $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
            } else {
                // Form mode
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                $this->setHeader('Content-Type', 'multipart/form-data');
            }
        }

        $headers = $this->buildHeadersForCurl();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $respHeaders = [];
        // this function is called by curl for each header received
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$respHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) {
                    // ignore invalid headers
                    return $len;
                }

                $respHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        $result = $this->doCurl($ch);

        $this->respHeaders = $respHeaders;
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->responseCode = $httpCode;
        $connectTime = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

        error_log(sprintf('Curl POST: %s connect, %s total', $connectTime, $totalTime));

        if ($httpCode >= 200 && $httpCode < 300 && strlen($result) > 1) {
            if ('auto' !== $this->parseResponse) {
                return $result;
            }
            $result = trim($result);
            $firstByte = substr($result, 0, 1);
            $lastByte = substr($result, -1);
            if ('{' === $firstByte && '}' === $lastByte) {
                return json_decode($result, true);
            }

            return $result;
        }

        $result = $result ?: '[false, timed out?]';

        throw new CurlException(
            sprintf('CURL resulted in failure (%s). (First 256 bytes: %s) Check logs.', $httpCode, substr($result, 0, 256)),
            $httpCode
        );
    }

    private function buildHeadersForCurl(): array
    {
        $ret = [];
        if (!empty($this->headers)) {
            foreach ($this->headers as $key => $value) {
                $ret[] = $key . ': ' . $value;
            }
        }

        return $ret;
    }

    private function validateHeaderString($key, $value): void
    {
        if (str_contains($key, ':')) {
            throw new \InvalidArgumentException('Invalid header key (check encoding)');
        }
        foreach ([$key, $value] as $test) {
            if (
                !mb_check_encoding($test, 'ASCII')
                || str_contains($test, "\n")
                || str_contains($test, "\0")
                || str_contains($test, "\r")
                || str_contains($test, "\t")
            ) {
                throw new \InvalidArgumentException('Invalid header key (check encoding)');
            }
        }
    }
}
