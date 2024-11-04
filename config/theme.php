<?php

// -------------------------------------------------------------------
// config\theme
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Framework\Pathfinder;
use Core\Service\ThemeManager;
use Support\Normalize;

return static function( ContainerConfigurator $theme ) : void {

    $parameters = [];

    foreach ( $parameters as $name => $value ) {
        $theme->parameters()->set( $name, Normalize::path( $value ) );
    }

    $theme->services()
        ->set( ThemeManager::class )
        ->args( [service( Pathfinder::class )] );
};
