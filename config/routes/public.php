<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function( RoutingConfigurator $routes ) : void {
    $routes
        ->add( 'core:public', '/{route}' )
        ->controller( ['core.controller.public', 'router'] )
        ->requirements( ['route' => '(?!_).+'] ) // exclude routes prefixed by underscore
        ->defaults( ['route' => null] )
        ->schemes( ['https'] )
        ->methods( ['GET', 'HEAD', 'OPTIONS'] );
};