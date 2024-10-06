<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

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

        $this->parameterBag->set( 'asset.inventory.core.css', \glob( $this->parameterBag->get( 'dir.assets' ).'/styles/*.css' ) );

        dump( $this );
    }

    /**
     * Initialize the {@see Manifest} service for use in this compiler pass.
     *
     * @param Definition  $manifestDefinition
     *
     * @return void
     */
    private function initializeManifestService( Definition $manifestDefinition ) : void
    {
        $this->manifest ??= new ( $manifestDefinition->getClass() )(
            $manifestDefinition->getArgument( 0 ), // $path
            $this->parameterBag, // $parameterBag
            null // $cacheAdapter
        );
    }

    // private function coreStylesheetAsset() : Asset
    // {
    //     // register as core.assetID (the generated hash)
    //     // each asset will have the data attribute data-asset='core', denoted as the 'group type'
    //     // each asset will have the id='assetID_hash' as a unique identifier
    //     // When the AssetManager is asked to fetch 'core' or 'ui:button', it will fetch all in tht group.
    //
    //     $asset = new Asset( 'core' );
    //     $asset->addSource( \glob( $this->parameterBag->get( 'dir.assets' ).'/styles/*.css' ) );
    //     return $asset;
    // }
    // private function generateCoreStyles() : void
    // {
    //     $css = new Stylesheet(
    //             $this->parameterBag->get( 'asset.core.stylesheet' ),
    //             \glob( $this->parameterBag->get( 'dir.assets' ).'/styles/*.css' ),
    //     );
    //
    //     $css->addReset()
    //         ->addBaseline()
    //         ->addDynamicRules();
    //
    //     $css->save( force: true );
    // }
}