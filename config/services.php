<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Settings;
use Core\Response\{Document};
use Core\Service\{DocumentService, Toast, ToastService};

return static function( ContainerConfigurator $container ) : void {

    $container->services()
        ->set( DocumentService::class )
        ->args( [service( Document::class ), service( Settings::class )] )

        // Static Toasts
        ->set( Toast::class );

        // Toasts
        // ->set( ToastService::class )->args( [service( 'request_stack' )] );
};
