<?php

namespace Core\Event;

use Core\Response\Document;
use Core\Model\{DocumentParser, Message};
use Core\Response\Attribute\Template;
use Core\Service\Request;
use Core\Settings;
use Core\DependencyInjection\{CoreController, ServiceContainer};
use Northrook\UI\Component\Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event, Event\ControllerEvent, KernelEvents};
use Symfony\Component\HttpFoundation\RequestStack;
use ReflectionAttribute;
use ReflectionClass;
use function Support\toString;

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
        // private RequestStack $requestStack,
    ) {}

    /**
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1?: int}>|string>
     */
    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::CONTROLLER => ['parseController', -100],
            KernelEvents::RESPONSE   => [
                ['prepareResponse'],
                ['parseResponseContent', 1_024],
            ],
        ];
    }

    /**
     * @param ControllerEvent $event
     *
     * @return void
     */
    public function parseController( ControllerEvent $event ) : void
    {
        if ( \is_array( $event->getController() ) && $event->getController()[0] instanceof CoreController ) {
            $this->controller = $event->getController()[0]::class;
            $event->getRequest()->attributes->add( $this->resolveResponseTemplate( $event ) );
        }
    }

    public function prepareResponse( Event\ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        // Always remove the identifying header
        \header_remove( 'X-Powered-By' );

        // Merge headers
        $event->getResponse()->headers->add( $this->headers->response->all() );
    }

    public function parseResponseContent( Event\ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        $head = new DocumentParser( $this->serviceLocator( Document::class ) );

        // If we have made it this far, we can safely assume we are either sending content HTML as a HTMX response
        // or we are sending a full document HTML. Either way we need to first append/prepend to ->content.

        $content = $this->contentHtml( $event->getResponse()->getContent() );

        dd( $content );

        $event->getResponse()->setContent( $content );
    }

    private function contentHtml( ?string $content ) : string
    {
        $html = [];

        foreach ( $this->flashBagMessages() as $message ) {
            $html[] = $message;
        }

        dump( $html );

        return $content;
    }

    /**
     * @return array<int, string>
     */
    private function flashBagMessages() : array
    {
        $messages = [];

        foreach ( $this->serviceLocator( Request::class )->flashBag()->all() as $type => $flash ) {
            foreach ( $flash as $message ) {
                $notification = $message instanceof Message ? new Notification(
                    $message->type,
                    $message->message,
                    $message->description,
                    $message->timeout,
                ) : new Notification( $type, toString( $message ) );

                if ( ! $notification->description ) {
                    $notification->attributes->add( 'class', 'compact' );
                }

                if ( ! $notification->timeout && 'error' !== $notification->type ) {
                    // $notification->setTimeout( Settings::get( 'notification.timeout' ) ?? 5_000 );
                    $notification->setTimeout( 5_000 );
                }

                $messages[] = (string) $notification;
            }
        }

        return $messages;
    }

    /**
     * TODO : Cache this.
     *
     * @param ControllerEvent $event
     *
     * @return array{_document_template: ?string, _content_template: ?string}
     */
    private function resolveResponseTemplate( ControllerEvent $event ) : array
    {
        $method = $event->getControllerReflector();

        $attribute = $method->getAttributes( Template::class, ReflectionAttribute::IS_INSTANCEOF )[0]
                     ?? ( new ReflectionClass( $event->getController() ) )->getAttributes(
                         Template::class,
                     )[0] ?? null;

        return $attribute ? [
            '_document_template' => $attribute->getArguments()[0] ?? null,
            '_content_template'  => $attribute->getArguments()[1] ?? null,
        ] : [];
    }
}