<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Core\Service\AssetManager\Asset\{Script, Style};
use Core\Service\AssetManager\Manifest;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\{ContainerBuilder, Definition};
use Override;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class AssetManifestPass implements CompilerPassInterface
{
    private Manifest $manifest;

    public function __construct( private ParameterBagInterface $parameterBag ) {}

    #[Override]
    public function process( ContainerBuilder $container ) : void
    {
        $this->initializeManifestService( $container->getDefinition( Manifest::class ) );

        $this->initializeManifestInventory();

        // $this->parameterBag->set( 'asset.inventory.core.css' );

        $this->manifest->registerAsset( 'core', Style::class )
            ->compile();
        $this->manifest->registerAsset( 'core', Script::class )
            ->compile();

        // $this->manifest->update();
    }

    private function initializeManifestInventory() : void
    {
        $appAssets  = $this->parameterBag->get( 'dir.assets' );
        $coreAssets = $this->parameterBag->get( 'dir.core.assets' );

        $this->manifest->addSourcePath( 'core', ...\glob( "{$appAssets}\styles\*.css" ) );
        $this->manifest->addSourcePath( 'core', ...\glob( "{$appAssets}\scripts\*.js" ) );
        $this->manifest->addSourcePath( 'admin', ...\glob( "{$appAssets}\styles\admin\*.css" ) );
    }

    /**
     * Initialize the {@see Manifest} service for use in this compiler pass.
     *
     * @param Definition $manifestDefinition
     *
     * @return void
     */
    private function initializeManifestService( Definition $manifestDefinition ) : void
    {
        $this->manifest ??= new ( $manifestDefinition->getClass() )(
            $manifestDefinition->getArgument( 0 ), // $path
            $this->parameterBag,                   // $parameterBag
            null, // $cacheAdapter
        );
    }
}