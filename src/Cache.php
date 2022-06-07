<?php

namespace Flagsmith;

use Psr\SimpleCache\CacheInterface;

/**
 * This class is a wrapper for the PSR-16 cache interface.
 *
 * It exists as an easy way to allow use to set global Prefix and TTL.
 */
class Cache
{
    private CacheInterface $cache;
    private string $prefix;
    private ?int $ttl = null;

    public function __construct(
        CacheInterface $cache,
        string $prefix,
        ?int $ttl = null
    ) {
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store. Must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        return $this->cache->set(
            $this->getKeyWithPrefix($key),
            $value,
            $ttl ?? $this->ttl
        );
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it, making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool
    {
        return $this->cache->has($this->getKeyWithPrefix($key));
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get(string $key, $default = null)
    {
        return $this->cache->get($this->getKeyWithPrefix($key), $default);
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple(array $values, $ttl = null): bool
    {
        $newValues = [];
        foreach ($values as $key => $value) {
            $newValues[$this->getKeyWithPrefix($key)] = $value;
        }

        return $this->cache->setMultiple($newValues, $ttl ?? $this->ttl);
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple(array $keys, $default = null)
    {
        return $this->cache->getMultiple(
            array_map(fn ($key) => $this->getKeyWithPrefix($key), $keys),
            $default
        );
    }

    /**
     * Get the full Key name including Prefix
     *
     * @param string $key
     * @return string
     */
    public function getKeyWithPrefix(string $key): string
    {
        return $this->prefix . '.' . $key;
    }
}
