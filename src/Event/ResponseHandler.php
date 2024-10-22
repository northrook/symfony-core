<?php

namespace Core\Event;

use Core\DependencyInjection\ServiceContainer;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event, KernelEvents};
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

    /**
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1?: int}>|string>
     */
    #[Override]
    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::RESPONSE       => ['onKernelResponse'],
            KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest'],
        ];
    }

    public function onKernelFinishRequest( Event\FinishRequestEvent $event ) : void
    {
        dump( $event );
    }

    public function onKernelResponse( Event\ResponseEvent $event ) : void
    {
        dump( $event );
    }
}