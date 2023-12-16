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

use MyApp\exception\CacheException;

/**
 * General caching service, abstraction to Redis.
 * We use Redis for caching.
 * Don't use this directly, use:
 * $container->get('cache') or cache($container).
 */
class Cache
{
    /** @var \Redis */
    private $redisHandle;

    /** @var callable */
    private $getCreds;

    private $host;

    private $port;

    private $timeout;

    /** @var bool Whether it has connected at least once */
    private $hasConnected = false;

    /**
     * Use this to share a single redis instance for multiple projects and not worry about collisions.
     *
     * @var string
     */
    private $globalPrefix = 'c';

    /**
     * CacherService constructor.
     *
     * @param float  $timeout  Timeout in seconds
     * @param string $username Optional
     * @param string $password Optional
     */
    public function __construct(
        string $host,
        string $port,
        float $timeout = 0.3,
        string $username = '',
        string $password = ''
    ) {
        $redis = new \Redis();

        $this->host = $host;
        $this->port = (int) $port;
        $this->timeout = $timeout;

        $this->getCreds = function () use ($username, $password) {
            return [$username, $password];
        };

        $this->redisHandle = $redis;
    }

    /**
     * Set a scalar value in cache.
     *
     * @param mixed $value   Scalar value (int, float, string) This will be converted to string for storage!
     * @param int   $expires Expiration TTL in seconds. 0 for infinite.
     *
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     *
     * @return bool Returns result of set/setex operation
     */
    public function setScalar(string $namespace, string $key, $value, int $expires = 0): bool
    {
        if (false === $value || null === $value) {
            throw new \InvalidArgumentException(
                'Cannot set cache value as false or null. Use delete operation instead.'
            );
        }
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('$value must be scalar. Use setArray for arrays');
        }
        $cacheKey = $this->globalPrefix . ':' . $namespace . ':' . $key;
        if ($expires > 0) {
            $retVal = $this->getRedis()->setex($cacheKey, $expires, (string) $value);
        } else {
            $retVal = $this->getRedis()->set($cacheKey, (string) $value);
        }

        return $retVal;
    }

    /**
     * Get a scalar value from cache.
     *
     * @return mixed Returns null on a cache miss
     */
    public function getScalar(string $namespace, string $key)
    {
        $cacheKey = $this->globalPrefix . ':' . $namespace . ':' . $key;
        $result = $this->getRedis()->get($cacheKey);
        if (!is_scalar($result)) {
            throw new CacheException('Value in cache not a scalar');
        }
        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * Increment (or decrement if $step is negative) an integer in cache by key.
     * If the value is not in cache, will treat init value as 0, then apply $step.
     *
     * @param int $expires Expires in TTL
     *
     * @return int The new value (could be zero)
     */
    public function increment(string $namespace, string $key, int $step = 1, int $expires = 86400): int
    {
        $cacheKey = $this->globalPrefix . ':' . $namespace . ':' . $key;
        if (0 === $step) {
            throw new \InvalidArgumentException('Cannot increment by zero');
        }
        if ($step > 0) {
            $result = $this->getRedis()->incrBy($cacheKey, $step);
        } else {
            $result = $this->getRedis()->decrBy($cacheKey, $step);
        }
        $this->getRedis()->expire($cacheKey, $expires);

        return is_int($result) ? $result : 0;
    }

    /**
     * Set an array (of arrays or scalars) to cache. Data will be PHP serialized. Data should NOT be
     * any Object or Class; but an exception will not be thrown here. However they will be converted to
     * __PHP_Incomplete_Class on retrieval.
     *
     * @param array $value   Array of data (list or hashmap)
     * @param int   $expires Expiration TTL in seconds. 0 for infinite.
     *
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     *
     * @return bool Returns result of set/setex operation
     */
    public function setArray(string $namespace, string $key, array $value, int $expires = 0): bool
    {
        $cacheKey = $this->globalPrefix . ':' . $namespace . ':' . $key;

        $value = serialize($value);
        if ($expires > 0) {
            $retVal = $this->getRedis()->setex($cacheKey, $expires, $value);
        } else {
            $retVal = $this->getRedis()->set($cacheKey, $value);
        }

        // @var bool $retVal
        return $retVal;
    }

    /**
     * Get a scalar value from cache. Objects or Classes will become __PHP_Incomplete_Class.
     *
     * @return null|array Returns null on a cache miss, otherwise array (could be empty array)
     */
    public function getArray(string $namespace, string $key): ?array
    {
        $cacheKey = $this->globalPrefix . ':' . $namespace . ':' . $key;
        $result = $this->getRedis()->get($cacheKey);
        if (!is_string($result)) {
            return null;
        }
        $result = unserialize($result, ['allowed_classes' => false]);
        if (!is_array($result)) {
            throw new CacheException('Encountered a non-array in cache: ' . gettype($result) . ' for key ' . $cacheKey);
        }

        return $result;
    }

    /**
     * Update the TTL (expire after seconds) of a given key.
     *
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     *
     * @return bool Returns result of expire() operation
     */
    public function updateTtl(string $namespace, string $key, int $ttl): bool
    {
        $cacheKey = $this->globalPrefix . ':' . $namespace . ':' . $key;

        return $this->getRedis()->expire($cacheKey, $ttl);
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     *
     * @return int number of keys removed
     */
    public function delete(string $namespace, string $key): int
    {
        $cacheKey = $this->globalPrefix . ':' . $namespace . ':' . $key;

        return $this->getRedis()->del($cacheKey);
    }

    /**
     * WARNING! This flushes all Redis data.
     */
    public function _flush_all(): bool
    {
        return $this->getRedis()->flushAll();
    }

    /**
     * Ensure you have a lock for a given key and TTL, by using increments with the assumption they init with zero.
     * Use $this->delete() to remove the lock.
     *
     * @return bool true if you have the lock and can proceed, false if you don't have it and should NOT proceed
     */
    public function lockThread(string $namespace, string $key, int $ttl): bool
    {
        $result = (int) $this->increment($namespace, $key, 1, $ttl);

        return 1 === $result;
    }

    /**
     * WARNING: not recommended to hand this raw to controllers.
     */
    protected function getRedis(): \Redis
    {
        $this->connect();

        return $this->redisHandle;
    }

    protected function getCredentials(): array
    {
        $func = $this->getCreds;

        return $func();
    }

    protected function connect(): void
    {
        if ($this->hasConnected && $this->redisHandle->isConnected()) {
            return;
        }

        [$username, $password] = $this->getCredentials();

        $redis = $this->redisHandle;
        $result = $redis->connect($this->host, $this->port, $this->timeout);

        if (!$result) {
            throw new \RuntimeException("Cannot connect to redis on {$this->host}:{$this->port}");
        }

        if ('' !== $password && '0' !== $password) {
            $result = $redis->auth($password);

            if (!$result) {
                throw new \RuntimeException("Cannot authenticate to redis on {$this->host}:{$this->port}");
            }
        }

        $this->hasConnected = true;
    }
}
