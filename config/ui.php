<?php

// -------------------------------------------------------------------
// config\ui
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\UI\RenderRuntime;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return static function( ContainerConfigurator $container ) : void {

    $container->services()
            // Template cache
        ->set( 'cache.runtime_render', PhpFilesAdapter::class )
        ->args( ['render', 0, '%dir.cache%'] )
        ->tag( 'cache.pool' )

            // Static Toasts
        ->set( RenderRuntime::class )
        ->args( [
            service( 'cache.runtime_render' ),
        ] );
};
