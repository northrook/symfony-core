<?php

namespace Core\Service;

use Core\DependencyInjection\Component\{CacheAdapter, UrlGenerator};
use Support\{ClassMethods, Str};
use Core\Service\AssetManager\Manifest;
use InvalidArgumentException;
use Northrook\Assets\{Script, Style};
use Northrook\Logger\Log;
use Northrook\Resource\Path;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use const Support\{AUTO, EMPTY_STRING};

/**
 * @internal
 * @author Martin Nielsen
 */
final class AssetManager
{
    use ClassMethods, UrlGenerator, CacheAdapter;

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

    /** @var bool Disables minification */
    public bool $debug = false;

    public function __construct(
        private readonly CurrentRequest          $request,
        protected readonly AdapterInterface      $cacheAdapter,
        protected readonly UrlGeneratorInterface $urlGenerator,
        private readonly Manifest                $manifest,
    ) {
        if ( false === $this->shouldProcessRequest() ) {
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
        $this->setDeployedAssets();
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
        foreach ( (array) $source as $source ) {
            $path = $source instanceof Path ? $source : new Path( $source );

            $this->manifest[$label][] = match ( $path->mimeType ) {
                'text/css'        => new Style( $path, $label ),
                'text/javascript' => new Script( $path, $label ),
                default           => throw new InvalidArgumentException(),
            };
        }

        return $this;
    }

    /**
     * Returns an Asset or Asset Group.
     *
     * @param string $label
     *
     * @return array<string, array<int,string>>
     */
    public function getAsset( string $label ) : ?array
    {

        if ( $this->isDeployed( $label ) ) {
            Log::notice( 'Asset {assetId} already deployed, {action}.', [
                'assetId' => $label,
                'action'  => 'skipped',
            ] );
            return [];
        }

        if ( ! $this->isRegistered( $label ) ) {

            Log::notice( 'Asset {assetId} already deployed, {action}.', [
                'assetId' => $label,
                'action'  => 'skipped',
            ] );
            return [];
        }

        return $this->getRegisteredAsset( $label );
    }

    private function isDeployed( string $assetId ) : bool
    {
        return \in_array( $assetId, $this->deployed, true );
    }

    private function isRegistered( string $assetId ) : bool
    {
        return $this->cacheHasItem( $assetId );
    }

    /**
     * @param string $assetId
     *
     * @return array
     */
    private function getRegisteredAsset( string $assetId ) : array
    {
        return $this->cacheGetValue( $assetId, [] );
    }

    private function setDeployedAssets() : void
    {
        $this->deployed = Str::explode( $this->request->headerBag( get: $this::ASSETS_HEADER ) ?? EMPTY_STRING );
    }

    private function shouldProcessRequest() : bool
    {
        // Check for an ASSETS_HEADER when handling Hypermedia Requests
        if ( $this->request->isHtmx ) {
            return $this->enabled = $this->request->headerBag( has: $this::ASSETS_HEADER );
        }

        // If this is an ordinary request, enable
        return $this->enabled = true; // @phpstan-ignore-line
    }
}
