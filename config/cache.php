<?php

// -------------------------------------------------------------------
// config\settings
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;


return static function( ContainerConfigurator $container ) : void {
    // $container->services()
    //           ->set( Clerk::class )
    //           ->args( [service( Stopwatch::class )] )
    //
    //         // TelemetryEventSubscriber
    //           ->set( Telemetry\TelemetryEventSubscriber::class )
    //           ->tag( 'kernel.event_subscriber' )
    //           ->args( [service( Clerk::class )] );
};