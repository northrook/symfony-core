<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{IconService, ToastService};

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        // Icon Manager
        ->set( IconService::class )
        ->tag( 'core.service_locator' )

        // Toasts
        ->set( ToastService::class )
        ->args( [service( 'request_stack' )] )
        ->tag( 'core.service_locator' );
};
