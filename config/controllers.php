<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\PublicController;
use Symfony\Component\HttpKernel\Profiler\Profiler;

return static function( ContainerConfigurator $container ) : void {

    /**
     * Profiler Alias for `autowiring`.
     */
    $container->services()->alias( Profiler::class, 'profiler' );

    $container->services()
        /**
         * Core `Public` Controller.
         */
        ->set( 'core.controller.public', PublicController::class )
        ->tag( 'controller.service_arguments' );
};
