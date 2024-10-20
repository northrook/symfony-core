<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\{AdminController, PublicController};
use Core\Service\{AssetManager, Headers, Request};
use Core\Response\{Compiler\DocumentHtml, Document, Parameters, ResponseHandler};
use Core\Event\RequestResponseHandler;
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
        service( Request::class ),
        service( ResponseHandler::class ),
    ];

    $container->services()

        // Template cache
        ->set( 'cache.response', PhpFilesAdapter::class )
        ->args( ['response', 0, '%dir.cache%/response'] )
        ->tag( 'cache.pool' )

        // Gateway - Request => Response
        ->set( RequestResponseHandler::class )
        ->args( [
            service( 'router' ),
            service( Headers::class ),
        ] )
        // : ControllerEvent
        ->tag(
            'kernel.event_listener',
            ['method' => 'matchControllerMethod', 'priority' => 128],
        )
        // : ResponseEvent
        ->tag(
            'kernel.event_listener',
            ['method' => 'mergeResponseHeaders', 'priority' => 1_024],
        )
        // : TerminateEvent
        ->tag(
            'kernel.event_listener',
            ['method' => 'sendResponse', 'priority' => -256],
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
             * Core `Public` Controller.
             */
        ->set( PublicController::class )
        ->call( 'setServiceLocator', [service( 'core.service_locator' )] )
        ->args( $controllerArguments )
        ->tag( 'controller.service_arguments' );
};