<?php

declare(strict_types=1);

namespace Core\Service\AssetManager;

use Core\DependencyInjection\Component\CacheAdapter;
use Core\Service\AssetManager\Asset\{Asset, Font, Script, Style};
use Northrook\ArrayStore;
use Northrook\Exception\E_Value;
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

    /**
     * @param string  $label
     * @param ?string $type
     *
     * @return Asset[]
     */
    public function getAsset( string $label, ?string $type = null ) : array
    {
        $label = "asset.{$label}.{$type}";
        if ( $type ) {
            $label .= ".{$this->assetType( $type )}";
        }

        return $this->manifest()->get( $label, [] );
    }

    /**
     * @template AssetObject
     *
     * @param string                    $label
     * @param class-string<AssetObject> $as
     * @param null|array|string         $source
     *
     * @return AssetObject
     */
    public function registerAsset( string $label, string $as, null|string|array $source = null ) : mixed
    {
        $type = $this->assetType( $as );
        $source ??= $this->manifest()->get( "inventory.{$label}.{$type}:" ) ;

        $asset = new ( $as )( (array) $source, $label );

        $this->manifest()->set( "asset.{$label}.{$type}", $asset );

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

    private function assetType( string $classString ) : string
    {
        return match ( $classString ) {
            Style::class  => 'css',
            Script::class => 'js',
            Font::class   => 'font',
            default       => E_Value::error( 'The provided {asClass} is not a valid asset type.', ['asClass' => $classString], halt: true ),
        };
    }

    private function assetName( string|Path $asset ) : string
    {
        $basename = $asset instanceof Path ? $asset->basename : $asset;

        return \strrchr( $basename, '.', true ) ;
        // return \str_replace( '.', ':', \strrchr( $basename, '.', true ) );
    }
}