<?php

// -------------------------------------------------------------------
// config\response
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Event\ResponseHandler;
use Core\UI\RenderRuntime;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

return static function( ContainerConfigurator $container ) : void {

    $response = $container->services();

    // Response EventSubscriber
    $response->set( ResponseHandler::class )
        ->tag( 'kernel.event_subscriber' );
};
