<?php

// -------------------------------------------------------------------
// config\response
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Event\ResponseHandler;
use Core\Response\{Document, Headers, Parameters};
use Core\Service\{AssetManager, Toast};
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

return static function( ContainerConfigurator $container ) : void {

    $response = $container->services();

    // Response EventSubscriber
    $response->set( ResponseHandler::class )
        ->args( [service( Toast::class )] )
        ->call( 'setServiceLocator', [service( 'core.service_locator' )] )
        ->tag( 'kernel.event_subscriber' );

    // Response Services
    $response->defaults()
        ->tag( 'controller.service_arguments' )
        ->autowire()

        // ResponseHeaderBag Service
        ->set( Headers::class )

        // Document Properties
        ->set( Document::class )
        ->args( [service( AssetManager::class )] ) // ?? Unsure if we want to inject this early

        // Template Parameters
        ->set( Parameters::class );
};
