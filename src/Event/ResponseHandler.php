<?php

namespace Core\Event;

use Northrook\Logger\Log;
use ReflectionFunctionAbstract;
use Core\DependencyInjection\{ServiceContainer};
use Core\Framework;
use Core\Framework\Controller\Template;
use Core\Response\{Document};
use Core\Service\{ToastService};
use Core\UI\Component\Notification;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpKernel\{Event\ControllerEvent, Event\ResponseEvent, Event\TerminateEvent, KernelEvents};

/**
 * Handles {@see Response} events for controllers extending the {@see Framework\Controller}.
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

    private bool $isHtmxRequest = false;

    /**
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1?: int}>|string>
     */
    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::CONTROLLER => ['parseController', 192],
            KernelEvents::RESPONSE   => ['parseResponse', 1_024],
            KernelEvents::TERMINATE  => ['responseCleanup', 1_024],
        ];
    }

    /**
     * @param ControllerEvent $event
     *
     * @return void
     */
    public function parseController( ControllerEvent $event ) : void
    {
        if ( \is_array( $event->getController() ) && $event->getController()[0] instanceof Framework\Controller ) {
            $this->controller    = $event->getController()[0]::class;
            $this->isHtmxRequest = $event->getRequest()->headers->has( 'hx-request' );

            $event->getRequest()->attributes->set( '_document_template', $this->getControllerTemplate() );
            $event->getRequest()->attributes->set(
                '_content_template',
                $this->getMethodTemplate( $event->getControllerReflector() ),
            );
        }
    }

    public function parseResponse( ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        if ( $this->isHtmxRequest ) {
            $this->document->add(
                [
                    'html.lang'   => 'en',
                    'html.id'     => 'top',
                    'html.theme'  => $this->document->get( 'theme.name' ) ?? 'system',
                    'html.status' => 'init',
                ],
            )
                ->add( 'meta.viewport', 'width=device-width,initial-scale=1' );

            if ( ! $this->document->isPublic ) {
                $this->document->set( 'robots', 'noindex, nofollow' );
                $this->headers->set( 'X-Robots-Tag', 'noindex, nofollow' );
            }
        }

        $html = new Document\ResponseRenderer(
            $this->isHtmxRequest,
            $this->document,
            $event->getResponse()->getContent(),
        );

        $event->getResponse()->setContent( $html );

        $this->responseHeaders( $event );

    }

    private function responseHeaders( ResponseEvent $event ) : void
    {
        // Always remove the identifying header
        \header_remove( 'X-Powered-By' );

        // Merge headers
        $event->getResponse()->headers->add( $this->headers->all() );

        $event->getResponse()->headers->set( 'Content-Type', 'text/html', false );

        if ( $this->isHtmxRequest ) {
            return;
        }

        // Document only headers

        if ( $this->document->isPublic ) {
            $event->getResponse()->headers->set( 'X-Robots-Tag', 'noindex, nofollow' );
        }

        // TODO : X-Robots
        // TODO : lang
        // TODO : cache
    }

    /**
     * - Commit unused {@see Toast} notifications.
     * - Commit deferred cache items.
     *
     * @param TerminateEvent $event
     *
     * @return void
     */
    public function responseCleanup( TerminateEvent $event ) : void {}

    private function handleNotifications() : string
    {
        $notifications = '';

        // $notifications = $this->notifications->getMessages();
        $flashBag = $this->serviceLocator( ToastService::class );

        foreach ( $flashBag->getMessages() as $message ) {
            $notification = new Notification(
                $message->type,
                $message->title,
                $message->description,
                $message->timeout,
            );

            if ( ! $notification->description ) {
                $notification->attributes->add( 'class', 'compact' );
            }

            if ( ! $notification->timeout && 'error' !== $notification->type ) {
                // $notification->setTimeout( Settings::get( 'notification.timeout' ) ?? 5_000 );
                $notification->setTimeout( 5_000 );
            }

            $notifications .= (string) $notification;
        }

        return $notifications;
    }

    private function getMethodTemplate( ReflectionMethod|ReflectionFunctionAbstract $method ) : ?string
    {
        $attribute = $method->getAttributes( Template::class, ReflectionAttribute::IS_INSTANCEOF )[0] ?? null;

        return $attribute ? $attribute->getArguments()[0] ?? null : null;
    }

    private function getControllerTemplate() : ?string
    {
        try {
            $attribute = ( new ReflectionClass( $this->controller ) )->getAttributes( Template::class )[0] ?? null;
        }
        catch ( ReflectionException $e ) {
            Log::exception( $e );
            return null;
        }

        return $attribute ? $attribute->getArguments()[0] ?? null : null;
    }
}
