<?php

declare(strict_types=1);

namespace Core\Service;

use Northrook\ArrayStore;
use Northrook\Logger\Log;
use Support\Str;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use function Support\classBasename;
use const Support\EMPTY_STRING;

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

    /** @var bool Disables minification */
    public bool $debug = false;

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
     * @param CurrentRequest        $request       // for determining deployed assets
     * @param CacheInterface        $cacheAdapter  // for caching fully generated asset HTML
     * @param ParameterBagInterface $parameterBag  // [pathfinder?]
     * @param string                $inventoryPath
     * @param string                $manifestPath
     */
    public function __construct(
        private readonly CurrentRequest        $request,
        private readonly CacheInterface        $cacheAdapter,
        private readonly ParameterBagInterface $parameterBag,
        private readonly string                $inventoryPath,
        private readonly string                $manifestPath,
    ) {
        $this->enabled = ( $this->request->isHtmx ) ? $this->request->headerBag( has : $this::ASSETS_HEADER ) : true;

        if ( false === $this->enabled ) {
            Log::notice(
                'The {class} is disabled, no {header} found.',
                [
                    'class'     => classBasename( $this::class ),
                    'header'    => 'HX-Assets',
                    'headerBag' => $this->request->headerBag()->all(),
                ],
            );
            return;
        }

        $this->deployed = Str::explode( $this->request->headerBag( get : $this::ASSETS_HEADER ) ?? EMPTY_STRING );
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