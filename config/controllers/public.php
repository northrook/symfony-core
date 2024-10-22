<?php

// -------------------------------------------------------------------
// config\controllers\public
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\PublicController;
use Core\Response\{Document, Parameters};

return static function( ContainerConfigurator $controller ) : void {

    $controller->services()
        ->set( PublicController::class )
        ->call( 'setServiceLocator', [service( 'core.service_locator' )] )
        ->tag( 'controller.service_arguments' )
        ->tag( 'monolog.logger', ['channel' => 'request'] );

};