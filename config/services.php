<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\DataCollector\PipelineCollector;
use Core\Settings;
use Core\Response\{Document};
use Core\Service\{DocumentService, IconService, ToastService};

return static function( ContainerConfigurator $container ) : void {

    $container->services()
        ->set( DocumentService::class )
        ->args( [service( Document::class ), service( Settings::class )] )

        // Icon Manager
        ->set( IconService::class )

        // Toasts
        ->set( ToastService::class )->args( [service( 'request_stack' )] );
};
