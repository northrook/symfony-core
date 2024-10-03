<?php

declare(strict_types=1);

namespace Core\Service\AssetManager;

use Core\DependencyInjection\Component\CacheAdapter;
use Northrook\ArrayStore;
use Northrook\Resource\Path;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class Manifest
{
    use CacheAdapter;

    private readonly ArrayStore $manifest;

    public function __construct(
        public readonly string              $name,
        public readonly string              $path,
        protected readonly AdapterInterface $cacheAdapter,
    ) {}

    public function asset( string $name ) : Asset
    {
        $asset = $this->manifest()->get( "asset.{$name}" ) ?? new Asset( $name );

        $this->manifest()->add( "asset.{$name}", $asset );

        return $asset;
    }

    /**
     * @param string $name Unique name to identify and retrieve the Asset
     * @param string $path Path to the asset file
     *
     * @return $this
     */
    public function register( string $name, string $path ) : Manifest
    {
        $this->manifest()->set( "inventory.{$name}", new Path( $path ) );
        return $this;
    }

    private function manifest() : ArrayStore
    {
        return $this->manifest ??= new ArrayStore(
            $this->name,
            $this->path,
            // autosave: false,
        );
    }
}