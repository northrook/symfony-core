<?php

namespace Core\Event;

use Core\Framework\DependencyInjection\ServiceContainer;
use Core\Response\ResponseRenderer;
use Symfony\Component\HttpFoundation\Request;
use Core\Framework\Response\{Document, Headers, Parameters};
use Northrook\Latte;
use Northrook\Logger\Log;
use ReflectionFunctionAbstract;
use Core\Framework;
use Core\Framework\Controller\Template;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ReflectionException;
use ReflectionMethod;
use InvalidArgumentException;
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

    protected string $content;

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

    protected function request() : Request
    {
        return $this->serviceLocator( Request::class );
    }

    protected function document() : Document
    {
        return $this->serviceLocator( Document::class );
    }

    protected function headers() : Headers
    {
        return $this->serviceLocator( Headers::class );
    }

    protected function parameters() : Parameters
    {
        return $this->serviceLocator( Parameters::class );
    }

    private function content( ResponseEvent $event ) : void
    {
        $this->content = $event->getResponse()->getContent();

        if ( $this->contentIsTemplate() ) {

            $this->parameters()->set( 'content', $this->request()->attributes->get( '_content_template' ) );

            $this->content = $this->serviceLocator( Latte::class )->templateToString(
                    $this->request()->attributes->get( '_document_template' ),
                    $this->parameters()->getParameters(),
            ) ;
        }
    }

    public function parseResponse( ResponseEvent $event ) : void
    {
        // We will receive either raw HTML, a .latte template, or null; indicating we use a controller::method template

        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        $this->content( $event );

        if ( $this->isHtmxRequest ) {
            $this->document()->add(
                    [
                            'html.lang'   => 'en',
                            'html.id'     => 'top',
                            'html.theme'  => $this->document()->get( 'theme.name' ) ?? 'system',
                            'html.status' => 'init',
                    ],
            )
                 ->add( 'meta.viewport', 'width=device-width,initial-scale=1' );

            if ( ! $this->document()->isPublic ) {
                $this->document()->set( 'robots', 'noindex, nofollow' );
                $this->headers()->set( 'X-Robots-Tag', 'noindex, nofollow' );
            }
        }

        $html = new ResponseRenderer(
                $this->isHtmxRequest,
                $this->document(),
                $this->content,
                $this->serviceLocator,
        );

        $event->getResponse()->setContent( $html );

        $this->responseHeaders( $event );

    }

    private function responseHeaders( ResponseEvent $event ) : void
    {
        // Always remove the identifying header
        // \header_remove( 'X-Powered-By' );

        // Merge headers
        $event->getResponse()->headers->add( $this->headers()->all() );

        $event->getResponse()->headers->set( 'Content-Type', 'text/html', false );

        if ( $this->isHtmxRequest ) {
            return;
        }

        // Document only headers

        if ( $this->document()->isPublic ) {
            $event->getResponse()->headers->set( 'X-Robots-Tag', 'noindex, nofollow' );
        }

        // TODO : X-Robots
        // TODO : lang
        // TODO : cache
    }

    /**
     * - Commit deferred cache items.
     *
     * @param TerminateEvent $event
     *
     * @return void
     */
    public function responseCleanup( TerminateEvent $event ) : void {}

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

    /**
     * Determine if the {@see \Symfony\Component\HttpFoundation\Response} `$content` is a template.
     *
     * - Empty `$content` will use {@see Framework\Controller} attribute templates.
     * - If the `$content` contains no whitespace, and ends with `.latte`, it is a template
     * - All other strings will be considered as `text/plain`
     *
     * @param ?string $content
     *
     * @return bool
     */
    private function contentIsTemplate( ?string $content = null ) : bool
    {
        $content ??= $this->content ?? throw new InvalidArgumentException( __METHOD__.': No content string available.' );

        // If the string is empty, use Controller attributes
        if ( ! $content ) {
            return true;
        }

        // Any whitespace and we can safely assume it not a template string
        if ( \str_contains( $content, ' ' ) ) {
            return false;
        }

        return (bool) ( \str_ends_with( $content, '.latte' ) );
    }
}
