<?php

// -------------------------------------------------------------------
// config\services
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{Pathfinder, ThemeManager, ToastService};

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        // Current Request ServiceContainer
        ->set( ThemeManager::class )
        ->args( [service( Pathfinder::class )] )

        // Toasts
        ->public()
        ->set( ToastService::class )->args( [service( 'request_stack' )] );
};
