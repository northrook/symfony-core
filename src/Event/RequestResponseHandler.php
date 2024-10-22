<?php

namespace Core\Event;

use Symfony\Component\HttpKernel\Event\{ControllerEvent, ResponseEvent, TerminateEvent};
use Core\Controller\{AdminController, PublicController};
use Core\Service\Headers;
use Northrook\Clerk;
use Symfony\Component\Routing\RouterInterface;

/**
 * Dynamically handles the Request => Response cycle for the {@see PublicController} and {@see AdminController}.
 *
 * - Dynamic route assignment
 * - Response header parsing
 *
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class RequestResponseHandler
{
    public const string PROFILER_GROUP = 'gateway';

    // set true if valid request
    // ControllerEvent sets this, ResponseHeaders trusts this
    public const array CONTROLLERS = [
        PublicController::class,
        AdminController::class,
    ];

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
            $profiler->stop();
            return;
        }

        // Check if the $controller has a valid $method
        if ( ! $this->getRouteMethod( $event ) ) {
            $profiler->stop();
            return;
        }

        // Bail if the request has no _controller attribute set
        if ( ! $this->getControllerAttributes( $event ) ) {
            $profiler->stop();
            return;
        }

        // Set the new controller and attributes respectively
        $event->setController( $this->controller );
        $event->getRequest()->attributes->set( '_controller', $this->_controller );

        // * All done!

        dump($this);

        $profiler->stop();
    }

    public function mergeResponseHeaders( ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->shouldParseEvent( $event ) ) {
            return;
        }

        // Always remove the identifying header
        \header_remove( 'X-Powered-By' );

        // Merge headers
        $event->getResponse()->headers->add( $this->headers->response->all() );
    }

    public function sendResponse( TerminateEvent $event ) : void
    {
        // dump( $event->getResponse()->headers->all() );
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
     * @param ControllerEvent|ResponseEvent $event
     *
     * @return bool
     */
    private function shouldParseEvent( ControllerEvent|ResponseEvent $event ) : bool
    {
        $profiler = Clerk::event( __METHOD__, $event::class );

        // Only parse main requests
        if ( ! $event->isMainRequest() ) {
            $profiler->stop();
            return false;
        }

        if ( $event instanceof ResponseEvent ) {
            // return true;
            $controller = $event->getRequest()->attributes->get( '_controller' );

            if ( ! $controller ) {
                $profiler->stop();
                return false;
            }

            if ( \is_array( $controller ) ) {
                $controller = $controller[0];
            }

            $profiler->stop();
            return \is_string( $controller ) && \str_starts_with( $controller, 'core' );
        }

        $controller = $event->getController();

        if ( ! \is_array( $controller ) || ! \is_object( $controller[0] ) ) {
            $profiler->stop();
            return false;
        }

        if ( ! \in_array( $controller[0]::class, $this::CONTROLLERS ) ) {
            $profiler->stop();
            return false;
        }

        $this->controller = $controller;

        unset( $controller );

        $profiler->stop();
        return true;
    }
}