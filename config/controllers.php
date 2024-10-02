<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\PublicController;
use Core\Response\RouteHandler;
use Symfony\Component\HttpKernel\Profiler\Profiler;

return static function( ContainerConfigurator $container ) : void {

    /**
     * Profiler Alias for `autowiring`.
     */
    $container->services()->alias( Profiler::class, 'profiler' );

    $container->services()
            // Router
        ->set( RouteHandler::class )
        ->tag( 'kernel.event_listener', ['priority' => 100] )
        ->tag( 'controller.service_arguments' )

        /**
         * Core `Public` Controller.
         */
        ->set( 'core.controller.public', PublicController::class )
        ->tag( 'controller.service_arguments' );
};
