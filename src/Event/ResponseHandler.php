<?php

namespace Core\Event;

use Core\Response\Attribute\Template;
use Core\Service\{Request};
use Core\DependencyInjection\{CoreController, ServiceContainer};
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\{Event, Event\ControllerEvent, Event\FinishRequestEvent, KernelEvents};
use Symfony\Component\HttpFoundation\RequestStack;
use ReflectionAttribute;
use ReflectionClass;

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
        // dd( __METHOD__);
        return [
            KernelEvents::CONTROLLER     => ['onKernelController', -100],
            KernelEvents::RESPONSE       => ['onKernelResponse'],
            KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest'],
        ];
    }


    public function onKernelController( ControllerEvent $event ) : void
    {
        if ( \is_array( $event->getController() ) && $event->getController()[0] instanceof CoreController ) {
            $this->controller = $event->getController()[0]::class;
        }

        $event->getRequest()->attributes->add( $this->resolveResponseTemplate( $event ) );
        echo __METHOD__ . PHP_EOL;
    }

    public function onKernelResponse( Event\ResponseEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;

        }
        // Always remove the identifying header
        \header_remove( 'X-Powered-By' );

        // Merge headers
        $event->getResponse()->headers->add( $this->headers->response->all() );
        echo __METHOD__ . PHP_EOL;
    }

    public function onKernelFinishRequest( FinishRequestEvent $event ) : void
    {
        // Bail if the controller doesn't pass validation
        if ( ! $this->controller ) {
            return;
        }

        // !! Render the Document here

        echo __METHOD__ . PHP_EOL;
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
                     ?? ( new ReflectionClass( $event->getController() ) )->getAttributes( Template::class )[0] ?? null;

        return [
                '_document_template' => $attribute->getArguments()[0] ?? null,
                '_content_template'  => $attribute->getArguments()[1] ?? null,
        ];
    }
}