<?php

declare (strict_types=1);
namespace PHPStan\Cache;

class Cache
{
    /** @var \PHPStan\Cache\CacheStorage */
    private $storage;
    public function __construct(\PHPStan\Cache\CacheStorage $storage)
    {
        $this->storage = $storage;
    }
    /**
     * @param string $key
     * @return mixed|null
     */
    public function load(string $key, string $variableKey)
    {
        return $this->storage->load($key, $variableKey);
    }
    /**
     * @param string $key
     * @param string $variableKey
     * @param mixed $data
     * @return void
     */
    public function save(string $key, string $variableKey, $data) : void
    {
        $this->storage->save($key, $variableKey, $data);
    }
}
