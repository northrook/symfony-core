<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\PublicController;
use Core\Service\{AssetManager, DocumentService};
use Core\Response\{Document, ResponseHandler, RouteHandler};
use Symfony\Component\HttpKernel\Profiler\Profiler;

return static function( ContainerConfigurator $container ) : void {

    /**
     * Profiler Alias for `autowiring`.
     */
    $container->services()->alias( Profiler::class, 'profiler' );

    $container->services()
        // Router
        ->set( RouteHandler::class )
        ->tag( 'kernel.event_listener', [
            'method'   => 'matchControllerMethod',
            'priority' => 100,
        ] )
        ->tag( 'controller.service_arguments' )

        // Controller Document autowire
        ->set( Document::class )
        ->tag( 'controller.service_arguments' )
        ->args( [service( AssetManager::class )] )
        ->autowire()

        // Document render preprocessing
        ->set( DocumentService::class )
        ->args( [service( 'request_stack' ), service( Document::class )] )

        //
        ->set( ResponseHandler::class )
        ->tag( 'controller.service_arguments' )
        ->args( [service_closure( DocumentService::class )] )

        /**
         * Core `Public` Controller.
         */
        ->set( 'core.controller.public', PublicController::class )
        ->args( [service( Document::class ), service( ResponseHandler::class )] )
        ->tag( 'controller.service_arguments' );
};
