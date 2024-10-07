<?php

namespace Core\Service;

use Northrook\Clerk;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Core\DependencyInjection\Component\{UrlGenerator};
use Support\{ClassMethods, Str};
use Core\Service\AssetManager\Asset\{Asset, Script, Style};
use Core\Service\AssetManager\Manifest;
use InvalidArgumentException;
use Northrook\Logger\Log;
use Northrook\Resource\Path;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use const Support\{AUTO, EMPTY_STRING};

/**
 * @internal
 * @author Martin Nielsen
 */
final class AssetManager
{
    use ClassMethods, UrlGenerator;

    public const string ASSETS_HEADER = 'HX-Assets';

    public readonly bool $enabled;

    /**
     * A list of currently active assets, denoted by their `assetID`.
     *
     * - A request with the header `HX-Assets` contain a comma separated list of deployed assets.
     *
     * @var string[]
     */
    public readonly array $deployed;

    /** @var ?bool Default inline setting for {@see Style} and {@see Script} assets */
    public ?bool $inline = AUTO;

    public ?int $cachePersistence = \Time\MINUTE;

    /** @var bool Disables minification */
    public bool $debug = false;

    public function __construct(
        private readonly CurrentRequest          $request,
        protected readonly CacheInterface        $cacheAdapter,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly ParameterBagInterface $parameterBag,
        private readonly Manifest                $manifest,
    ) {
        // Check for an ASSETS_HEADER when handling Hypermedia Requests
        // If this is an ordinary request, enable
        $this->enabled = ( $this->request->isHtmx ) ? $this->request->headerBag( has : $this::ASSETS_HEADER ) : true;

        if ( false === $this->enabled ) {
            Log::notice(
                'The {class} is disabled, no {header} found.',
                [
                    'class'     => $this->classBasename(),
                    'header'    => 'HX-Assets',
                    'headerBag' => $this->request->headerBag()->all(),
                ],
            );
            return;
        }

        $this->deployed = Str::explode( $this->request->headerBag( get : $this::ASSETS_HEADER ) ?? EMPTY_STRING );

        dump(
            $this::class.' enabled: '.\json_encode( $this->enabled ),
            'Deployed assets: '.( $this->deployed ? \implode( ', ', $this->deployed ) : 'none' ),
        );
    }

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
     * Add an asset to the master list of available assets.
     *
     * @param string      $label
     * @param Path|string ...$source
     *
     * @return $this
     */
    public function registerAsset( string $label, string|Path ...$source ) : self
    {
        // foreach ( (array) $source as $source ) {
        //     $path = $source instanceof Path ? $source : new Path( $source );
        //
        //     $this->manifest[$label][] = match ( $path->mimeType ) {
        //         'text/css'        => new Style( $path, $label ),
        //         'text/javascript' => new Script( $path, $label ),
        //         default           => throw new InvalidArgumentException(),
        //     };
        // }

        return $this;
    }

    /**
     * Returns an Asset or Asset Group.
     *
     * @param string       $asset label.type:inline
     * @param class-string $class
     *
     * @return array<string, string>
     */
    public function getAsset( string $asset, string $class ) : array
    {
        $label = \strstr( $asset, '.', true );

        if ( ! $this->manifest->hasAsset( $asset ) ) {
            Log::notice(
                'Asset {assetId} not registered, {action}.',
                [
                    'assetId' => $label,
                    'action'  => 'aborting',
                    'asset'   => $asset,
                ],
            );
            return [];
        }

        if ( $this->isDeployed( $label ) ) {
            Log::notice(
                'Asset {assetId} already deployed, {action}.',
                [
                    'assetId' => $label,
                    'action'  => 'skipped',
                    'asset'   => $asset,
                ],
            );
            return [];
        }

        try {
            return $this->cacheAdapter->get(
                $label,
                function( CacheItem $item ) use ( $label, $asset, $class ) {
                    $profiler = Clerk::event( $item::class."-> {$label}" );
                    $item->expiresAfter( 1 );
                    // $item->expiresAfter( $this->cachePersistence );

                    $registeredAsset = $this->manifest->getSource( $asset );

                    /**
                     * @var Asset $asset
                     */
                    $asset = new ( $class )( $registeredAsset, $label, $this->parameterBag->get( 'dir.public' ) );

                    dump( $asset );

                    // $args   = \is_array( $asset ) ? \end( $asset ) : [];
                    // $assets = [];

                    // foreach ( $registeredAsset as $label => $asset ) {
                    //     $assets["asset.{$asset->type}.{$asset->assetID}"] = $asset->getElement();
                    // }

                    $profiler->stop();
                    return [];
                },
            );
        }
        catch ( \Psr\Cache\InvalidArgumentException $exception ) {
            Log::exception( $exception );
            return [];
        }
    }

    private function isDeployed( string $assetId ) : bool
    {
        return \in_array( $assetId, $this->deployed, true );
    }
}
