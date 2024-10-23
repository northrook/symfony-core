<?php

namespace Core\Event;

use Core\Service\{RenderService, Request};
use Core\DependencyInjection\{CoreController, ServiceContainer};
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event, Event\ControllerEvent, Event\FinishRequestEvent, KernelEvents};
/**
 * Handles {@see Response} events for controllers extending the {@see CoreController}.
 *
 * - Output parsing
 * - Response header parsing
 *
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class ResponseHandler implements EventSubscriberInterface
{
    use ServiceContainer;

    private ?string $controller = null;

    public function __construct(
        private RenderService $renderService,
    ) {}

    /**
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1?: int}>|string>
     */
    public static function getSubscribedEvents() : array
    {
        // dd( __METHOD__);
        return [
            KernelEvents::CONTROLLER     => ['onKernelController', -100],
            KernelEvents::RESPONSE       => ['onKernelResponse'],
            KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest'],
        ];
    }

    public function onKernelController( ControllerEvent $event ) : void
    {
        if ( $event->getController() instanceof CoreController ) {
            $this->controller = $event->getController()::class;
        }

        dd( $this );
        // $this->value = __METHOD__;
        // Has reflector already
        // Check if instanceof CoreController - set templates here
    }

    public function onKernelFinishRequest( FinishRequestEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        dd( $this );
    }

    public function onKernelResponse( Event\ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }
        // dd( $this->request()->controller );
    }

    final protected function request() : Request
    {
        return $this->serviceLocator( Request::class );
    }
}