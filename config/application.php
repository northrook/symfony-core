<?php

// -------------------------------------------------------------------
// config\application
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Settings;

return static function( ContainerConfigurator $container ) : void {
    $container->services()
        ->set( Settings::class )
        ->args( ['%kernel.build_dir%'] )
        ->public();
};