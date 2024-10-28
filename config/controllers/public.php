<?php

// -------------------------------------------------------------------
// config\controllers\public
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Controller\PublicController;

return static function( ContainerConfigurator $controller ) : void {

    $controller->services()
        ->set( PublicController::class )
        ->tag( 'controller.service_arguments' )
        ->tag( 'monolog.logger', ['channel' => 'request'] );

};
