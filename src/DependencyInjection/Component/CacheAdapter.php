<?php

namespace Core\DependencyInjection\Component;

use Northrook\Logger\Log;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @property AdapterInterface|CacheInterface $cacheAdapter;
 */
trait CacheAdapter
{
    protected function cacheHasItem( mixed $key ) : bool
    {
        try {
            return $this->cacheAdapter->hasItem( $key );
        }
        catch ( InvalidArgumentException $exception ) {
            Log::exception( $exception );
            return false;
        }
    }

    protected function cacheGetValue( mixed $key, mixed $default = null ) : mixed
    {
        return $this->cacheGetItem( $key )?->get() ?? $default;
    }

    protected function cacheGetItem( mixed $key ) : ?CacheItem
    {
        try {
            return $this->cacheAdapter->getItem( $key );
        }
        catch ( InvalidArgumentException $exception ) {
            Log::exception( $exception );
            return null;
        }
    }
}