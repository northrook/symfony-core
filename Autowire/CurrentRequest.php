<?php

namespace Northrook\Symfony\Autowire;

use Northrook\Core\Trait\PropertyAccessor;
use Symfony\Component\HttpFoundation as Http;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * @property-read string $routeName
 * @property-read string $routeRoot
 * @property-read string $pathInfo
 * @property-read string $route
 * @property-read string $method
 * @property-read string $type
 * @property-read bool   $isHypermedia
 * @property-read bool   $isJson
 * @property-read bool   $isHtml
 */
final readonly class CurrentRequest
{
    use PropertyAccessor;

    private string $requestType;
    private string $routeName;
    private string $routeRoot;
    private string $pathInfo;
    private string $route;
    private string $method;

    public Http\Request $current;

    /**
     * Assigns the current {@see Http\Request} from the {@see Http\RequestStack}, to {@see CurrentRequest::$current}.
     *
     * If no {@see request} is found, one will be created from {@see $GLOBALS}, and pushed onto the empty {@see stack}
     *
     * @param Http\RequestStack  $stack
     */
    public function __construct(
        public Http\RequestStack $stack,
    ) {
        if ( !$this->stack->getCurrentRequest() ) {
            $this->stack->push( Http\Request::createFromGlobals() );
        }

        $this->current = $this->stack->getCurrentRequest();
    }

    /**
     * Retrieves various values.
     *
     * - The call is cached either in this {@see $this::class}, or natively in the Symfony {@see Http\Request}.
     *
     * @param string  $property
     *
     * @return string|bool
     */
    public function __get( string $property ) : string | bool {
        return match ( $property ) {
            'route'        => $this->route(),
            'routeName'    => $this->routeName(),
            'routeRoot'    => $this->routeRoot(),
            'pathInfo'     => $this->current->getPathInfo(),
            'method'       => $this->current->getMethod(),
            'type'         => $this->type(),
            'isHypermedia' => $this->type( 'hypermedia' ),
            'isJson'       => $this->type( 'json' ),
            'isHtml'       => $this->type( 'html' ),
        };
    }

    /**
     * @param ?string  $get  {@see Http\HeaderBag::get} Returns null if the header is not set
     * @param ?string  $has  {@see Http\HeaderBag::has} Checks if the headerBag contains the header
     *
     * @return null|Http\HeaderBag|string|bool
     */
    public function headerBag( ?string $get = null, ?string $has = null ) : Http\HeaderBag | string | bool | null {

        if ( !$get && !$has ) {
            return $this->current->headers;
        }

        return $get ? $this->current->headers->get( $get ) : $this->current->headers->has( $has );
    }

    /**
     * @param ?string  $get  {@see  SessionInterface::get}
     *
     * @return FlashBagAwareSessionInterface|mixed
     */
    public function session( ?string $get = null ) : mixed {
        try {
            return $get ? $this->current->getSession()->get( $get ) : $this->current->getSession();
        }
        catch ( Http\Exception\SessionNotFoundException $exception ) {
            throw new Http\Exception\LogicException(
                message  : 'Sessions are disabled. Enable them in "config/packages/framework".',
                previous : $exception,
            );
        }
    }

    /**
     * @param  ?string  $get  {@see Http\Request::get}
     *
     * @return Http\ParameterBag|array|string|int|bool|float|null
     */
    public function parameter( ?string $get = null ) : Http\ParameterBag | array | string | int | bool | float | null {
        return $get ? $this->current->get( $get ) : $this->current->attributes;
    }

    public function attributes( ?string $get = null ) : Http\ParameterBag | array | string | int | bool | float | null {
        return $get ? $this->current->attributes->get( $get ) : $this->current->attributes;
    }

    /**
     * @param ?string  $get  {@see  InputBag::get}
     *
     * @return Http\InputBag|string|int|float|bool|null
     */
    public function query( ?string $get = null ) : Http\InputBag | string | int | float | bool | null {
        return $get ? $this->current->query->get( $get ) : $this->current->query;
    }

    /**
     * @param ?string  $get  {@see Http\InputBag::get}
     *
     * @return Http\InputBag|string|int|float|bool|null
     */
    public function cookies( ?string $get = null ) : Http\InputBag | string | int | float | bool | null {
        return $get ? $this->current->cookies->get( $get ) : $this->current->cookies;
    }

    public function flashBag() : FlashBagInterface {
        return $this->session()->getFlashBag();
    }

    /**
     * Return the current requestType, or match against it.
     *
     * - Pass `null` to return the current requestType as string
     *
     * @param ?string  $is
     *
     * @return bool|string
     */
    private function type( ?string $is = null ) : bool | string {

        $this->requestType ??= $this->headerBag( get : 'hx-request' ) ?? $this->headerBag( get : 'content-type' );

        return $is ? $is === $this->requestType : $this->requestType;
    }

    /**
     * Resolve and cache the current route key
     *
     * @return string
     */
    private function route() : string {
        return $this->route ??= $this->current->attributes->get( 'route' ) ?? '';
    }

    /**
     * Resolve and cache the current route name
     *
     * @return string
     */
    private function routeName() : string {
        return $this->routeName ??= $this->current->get( '_route' ) ?? '';
    }

    /**
     * Resolve and cache the current route root name
     *
     * @return string
     */
    private function routeRoot() : string {
        return strstr( $this->routeName(), ':', true );
    }

}