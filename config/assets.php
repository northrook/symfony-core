<?php

// -------------------------------------------------------------------
// config\assets
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Northrook\Assets\AssetManager;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return static function( ContainerConfigurator $container ) : void {

    $container->services()
        ->set( 'cache.assets', PhpFilesAdapter::class )
        ->args( ['assets', 0, '%kernel.cache_dir%/assets'] )
        ->tag( 'cache.pool' )

            // AssetManager
        ->set( AssetManager::class )
        ->args( [service( 'request_stack' ), service( 'cache.assets' )] );

    // $container->services()
    //           ->set( Clerk::class )
    //           ->args( [service( Stopwatch::class )] )
    //
    //         // TelemetryEventSubscriber
    //           ->set( Telemetry\TelemetryEventSubscriber::class )
    //           ->tag( 'kernel.event_subscriber' )
    //           ->args( [service( Clerk::class )] );
};