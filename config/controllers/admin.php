<?php

// -------------------------------------------------------------------
// config\controllers\admin
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\AdminController;

return static function( ContainerConfigurator $controller ) : void {

    $controller->services()
        ->set( AdminController::class )
        ->call( 'setServiceLocator', [service( 'core.service_locator' )] )
        ->tag( 'controller.service_arguments' )
        ->tag( 'monolog.logger', ['channel' => 'request'] );
};