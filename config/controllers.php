<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{AssetManager};
use Core\Event\ResponseHandler;
use Core\Response\{Document,Parameters};
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
        ->args( ['response', 0, '%dir.cache%/response'] )
        ->tag( 'cache.pool' )

        // Event
        ->set( ResponseHandler::class )
        // ->args( [service( RenderService::class )] )
        ->call( 'setServiceLocator', [service( 'core.service_locator' )] )
        ->tag( 'kernel.event_subscriber' )

        // Controller Document autowire
        ->set( Document::class )
        ->tag( 'controller.service_arguments' )
        ->args( [service( AssetManager::class )] )
        ->autowire()

        // Template parameters
        ->set( Parameters::class )
        ->tag( 'controller.service_arguments' )
        ->autowire();

    // Document HTML Renderer
    // ->set( DocumentParser::class )
    // ->args( [
    //     service( Document::class ),
    //     service( 'core.service_locator' ),
    // ], );

    // Document render preprocessing
    // ->set( ResponseHandler::class )
    // ->tag( 'controller.service_arguments' )
    // ->args( [
    //     service( Document::class ),
    //     service( Parameters::class ),
    //     service( 'cache.response' ),
    //     service( DocumentParser::class ),
    // ] );
};