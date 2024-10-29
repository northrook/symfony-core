<?php

// -------------------------------------------------------------------
// config\telemetry
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\DataCollector\PipelineCollector;
use Core\Event\TelemetryPipeline;
use Northrook\Clerk;
use Symfony\Component\Stopwatch\Stopwatch;

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        // Profiler
        ->set( PipelineCollector::class )
        ->tag( 'data_collector' )

        // Stopwatch
        ->set( Clerk::class )
        ->args( [
            service( Stopwatch::class ),
            false, // do not throw on duplicates
        ] )

        // TelemetryEventSubscriber
        ->set( TelemetryPipeline::class )
        ->tag( 'kernel.event_subscriber' )
        ->args( [service( Clerk::class )] );
};
