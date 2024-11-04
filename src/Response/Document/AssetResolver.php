<?php

declare(strict_types=1);

namespace Core\Response\Document;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class AssetResolver
{
    public const string ASSETS_HEADER = 'HX-Assets';

    private array $deployed = [];

    /**
     * @param string                $publicPath
     * @param array<string, string> $assetDirectories `[bundle=>path]`
     * @param string                $manifestPath
     * @param string                $storagePath
     * @param ?CacheInterface       $cache
     */
    public function __construct(
        public readonly string           $publicPath,
        private array                    $assetDirectories,
        private readonly string          $manifestPath,
        private readonly string          $storagePath,
        private readonly ?CacheInterface $cache = null,
    ) {
        $this->assetDirectories = $assetDirectories;
    }

    public function registerAsset( string $name, ?string $filename = null, array $properties = [] ) : void
    {
        $name = \strtolower( $name );
        $filename ??= $name;
        $properties = \array_merge(
            $properties,
            [
                'script' => [
                    'inline' => true,
                ],
                'style' => [
                    'inline' => true,
                ],
            ],
        );

        // each property key will be matched, so if core has both style and script,
        // it will look for and enqueue both a .css and .js file in the ./assets/{style|script}/core.#|core/files.#
    }

    public function registerDeployed( string ...$assets ) : void
    {
        foreach ( $assets as $asset ) {
            $this->deployed[] = \strtolower( $asset );
        }
    }

    public function addAssetDirectory( string $bundle, string $path ) : void
    {
        $this->assetDirectories[$bundle] = $path ;
    }
}
