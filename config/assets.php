<?php

// -------------------------------------------------------------------
// config\assets
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{AssetManager, AssetManager\Manifest, CurrentRequest};
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        //
        ->set( 'cache.assets', PhpFilesAdapter::class )
        ->args( ['assets', 0, '%kernel.cache_dir%/assets'] )
        ->tag( 'cache.pool' )

        //
        ->set( Manifest::class )
        ->args( [
            'asset.manifest',   // name
            '%dir.var%/assets', // directory
            service( 'cache.assets' ),
        ] )

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
