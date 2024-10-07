<?php

declare(strict_types=1);

namespace Core\Response;

use Core\Controller\PublicController;
use Northrook\Clerk;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\RouterInterface;

final class RouteHandler
{
    public const array CONTROLLERS = [
        PublicController::class,
    ];

    /** @var array{0:object, 1:string} */
    private array $controller;

    /** @var array{0:string, 1:string} */
    private array $_controller;

    // TODO : Dynamically update routes as they are discovered

    public function __construct( private readonly RouterInterface $router ) {}

    public function matchControllerMethod( ControllerEvent $event ) : void
    {
        Clerk::event( RouteHandler::class, 'controller' );

        // Bail if the controller doesn't pass validation
        if ( false === $this->getValidController( $event ) ) {
            return;
        }

        // Check if the $controller has a valid $method
        if ( false === $this->getRouteMethod( $event ) ) {
            return;
        }

        // Bail if the request has no _controller attribute set
        if ( false === $this->getControllerAttributes( $event ) ) {
            return;
        }

        // Set the new controller and attributes respectively
        $event->setController( $this->controller );
        $event->getRequest()->attributes->set( '_controller', $this->_controller );

        // * All done!

        // TODO : Avoid having to discover and validate routes on each request.
        //        Find a way to update the compiled RouteCollection, or simply cache the route.

        // $name  = $event->getRequest()->attributes->get( '_route' );
        // $route = clone $this->router->getRouteCollection()->get( $name );
        //
        // $name .= ":{$this->method}";
        // $route->setPath( $event->getRequest()->attributes->get( 'route' ) );
        // $route->setDefaults( [ '_controller' => $this->_controller ] );
        // $route->setRequirements( [] );
        // $this->router->getRouteCollection()->add( $name, $route );
        //
        // RouteCompiler::compile( $route );
    }

    private function getControllerAttributes( ControllerEvent $event ) : bool
    {
        // Get the _controller attribute
        $_controller = $event->getRequest()->get( '_controller' );

        if ( ! $_controller || ! \is_array( $_controller ) ) {
            return false;
        }

        $_controller[1] = $this->controller[1];

        $this->_controller = $_controller;

        unset( $_controller );

        return true;
    }

    private function getRouteMethod( ControllerEvent $event ) : bool
    {
        // The new route to match
        $route = $event->getRequest()->get( 'route' );

        if ( ! $route || ! \is_string( $route ) ) {
            return false;
        }

        $this->controller[1] = \strtolower( \str_replace( '/', '_', $route ) );

        return \method_exists( $this->controller[0], $this->controller[1] );
    }

    /**
     * @param ControllerEvent $event
     *
     * @return bool
     */
    private function getValidController( ControllerEvent $event ) : bool
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

        $this->controller = $controller;

        unset( $controller );

        return true;
    }
}
