<?php

namespace Core\Event;

use Northrook\Logger\Log;
use ReflectionFunctionAbstract;
use Core\DependencyInjection\{ServiceContainer};
use Core\Framework;
use Core\Framework\Controller\Template;
use Core\Response\{Document};
use Core\Service\{DocumentService, ToastService};
use Core\UI\Component\Notification;
use Northrook\HTML\Element;
use Northrook\HTML\Element\Attributes;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpKernel\{Event\ControllerEvent, Event\ResponseEvent, Event\TerminateEvent, KernelEvents};
use const Support\{EMPTY_STRING, WHITESPACE};

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
            KernelEvents::CONTROLLER => ['parseController', -100],
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
            $event->getRequest()->attributes->set( '_content_template', $this->getMethodTemplate( $event->getControllerReflector() ) );
        }
    }

    public function parseResponse( ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        $render = new Document\ResponseRenderer(
            $this->isHtmxRequest,
            $this->document,
            $event->getResponse()->getContent(),
        );

        dump( $render );
        // If we have made it this far, we can safely assume we are either sending content HTML as a HTMX response
        // or we are sending a full document HTML. Either way we need to first append/prepend to ->content.

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

            if ( ! $this->document->isPublic ) {
                $this->document->set( 'robots', 'noindex, nofollow' );
                $this->headers->set( 'X-Robots-Tag', 'noindex, nofollow' );
            }
            // public robots

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
