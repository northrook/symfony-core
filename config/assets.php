<?php

// -------------------------------------------------------------------
// config\assets
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{AssetManager, AssetManager\Manifest, CurrentRequest, StylesheetGenerator};
use Support\Normalize;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return static function( ContainerConfigurator $container ) : void {

    foreach ( [
        'asset.manifest'         => '%dir.var%/assets/asset-manifest.array.php',
        'asset.core.stylesheet'  => '%dir.assets.storage%/style/stylesheet.css',
        'asset.admin.stylesheet' => '%dir.assets.storage%/style/admin.css',
    ] as $name => $path ) {
        $container->parameters()->set( $name, Normalize::path( $path ) );
    }

    $container->services()

        //
        ->set( 'cache.assets', PhpFilesAdapter::class )
        ->args( ['assets', 0, '%kernel.cache_dir%/assets'] )
        ->tag( 'cache.pool' )

        //
        ->set( Manifest::class )
        ->args( [
            '%asset.manifest%',
            service( 'parameter_bag' ),
            service( 'cache.assets' ),
        ] )

        //
        ->set( StylesheetGenerator::class )
        ->tag( 'controller.service_arguments' )

        // AssetManager
        ->set( AssetManager::class )
        ->args( [
            service( CurrentRequest::class ),
            service( 'cache.assets' ),
            service( 'router' ),
            service( Manifest::class ),
        ] );

    // $container->services()
    //           ->set( Clerk::class )
    //           ->args( [service( Stopwatch::class )] )
    //
    //         // TelemetryEventSubscriber
    //           ->set( Telemetry\TelemetryEventSubscriber::class )
    //           ->tag( 'kernel.event_subscriber' )
    //           ->args( [service( Clerk::class )] );
};
