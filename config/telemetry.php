<?php

// -------------------------------------------------------------------
// config\telemetry
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Northrook\Clerk;
use Core\Telemetry;
use Symfony\Component\Stopwatch\Stopwatch;

return static function( ContainerConfigurator $container ) : void {
    $container->services()
        ->set( Clerk::class )
        ->args( [
            service( Stopwatch::class ),
            false, // do not throw on duplicates
        ] )

        // TelemetryEventSubscriber
        ->set( Telemetry\TelemetryEventSubscriber::class )
        ->tag( 'kernel.event_subscriber' )
        ->args( [service( Clerk::class )] );
};
