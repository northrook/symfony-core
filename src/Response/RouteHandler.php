<?php

declare(strict_types=1);

namespace Core\Response;

use JetBrains\PhpStorm\Pure;
use Core\Controller\PublicController;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

final class RouteHandler
{
    public const array CONTROLLERS = [
        PublicController::class,
    ];

    public function matchControllerMethod( ControllerEvent $event ) : void
    {
        $controller = $this->getValidController( $event );

        // Bail if the controller doesn't pass validation
        if ( false === $controller ) {
            return;
        }

        // The new route to match
        $method = $event->getRequest()->get( 'route' );

        // Check if the $controller has a valid $method
        if ( ! $method || false === \method_exists( $controller, $method ) ) {
            return;
        }

        $_controller = $event->getRequest()->get( '_controller' )[0] ?? false;

        // Bail if the request has no _controller attribute set
        if ( ! $_controller ) {
            return;
        }

        // Set the new controller and attributes respectively
        $event->setController( [$controller, $method] );
        $event->getRequest()->attributes->set( '_controller', [$_controller, $method] );

        // All done!
    }

    /**
     * @param ControllerEvent $event
     *
     * @return false|object
     */
    #[Pure( true )]
    private function getValidController( ControllerEvent $event ) : object|false
    {
        // Only parse main requests
        if ( ! $event->isMainRequest() ) {
            return false;
        }

        $controller = $event->getController();

        if ( ! \is_array( $controller ) || ! \is_object( $controller[0] ) ) {
            return false;
        }

        if ( ! \in_array( $controller[0]::class, $this::CONTROLLERS ) ) {
            return false;
        }

        return $controller[0];
    }
}
