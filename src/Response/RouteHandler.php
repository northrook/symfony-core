<?php

declare(strict_types=1);

namespace Core\Response;

use Symfony\Component\HttpKernel\Event\ControllerEvent;

final class RouteHandler
{
    public function __invoke( ControllerEvent $controller ) : void
    {
        if ( \str_starts_with( $controller->getRequest()->getPathInfo(), '/_' ) ) {
            return;
        }

        $route = $controller->getRequest()->get( 'route' );

        $current = $controller->getController();

        if ( \method_exists( $current[0], $route ) ) {
            $current[1] = $route;
        }
        else {
            $current[1] = 'index';
        }

        $controller->setController( $current );
        dump( $current, $route );
    }
}
