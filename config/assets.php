<?php

// -------------------------------------------------------------------
// config\assets
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{AssetManager, AssetManager\Manifest, CurrentRequest, StylesheetGenerator};
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        //
        ->set( 'cache.assets', PhpFilesAdapter::class )
        ->args( ['assets', 0, '%dir.cache%/assets'] )
        ->tag( 'cache.pool' )

        //
        ->set( Manifest::class )
        ->args( [
            param( 'path.asset_manifest' ),
            service( 'parameter_bag' ),
            service( 'cache.assets' ),
        ] )

        // //
        // ->set( StylesheetGenerator::class )
        // ->tag( 'controller.service_arguments' )

        // AssetManager
        ->set( AssetManager::class )
        ->args( [
            service( CurrentRequest::class ),
            service( 'cache.assets' ),
            service( 'router' ),
            service( 'parameter_bag' ),
            service( Manifest::class ),
        ] );
};