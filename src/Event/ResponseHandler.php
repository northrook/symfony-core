<?php

namespace Core\Event;

use Core\Response\Document;
use Core\Service\{DocumentService, Request};
use Core\Model\{Message};
use Core\Response\Attribute\Template;
use Core\Settings;
use Core\DependencyInjection\{CoreController, ServiceContainer};
use Northrook\HTML\Element;
use Northrook\HTML\Element\Attributes;
use Northrook\UI\Component\Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event\ControllerEvent, Event\ResponseEvent, KernelEvents};
use Symfony\Component\HttpFoundation\RequestStack;
use ReflectionAttribute;
use ReflectionClass;
use function Support\toString;
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
        // private RequestStack $requestStack,
    ) {}

    /**
     * @return array<string, array{0: string, 1: int}|list<array{0: string, 1?: int}>|string>
     */
    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::CONTROLLER => ['parseController', -100],
            KernelEvents::RESPONSE   => ['parseResponse', 1_024],
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

    public function prepareResponse( ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
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

        $content = (string) $event->getResponse()->getContent();

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

            foreach ( $this->flashBagMessages() as $message ) {
                $html[] = $message;
            }

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
                ->assets( 'script' )
                ->assets( 'style' )
                ->assets( 'link' );

            $toasts = \implode( PHP_EOL, $this->flashBagMessages() );
            $body   = new Element( 'body', $this->serviceLocator( Document::class )->pull( 'body', [] ), [$toasts, $content] );

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
        $event->getResponse()->headers->add( $this->headers->response->all() );

        $event->getResponse()->headers->set( 'Content-Type', 'text/html', false );

        // TODO : X-Robots
        // TODO : lang
        // TODO : cache
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
