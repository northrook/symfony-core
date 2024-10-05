<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\{AdminController, PublicController};
use Core\Service\{AssetManager, CurrentRequest};
use Core\Response\{Compiler\DocumentHtml, Document, Parameters, ResponseHandler, RouteHandler};
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\HttpKernel\Profiler\Profiler;

return static function( ContainerConfigurator $container ) : void {
    /**
     * Profiler Alias for `autowiring`.
     */
    $container->services()->alias( Profiler::class, 'profiler' );

    $controllerArguments = [
        service( Document::class ),
        service( Parameters::class ),
        service( CurrentRequest::class ),
        service( ResponseHandler::class ),
    ];

    $container->services()

            // Template cache
        ->set( 'cache.response', PhpFilesAdapter::class )
        ->args( ['response', 0, '%kernel.cache_dir%/response'] )
        ->tag( 'cache.pool' )

            // Router
        ->set( RouteHandler::class )
        ->args( [service( 'router' )] )
        ->tag(
            'kernel.event_listener',
            [
                'method'   => 'matchControllerMethod',
                'priority' => 100,
            ],
        )

            // Controller Document autowire
        ->set( Document::class )
        ->tag( 'controller.service_arguments' )
        ->args( [service( AssetManager::class )] )
        ->autowire()

            // Template parameters
        ->set( Parameters::class )
        ->tag( 'controller.service_arguments' )
        ->autowire()

            // Document HTML Renderer
        ->set( DocumentHtml::class )
        ->args( [
            service( Document::class ),
            service( 'core.service_locator' ),
        ], )

            // Document render preprocessing
        ->set( ResponseHandler::class )
        ->tag( 'controller.service_arguments' )
        ->args( [
            service( Document::class ),
            service( Parameters::class ),
            service( 'cache.response' ),
            service( DocumentHtml::class ),
        ], )
        /**
         * Core `Admin` Controller.
         */
        ->set( 'core.controller.admin', AdminController::class )
        ->args( $controllerArguments )
        ->tag( 'controller.service_arguments' )
        /**
         * Core `Public` Controller.
         */
        ->set( 'core.controller.public', PublicController::class )
        ->args( $controllerArguments )
        ->tag( 'controller.service_arguments' );
};