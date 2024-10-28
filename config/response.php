<?php

// -------------------------------------------------------------------
// config\response
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Event\ResponseHandler;
use Core\UI\RenderRuntime;
use Core\Response\{Document, Headers, Parameters, ResponseContext};
use Core\Service\{Toast};
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

return static function( ContainerConfigurator $container ) : void {

    $response = $container->services();

    // Response
    $response->set( ResponseContext::class );

    // Response EventSubscriber
    $response->set( ResponseHandler::class )
        ->args( [
            service( Toast::class ),
        ] )
        ->tag( 'kernel.event_subscriber' );

    // Response Services
    $response->defaults()
        ->tag( 'controller.service_arguments' )
        ->autowire()

        // ResponseHeaderBag Service
        ->set( Headers::class )

        // Document Properties
        ->set( Document::class )

        // Template Parameters
        ->set( Parameters::class );
};
