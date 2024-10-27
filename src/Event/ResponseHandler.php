<?php

namespace Core\Event;

use Core\Controller\Attribute\Template;
use Core\DependencyInjection\{CoreController, ServiceContainer};
use Core\Response\Document;
use Core\Service\{DocumentService, Request, Toast};
use Northrook\HTML\Element;
use Northrook\HTML\Element\Attributes;
use Northrook\UI\Component\Notification;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event\ControllerEvent,
    Event\ResponseEvent,
    Event\TerminateEvent,
    KernelEvents};
use const Support\{EMPTY_STRING, WHITESPACE};

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

    private bool $isHtmxRequest = false;

    public function __construct(
        private readonly Toast $notifications,
    ) {}

    /**
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1?: int}>|string>
     */
    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::CONTROLLER => ['parseController', -100],
            KernelEvents::RESPONSE   => ['parseResponse', 1_024],
            KernelEvents::RESPONSE   => ['preserveNotifications', 1_064],
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
        if ( \is_array( $event->getController() ) && $event->getController()[0] instanceof CoreController ) {
            $this->controller    = $event->getController()[0]::class;
            $this->isHtmxRequest = $event->getRequest()->headers->has( 'hx-request' );
            $event->getRequest()->attributes->add( $this->resolveResponseTemplate( $event ) );
        }
    }

    public function parseResponse( ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        // If we have made it this far, we can safely assume we are either sending content HTML as a HTMX response
        // or we are sending a full document HTML. Either way we need to first append/prepend to ->content.

        $response = $event->getResponse();

        $content = $this->handleNotifications();
        $content .= (string) $event->getResponse()->getContent();

        // Contentful HTML response
        if ( $this->isHtmxRequest ) {

            $this->document()
                ->title()
                ->meta( 'document' )
                ->meta( 'robots' )
                ->meta( 'theme' )
                ->assets( 'font' )
                ->assets( 'script' )
                ->assets( 'style' )
                ->assets( 'link' );

            $html = $this->document()->head;

            $html[] = $content;

            $content = \implode( PHP_EOL, $html );
        }
        // Full Document HTML response
        else {

            $this->document->add(
                [
                    'html.lang'   => 'en',
                    'html.id'     => 'top',
                    'html.theme'  => $this->document->get( 'theme.name' ) ?? 'system',
                    'html.status' => 'init',
                ],
            )
                ->add( 'meta.viewport', 'width=device-width,initial-scale=1' );

            $this->document()
                ->meta( 'meta.viewport' )
                ->title()
                ->meta( 'document' )
                ->meta( 'robots' )
                ->meta( 'theme' )
                ->meta( 'meta' )
                ->assets();

            $body = new Element( 'body', $this->serviceLocator( Document::class )->pull( 'body', [] ), $content );

            $htmlAttributes = $this->document->pull( 'html', null );
            $htmlAttributes = $htmlAttributes ? WHITESPACE.Attributes::from( $htmlAttributes ) : EMPTY_STRING;

            $html = [
                '<!DOCTYPE html>',
                "<html{$htmlAttributes}>",
                $this->document()->getHead(),
                $body->toString( PHP_EOL ),
                '</html>',
            ] ;

            $content = \implode( PHP_EOL, $html );

        }

        // $content = $this->contentHtml( $event->getResponse()->getContent() );

        // dd( $content );

        $this->responseHeaders( $event );

        $event->getResponse()->setContent( $content );
    }

    public function preserveNotifications( ResponseEvent $event ) : void
    {
        $flashBag = $event->getRequest()->getSession()->getFlashBag();

        foreach ( $this->notifications->getMessages() as $message ) {
            if ( ! \in_array( $message->message, $flashBag->peek( $message->type ) ) ) {
                $flashBag->add( $message->type, $message->message );
            }
        }
    }

    private function document() : DocumentService
    {
        return $this->serviceLocator( DocumentService::class );
    }

    private function responseHeaders( ResponseEvent $event ) : void
    {

        // Always remove the identifying header
        \header_remove( 'X-Powered-By' );

        // Merge headers
        $event->getResponse()->headers->add( $this->headers->all() );

        $event->getResponse()->headers->set( 'Content-Type', 'text/html', false );

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

    /**
     * @return array<int, string>
     */
    private function handleNotifications() : string
    {
        $notifications = '';

        // $notifications = $this->notifications->getMessages();
        $flashBag = $this->serviceLocator( Request::class )->flashBag();

        foreach ( $flashBag->all() as $type => $flashes ) {
            foreach ( $flashes as $flash ) {
                $this->notifications->setMessage( $type, $flash );
            }
        }

        foreach ( $this->notifications->pullMessages() as $toast ) {
            $notification = new Notification(
                $toast->type,
                $toast->message,
                $toast->description,
                $toast->timeout,
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
