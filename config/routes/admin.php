<?php

declare(strict_types=1);

/*
 * Northrook Core Routes
 * Admin routes
 */

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function( RoutingConfigurator $routes ) : void {
    $routes->add( 'core:admin', '/{route}' )
        ->controller( ['core.controller.admin', 'router'] )
        ->requirements( ['route' => '.+'] )
        ->defaults( ['route' => '/admin/dashboard'] )
        ->schemes( ['https'] )
        ->methods( ['GET', 'HEAD', 'OPTIONS'] );

    $routes->add( 'core:admin:api', '/api/{action}' )
        ->controller( ['core.controller.admin', 'api'] )
        ->requirements( ['action' => '.+'] )
        ->schemes( ['https'] )
        ->methods( ['GET', 'POST', 'HEAD'] );

    $routes->add( 'core:admin:search', '/search/{action}' )
        ->controller( ['core.controller.admin', 'search'] )
        ->requirements( ['action' => '.+'] )
        ->defaults( ['action' => null] )
        ->schemes( ['https'] )
        ->methods( ['GET', 'POST', 'HEAD'] );
};
