<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Core\Service\{AssetManager, Pathfinder, ThemeManager};
use Core\Service\AssetManager\Compiler\{Style,Script};
use Override;
use Northrook\ArrayStore;
use Northrook\Exception\CompileException;
use Northrook\Resource\Path;
use Symfony\Component\DependencyInjection\{ContainerBuilder};
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class AssetManifestPass implements CompilerPassInterface
{
    private ArrayStore $inventory;

    private AssetManager $assetManager;

    private ThemeManager $themeManager;

    public function __construct( private ParameterBagInterface $parameterBag ) {}

    #[Override]
    public function process( ContainerBuilder $container ) : void
    {
        $this->inventory    = new ArrayStore( $this->parameterBag->get( 'path.asset_inventory' ) );
        $this->assetManager = $this->initializeAssetManager( $container );
        $this->themeManager = $this->initializeThemeManager( $container );

        $this->inventory->clear();
        $this->initializeManifestInventory();

        $this->registerCoreAssets();
    }

    private function initializeManifestInventory() : void
    {
        $appAssets  = $this->parameterBag->get( 'dir.assets' );
        $coreAssets = $this->parameterBag->get( 'dir.core.assets' );
        $themeStyle = $this->parameterBag->get( 'dir.assets.storage' ).'/theme.css';

        $this->themeManager->generateTheme()->save( $themeStyle );

        $this->getAssetGroup( 'baseline', 'style.theme', $themeStyle );

        $this->getAssetGroup( 'baseline', 'style', \glob( $coreAssets.'\styles\reset.css' ) );
        $this->getAssetGroup( 'baseline', 'style', \glob( $coreAssets.'\styles\core.css' ) );
        $this->getAssetGroup( 'baseline', 'style', \glob( $coreAssets.'\styles\core.*.css' ) );

        $this->getAssetGroup( 'core', 'style', \glob( "{$appAssets}\styles\*.css" ) );
        $this->getAssetGroup( 'core', 'script', \glob( "{$appAssets}\scripts\*.js" ) );
        $this->getAssetGroup( 'admin', 'style', \glob( "{$appAssets}\styles\admin\*.css" ) );

        $this->inventory->save();
    }

    private function registerCoreAssets() : void
    {
        $this->assetManager->compileAsset( 'core.style', Style::class, 'baseline.style' );
        $this->assetManager->compileAsset( 'core.script', Script::class );
    }

    private function getAssetGroup( string $label, string $type, string|array $source ) : void
    {
        if ( \is_string( $source ) ) {
            $source = [$source];
        }

        $inventory = [];

        foreach ( $source as $path ) {
            $asset = new Path( $path );

            if ( ! $asset->isReadable ) {
                throw new CompileException( "The asset at '{$asset->path}' is not readable.'" );
            }

            $basename = \strrchr( $asset->basename, '.', true );

            $inventory["{$label}.{$type}.{$basename}"] = $asset->path;
        }

        $this->inventory->set( $inventory );
    }

    /**
     * Initialize the {@see Manifest} service for use in this compiler pass.
     *
     * @param ContainerBuilder $container
     *
     * @return AssetManager
     */
    private function initializeAssetManager( ContainerBuilder $container ) : AssetManager
    {
        $assetManager = $container->getDefinition( AssetManager::class );

        return new ( $assetManager->getClass() )(
            null,
            null,
            $this->parameterBag,
            $this->parameterBag->get( 'path.asset_inventory' ),
            $this->parameterBag->get( 'path.asset_manifest' ),
        );
    }

    /**
     * Initialize the {@see Manifest} service for use in this compiler pass.
     *
     * @param ContainerBuilder $container
     *
     * @return ThemeManager
     */
    private function initializeThemeManager( ContainerBuilder $container ) : ThemeManager
    {
        $assetManager   = $container->getDefinition( ThemeManager::class );
        $pathfinder     = $container->getDefinition( Pathfinder::class );
        $pathfinderPath = $pathfinder->getArgument( 1 );

        dump( $pathfinderPath );

        return new ( $assetManager->getClass() )(
            new Pathfinder( $this->parameterBag, $pathfinderPath )
        );
    }
}
