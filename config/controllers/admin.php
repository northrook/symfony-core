<?php

// -------------------------------------------------------------------
// config\controllers\admin
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\AdminController;
use Core\Response\{Document, Parameters};

return static function( ContainerConfigurator $controller ) : void {

    $controller->services()
        ->set( AdminController::class )
        ->args( [
            service( Document::class ),
            service( Parameters::class ),
        ] )
        ->call( 'setServiceLocator', [service( 'core.service_locator' )] )
        ->tag( 'controller.service_arguments' )
        ->tag( 'monolog.logger', ['channel' => 'request'] );
};