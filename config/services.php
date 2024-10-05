<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{CurrentRequest, ToastService};
return static function( ContainerConfigurator $container ) : void {

    $container->services()

            // Current Request ServiceContainer
        ->set( CurrentRequest::class )
        ->args( [service( 'request_stack' ), service( 'http_kernel' )] )

            // Toasts
        ->public()
        ->set( ToastService::class )->args( [service( 'request_stack' )] );
};