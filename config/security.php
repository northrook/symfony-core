<?php

// -------------------------------------------------------------------
// config\security
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Security\Security;

return static function( ContainerConfigurator $container ) : void {

    $container->services()
        ->set( Security::class )
        ->call( 'setServiceLocator', [service( 'core.service_locator' )] );
};