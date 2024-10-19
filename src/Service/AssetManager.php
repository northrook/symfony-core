<?php

declare(strict_types=1);

namespace Core\Service;

use Northrook\{ArrayStore, Clerk};
use Core\Service\AssetManager\Asset;
use Core\Service\AssetManager\Compiler\AssetCompiler;
use InvalidArgumentException;
use Northrook\Logger\Log;
use Support\Str;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use function Support\classBasename;
use const Support\EMPTY_STRING;

/*

 * Each __FILE__::source is saved as a Path object to the database

 * Each Asset::class has a $sources array[assetID => Path::class]

 * Each Asset::class is the end result, and will merge the array if able

Assets will be registered here from each Bundle,
and should have options to merge, minify, and inline.

They need to be exposable to the UI somehow,
so an admin can easily add, edit, disable, or remove at will.

Core assets will not be removable.

The registered Asset should be saved somewhere semi-permanent, ideally to the database.

The __resulting__ asset data (html,css,etc) will be cached at runtime for a given time.

The getAsset method should first check the provided $cacheAdapter, if that returns empty,
recompile with the stored settings.

 */

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class AssetManager
{
    public const string ASSETS_HEADER = 'HX-Assets';

    private readonly ArrayStore $inventory;

    private readonly ArrayStore $manifest;

    /** @var bool `HTMX` requests require the {@see AssetManager::ASSETS_HEADER} */
    public readonly bool $enabled;

    /** @var ?int Expiration setting for the {@see CacheInterface} */
    public ?int $cachePersistence = \Time\MINUTE;

    /**
     * A list of currently active assets, denoted by their `label`.
     *
     * - A request with the header `HX-Assets` contain a comma separated list of deployed assets.
     *
     * @var string[]
     */
    public readonly array $deployed;

    /**
     * @param ?Request              $request       // for determining deployed assets
     * @param ?CacheInterface       $cacheAdapter  // for caching fully generated asset HTML
     * @param ParameterBagInterface $parameterBag  // [pathfinder?]
     * @param string                $inventoryPath
     * @param string                $manifestPath
     */
    public function __construct(
        private readonly ?Request              $request,
        private readonly ?CacheInterface       $cacheAdapter,
        private readonly ParameterBagInterface $parameterBag,
        private readonly string                $inventoryPath,
        private readonly string                $manifestPath,
    ) {
        $this->enabled = ( $this->request?->isHtmx ) ? $this->request?->headerBag( has : $this::ASSETS_HEADER ) : true;

        if ( false === $this->enabled ) {
            Log::notice(
                'The {class} is disabled, no {header} found.',
                [
                    'class'     => classBasename( $this::class ),
                    'header'    => 'HX-Assets',
                    'headerBag' => $this->request?->headerBag()->all(),
                ],
            );
            return;
        }

        $this->deployed = Str::explode( $this->request?->headerBag( get : $this::ASSETS_HEADER ) ?? EMPTY_STRING );
    }

    /**
     * @param string $label
     *
     * @return Asset[]
     */
    public function getAsset( string $label ) : array
    {
        $assets = $this->getManifest()->get( $label );

        if ( $assets instanceof Asset ) {
            return [$assets->id => $assets];
        }

        if ( \is_array( $assets ) ) {
            return $this->maybeNested( $assets );
        }

        throw new InvalidArgumentException();
    }

    public function resolveAssets( string $label ) : array
    {
        $assets      = [];
        [$key, $mod] = Str::bisect( $label, ':' );

        foreach ( $this->getAsset( $key ) as $asset ) {
            // $html = $this->cacheAdapter->get(
            //     "{$asset->id}{$mod}",
            //     function( CacheItem $item ) use ( $asset, $mod ) {
            //         $item->expiresAfter( 5 );
            //
            //         if ( 'inline' === $mod ) {
            //             return ( $asset::class )::inline( $asset );
            //         }
            //         return ( $asset::class )::link( $asset );
            //     },
            // );

            /** @type  Asset $assetClass */
            $assetClass = $asset::class;
            $html       = 'inline' === $mod ? $assetClass::inline( $asset ) : $assetClass::link( $asset );

            $assets["asset.{$asset->type}.{$asset->id}"] = $html;
        }

        return $assets;
    }

    private function maybeNested( array $assets ) : array
    {
        $array = [];

        foreach ( $assets as $asset ) {
            if ( \is_array( $asset ) ) {
                $array = [...$array, ...$this->maybeNested( $asset )];
            }
            else {
                $array[$asset->id] = $asset;
            }

        }
        return $array;
    }

    /**
     * This will compile and register a new Asset under a given label, saving it to the Manifest.
     *
     * - Parse, compile, and merge assets from the inventory by $label or $key.
     * - Minify and optimize resulting data.
     * - Save relevant compiled files in the `./public/assets/{type}` directory.
     *
     * @template AssetObject
     *
     * @param string                    $label
     * @param class-string<AssetObject> $class
     * @param string                    ...$inventory
     *
     * @return AssetObject
     */
    public function compileAsset( string $label, string $class, string ...$inventory ) : mixed
    {
        $profiler = Clerk::event( __METHOD__."->{$label} as {$class}" );

        if ( empty( $inventory ) ) {
            $inventory = [Str::end( $label, \strtolower( '.'.classBasename( $class ) ) )];
        }
        // dump( $inventory );

        $sources = [];

        foreach ( $inventory as $asset ) {
            $sources = [...$sources, ...(array) $this->getInventory()->get( "{$asset}:" )];
        }
        //
        $storageDirectory = $this->parameterBag->get( 'dir.root' );

        /** @var AssetCompiler $compiled */
        $compiled = new ( $class )( $sources, $label, $storageDirectory );

        $type  = $compiled->type;
        $label = Str::end( $label, ".{$type}" );

        $this->getManifest()->set( $label, $compiled->asset );

        $profiler->stop();
        return $compiled;
    }

    public function getInventory() : ArrayStore
    {
        return $this->inventory ??= new ArrayStore( $this->inventoryPath );
    }

    public function getManifest() : ArrayStore
    {
        return $this->manifest ??= new ArrayStore( $this->manifestPath );
    }
}