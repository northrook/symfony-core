<?php

declare(strict_types=1);

namespace Core\Service\AssetManager;

use Core\DependencyInjection\Component\CacheAdapter;
use Northrook\ArrayStore;
use Northrook\Resource\Path;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Manifest
{
    use CacheAdapter;

    private readonly ArrayStore $manifest;

    public function __construct(
        public readonly string                   $path,
        protected readonly ParameterBagInterface $parameterBag,
        protected readonly ?AdapterInterface     $cacheAdapter,
    ) {}

    public function asset( string $name ) : Asset
    {
        $asset = $this->manifest()->get( "asset.{$name}" ) ?? new Asset( $name );

        $this->manifest()->add( "asset.{$name}", $asset );

        return $asset;
    }

    /**
     * @param string $label     Unique name to identify and retrieve the Asset
     * @param string ...$source Path to the source file
     *
     * @return $this
     */
    public function addSourcePath( string $label, string ...$source ) : Manifest
    {
        foreach ( $source as $path ) {
            $source = new Path( $path );
            $type   = $source->extension;
            $name   = $this->assetName( $source->basename );
            $this->manifest()->set( "inventory.{$label}.{$type}.{$name}", $source->path );
        }

        return $this;
    }

    private function manifest() : ArrayStore
    {
        return $this->manifest ??= new ArrayStore( $this->path );
    }

    private function assetName( string|Path $asset ) : string
    {
        $basename = $asset instanceof Path ? $asset->basename : $asset;

        return \str_replace( '.', ':', \strrchr( $basename, '.', true ) );
    }
}
