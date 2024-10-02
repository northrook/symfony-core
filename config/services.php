<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\CurrentRequest;

return static function( ContainerConfigurator $container ) : void {

    $container->services()->defaults()->public()

            // Current Request Service
        ->set( CurrentRequest::class )
        ->args( [service( 'request_stack' ), service( 'http_kernel' )] );
};
