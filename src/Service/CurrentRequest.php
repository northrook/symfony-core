<?php

namespace Core\Service;

use Northrook\Trait\PropertyAccessor;
use Symfony\Component\HttpFoundation as Http;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @property Request $current
 * @property string  $routeName
 * @property string  $routeRoot
 * @property string  $pathInfo
 * @property string  $route
 * @property string  $method
 * @property string  $type
 * @property string  $controller
 * @property bool    $isHtmx
 * @property bool    $isJson
 * @property bool    $isHtml
 */
final readonly class CurrentRequest
{
    use PropertyAccessor;

    /**
     * Assigns the current {@see Request} from the {@see Http\RequestStack}, to {@see CurrentRequest::$current}.
     *
     * If no {@see Request} is found, one will be created from {@see $GLOBALS}, and pushed onto the empty {@see stack}
     *
     * @param Http\RequestStack   $stack
     * @param HttpKernelInterface $kernel
     */
    public function __construct(
        public Http\RequestStack $stack,
        private HttpKernelInterface $kernel,
    ) {
        if ( ! $this->stack->getCurrentRequest() ) {
            $this->stack->push( Request::createFromGlobals() );
        }
    }

    /**
     * Retrieves various values.
     *
     * - The call is cached either in this {@see $this::class}, or natively in the Symfony {@see Request}.
     *
     * @param string $property
     *
     * @return bool|string
     */
    public function __get( string $property ) : string|bool
    {
        return match ( $property ) {
            'current'    => $this->currentRequest(),
            'route'      => $this->route(),
            'routeName'  => $this->routeName(),
            'routeRoot'  => $this->routeRoot(),
            'pathInfo'   => $this->currentRequest()->getPathInfo(),
            'method'     => $this->currentRequest()->getMethod(),
            'controller' => $this->requestController(),
            'type'       => $this->type(),
            'isHtmx'     => $this->type( 'htmx' ),
            'isJson'     => $this->type( 'json' ),
            'isHtml'     => $this->type( 'html' ),
        };
    }

    /**
     * Public access via magic {@see CurrentRequest::$current};.
     *
     * @return Request
     */
    private function currentRequest() : Request
    {
        return $this->stack->getCurrentRequest();
    }

    /**
     * @param ?string $get {@see Http\HeaderBag::get} Returns null if the header is not set
     * @param ?string $has {@see Http\HeaderBag::has} Checks if the headerBag contains the header
     *
     * @return null|bool|Http\HeaderBag|string
     */
    public function headerBag( ?string $get = null, ?string $has = null ) : Http\HeaderBag|string|bool|null
    {
        if ( ! $get && ! $has ) {
            return $this->currentRequest()->headers;
        }

        return $get ? $this->currentRequest()->headers->get( $get ) : $this->currentRequest()->headers->has( $has );
    }

    /**
     * @param ?string $get {@see  SessionInterface::get}
     *
     * @return FlashBagAwareSessionInterface|mixed
     */
    public function session( ?string $get = null ) : mixed
    {
        try {
            return $get ? $this->currentRequest()->getSession()->get( $get ) : $this->currentRequest()->getSession();
        }
        catch ( Http\Exception\SessionNotFoundException $exception ) {
            throw new Http\Exception\LogicException( message  : 'Sessions are disabled. Enable them in "config/packages/framework".', previous : $exception);
        }
    }

    /**
     * @param ?string $get {@see Http\Request::get}
     *
     * @return null|array|bool|float|Http\ParameterBag|int|string
     */
    public function parameter( ?string $get = null ) : Http\ParameterBag|array|string|int|bool|float|null
    {
        return $get ? $this->currentRequest()->get( $get ) : $this->currentRequest()->attributes;
    }

    public function attributes( ?string $get = null ) : Http\ParameterBag|array|string|int|bool|float|null
    {
        return $get ? $this->currentRequest()->attributes->get( $get ) : $this->currentRequest()->attributes;
    }

    /**
     * @param ?string $get {@see  InputBag::get}
     *
     * @return null|bool|float|Http\InputBag|int|string
     */
    public function query( ?string $get = null ) : Http\InputBag|string|int|float|bool|null
    {
        return $get ? $this->currentRequest()->query->get( $get ) : $this->currentRequest()->query;
    }

    /**
     * @param ?string $get {@see Http\InputBag::get}
     *
     * @return null|bool|float|Http\InputBag|int|string
     */
    public function cookies( ?string $get = null ) : Http\InputBag|string|int|float|bool|null
    {
        return $get ? $this->currentRequest()->cookies->get( $get ) : $this->currentRequest()->cookies;
    }

    public function flashBag() : FlashBagInterface
    {
        return $this->session()->getFlashBag();
    }

    public function httpKernel() : HttpKernelInterface
    {
        return $this->kernel;
    }

    /**
     * Return the current requestType, or match against it.
     *
     * - Pass `null` to return the current requestType as string
     *
     * @param ?string $is
     *
     * @return bool|string
     */
    private function type( ?string $is = null ) : bool|string
    {
        static $requestType;

        $requestType
                ??= $this->headerBag( get : 'hx-request' )
                       ?? $this->headerBag( get : 'content-type' )
                       ?? 'text/html; charset=utf-8';

        return $is ? $is === $requestType : $requestType;
    }

    /**
     * Resolve and cache the current route key.
     *
     * @return string
     */
    private function route() : string
    {
        static $route;
        return $route ??= $this->currentRequest()->attributes->get( 'route' ) ?? '';
    }

    /**
     * Resolve and cache the current route name.
     *
     * @return string
     */
    private function routeName() : string
    {
        static $routeName;
        return $routeName ??= $this->currentRequest()->get( '_route' ) ?? '';
    }

    /**
     * Resolve and cache the current route root name.
     *
     * @return string
     */
    private function routeRoot() : string
    {
        return \strstr( $this->routeName(), ':', true );
    }

    /**
     * Resolve and cache the controller and method for this request.
     *
     * @return string
     */
    private function requestController() : string
    {
        static $controller;
        return $controller ??= ( \is_array( $controller = $this->parameter( '_controller' ) ) )
                ? \implode( '::', $controller )
                : $controller;
    }
}