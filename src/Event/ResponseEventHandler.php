<?php

namespace Core\Event;

use Symfony\Component\HttpKernel\Event\{ControllerEvent, ResponseEvent};
use Core\Controller\PublicController;
use Core\Service\Headers;
use Symfony\Component\Routing\RouterInterface;

final class ResponseEventHandler
{
    public const array CONTROLLERS = [
        PublicController::class,
    ];

    public function __construct(
        private readonly RouterInterface $router,
        private readonly Headers         $headers,
    ) {}

    public function matchControllerMethod( ControllerEvent $event ) : void
    {
        dump( $event->getRequest() );
    }

    public function mergeResponseHeaders( ResponseEvent $event ) : void
    {
        dump( $event->getRequest() );
    }
}