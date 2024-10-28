<?php

namespace Core\DependencyInjection;

use Core\Response\{Document, Parameters, Headers};
use Core\Service\{Pathfinder, Request};
use Core\Service\Security;
use Exception;
use Northrook\Exception\{E_Value};
use Northrook\Latte;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @property-read Request                $request
 * @property-read Pathfinder             $pathfinder
 * @property-read Latte                  $latte
 * @property-read Document               $document
 * @property-read Parameters             $parameters
 * @property-read Headers                $headers
 * @property-read Security $security
 * @property-read UrlGeneratorInterface  $urlGenerator
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
trait ServiceContainer
{
    // private readonly ServiceLocator $serviceLocator;
    private static ServiceLocator $__serviceLocator;

    public function __get( string $service )
    {
        return match ( $service ) {
            'request'      => $this->serviceLocator( Request::class ),
            'pathfinder'   => $this->serviceLocator( Pathfinder::class ),
            'latte'        => $this->serviceLocator( Latte::class ),
            'document'     => $this->serviceLocator( Document::class ),
            'parameters'   => $this->serviceLocator( Parameters::class ),
            'headers'      => $this->serviceLocator( Headers::class ),
            'security'     => $this->serviceLocator( Security::class ),
            'urlGenerator' => $this->serviceLocator( RouterInterface::class ),
        };
    }

    /**
     * @template Service
     *
     * @param class-string<Service> $get
     *
     * @return Service
     */
    final protected function serviceLocator( string $get ) : mixed
    {
        try {
            // return $this->serviceLocator->get( $get );
            return $this::$__serviceLocator->get( $get );
        }
        catch ( Exception $exception ) {
            return E_Value::error(
                'The {ServiceContainer} does not provide access to the {GetService} service.',
                ['ServiceContainer' => ServiceContainer::class, 'GetService' => $get],
                $exception,
                true,
            );
        }
    }

    /**
     * @internal
     *
     * @param ServiceLocator $serviceLocator
     *
     * @return void
     */
    #[Required]
    final public function setServiceLocator( ServiceLocator $serviceLocator ) : void
    {
        dump( __METHOD__ );
        $this::$__serviceLocator ??= $serviceLocator;
    }

    public function __destruct()
    {
        dump( $this, $this::$__serviceLocator );
    }
}
