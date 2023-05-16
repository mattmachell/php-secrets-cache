<?php

declare(strict_types=1);

namespace SecretsCache;

use Psr\SimpleCache as Cache;

class APCUCache implements Cache\CacheInterface
{

    public function __construct(protected int $default_expiry = 3600, protected string $prefix = 'secretscache')
    {
    }

    public function convertToSeconds(null|int|\DateInterval $ttl = null): int
    {
        if ($ttl instanceof \DateInterval) {
            $start = new \DateTimeImmutable();
            $end = $start->add($ttl);
            $ttl = $end->getTimeStamp() - $start->getTimeStamp();
        } elseif ($ttl === null) {
            $ttl = $this->default_expiry;
        }

        return $ttl;
    }

    protected function getKey(string $key) : string{
        return $this->prefix .':'. $key;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        \apcu_store($this->getKey($key), \serialize($value), $this->convertToSeconds($ttl));

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stored = \apcu_fetch($this->getKey($key), $sucess);

        $stored = !empty($stored) ? \unserialize($stored) : $default;

        return $stored;
    }

    public function delete(string $key): bool
    {
        return \apcu_delete($this->getKey($key));
    }

    public function clear(): bool
    {
        \apcu_delete(new \APCUIterator('#^' . $this->prefix . '#'));
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $return = [];
        foreach ($keys as $key) {
            $value = \apcu_fetch($this->getKey($key));
            $return[$key] = !empty($value) ? \unserialize($value) : $default;
        }
        return $return;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            \apcu_store($this->getKey($key), \serialize($value), $this->convertToSeconds($ttl));
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $delete_keys = [];
        foreach ($keys as $key) {
            $delete_keys[] = $this->getKey($key);
        }
        \apcu_delete($delete_keys);
        return true;
    }

    public function has(string $key): bool
    {
        return \apcu_exists($this->getKey($key));
    }
}
