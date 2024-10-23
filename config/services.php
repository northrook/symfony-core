<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{RenderService, ToastService};

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        // Rendering
        ->set( RenderService::class )

        // Toasts
        ->set( ToastService::class )->args( [service( 'request_stack' )] );
};
