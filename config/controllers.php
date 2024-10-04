<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\PublicController;
use Core\Service\{AssetManager, CurrentRequest, DocumentService};
use Core\Response\{Document, Parameters, ResponseHandler, RouteHandler};
use Northrook\Latte;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\HttpKernel\Profiler\Profiler;

return static function( ContainerConfigurator $container ) : void {

    /**
     * Profiler Alias for `autowiring`.
     */
    $container->services()->alias( Profiler::class, 'profiler' );

    $container->services()

        // Template cache
        ->set( 'cache.response', PhpFilesAdapter::class )
        ->args( ['response', 0, '%kernel.cache_dir%/response'] )
        ->tag( 'cache.pool' )

        // Router
        ->set( RouteHandler::class )
        ->args( [service( 'router' )] )
        ->tag( 'kernel.event_listener', [
            'method'   => 'matchControllerMethod',
            'priority' => 100,
        ] )

        // Controller Document autowire
        ->set( Document::class )
        ->tag( 'controller.service_arguments' )
        ->args( [service( AssetManager::class )] )
        ->autowire()

        // Template parameters
        ->set( Parameters::class )
        ->tag( 'controller.service_arguments' )
        ->autowire()

        // Document render preprocessing
        ->set( ResponseHandler::class )
        ->tag( 'controller.service_arguments' )
        ->args( [
            service( Document::class ),
            service( Parameters::class ),
            service( CurrentRequest::class ),
            service( 'cache.response' ),
            service_closure( Latte::class ),
        ] )

            // ->set( DocumentService::class )
        // ->args( [service( 'request_stack' ), service( Document::class )] )

        /**
         * Core `Public` Controller.
         */
        ->set( 'core.controller.public', PublicController::class )
        ->args( [
            service( Document::class ),
            service( Parameters::class ),
            service( ResponseHandler::class ),
        ] )
        ->tag( 'controller.service_arguments' );
};
