<?php

namespace Core\Event;

use Symfony\Component\HttpKernel\Event\{ControllerEvent, ResponseEvent};
use Core\Controller\PublicController;
use Core\Service\Headers;
use Northrook\Clerk;
use Symfony\Component\Routing\RouterInterface;

final class RequestResponseHandler
{
    public const string PROFILER_GROUP = 'gateway';

    // set true if valid request
    // ControllerEvent sets this, ResponseHeaders trusts this
    public const array CONTROLLERS = [
        PublicController::class,
    ];

    private bool $handleRequest = false;

    /** @var array{0:object, 1:string} */
    private array $controller;

    /** @var array{0:string, 1:string} */
    private array $_controller;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly Headers         $headers,
    ) {}

    public function matchControllerMethod( ControllerEvent $event ) : void
    {
        $profiler = Clerk::event( __METHOD__, $this::PROFILER_GROUP );

        // Bail if the controller doesn't pass validation
        if ( ! $this->shouldParseEvent( $event ) ) {
            return;
        }

        // Check if the $controller has a valid $method
        if ( ! $this->getRouteMethod( $event ) ) {
            return;
        }

        // Bail if the request has no _controller attribute set
        if ( ! $this->getControllerAttributes( $event ) ) {
            return;
        }

        // Set the new controller and attributes respectively
        $event->setController( $this->controller );
        $event->getRequest()->attributes->set( '_controller', $this->_controller );

        // * All done!

        $profiler->stop();
    }

    public function mergeResponseHeaders( ResponseEvent $event ) : void
    {
        $profiler = Clerk::event( __METHOD__, $this::PROFILER_GROUP );

        if ( ! $this->handleRequest ) {
            return;
        }

        dump(
            $this,
            $event,
            $event->getRequest()->headers->all(),
            $this->headers->response->all(),
        );
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
    private function shouldParseEvent( ControllerEvent $event ) : bool
    {
        // Only parse main requests
        if ( ! $event->isMainRequest() ) {
            return $this->handleRequest ??= false;
        }

        $controller = $event->getController();

        if ( ! \is_array( $controller ) || ! \is_object( $controller[0] ) ) {
            return $this->handleRequest ??= false;
        }

        if ( ! \in_array( $controller[0]::class, $this::CONTROLLERS ) ) {
            return $this->handleRequest ??= false;
        }

        $this->controller = $controller;

        unset( $controller );

        return $this->handleRequest ??= true;
    }
}
