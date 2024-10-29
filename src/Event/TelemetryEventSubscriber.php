<?php

declare( strict_types = 1 );

namespace Core\Event;

use Northrook\Clerk;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * @author Martin Nielsen <mn@northrook.com>
 */
final readonly class TelemetryEventSubscriber implements EventSubscriberInterface
{
    const string GROUP = 'Clerk';

    public function __construct( private Clerk $monitor ) {}

    public static function getSubscribedEvents() : array
    {
        return [
                'kernel.request'              => 'onKernelRequest',
                'kernel.controller'           => 'onKernelController',
                'kernel.controller_arguments' => 'onKernelControllerArguments',
                'kernel.view'                 => 'onKernelView',
                'kernel.response'             => 'onKernelResponse',
                'kernel.finish_request'       => 'onKernelFinishRequest',
                'kernel.exception'            => 'onKernelException',
                'kernel.terminate'            => 'onKernelTerminate',
        ];
    }

    public function onKernelRequest() : void
    {
        // $this->monitor->event( 'onKernelRequest', $this::GROUP );
    }

    public function onKernelController() : void
    {
        // $this->monitor->event( 'onKernelController', $this::GROUP );
        // $this->monitor->stopwatch->stop( 'onKernelRequest' );
    }

    public function onKernelControllerArguments() : void
    {
        // $this->monitor->stop( 'onKernelController' );
    }

    public function onKernelView() : void
    {
        // $this->monitor->event( 'onKernelView', $this::GROUP );
    }

    public function onKernelResponse( ResponseEvent $event ) : void
    {
        $this->monitor->event( $event::class, 'response' );
    }

    public function onKernelFinishRequest() : void
    {
        $this->monitor::stopGroup( 'response' );
    }

    public function onKernelException( ExceptionEvent $event ) : void
    {
        $this->monitor->event( $event::class, 'exception' );
    }

    public function onKernelTerminate( TerminateEvent $event ) : void
    {
        foreach ( $this->monitor->getEvents() as $event ) {
            $event->stop();
        }
    }

}
