<?php

namespace Core\DependencyInjection;

use Core\Response\{Document, Parameters, Headers};
use Core\Service\{Pathfinder, Request};
use Core\Service\Security;
use Northrook\Latte;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

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
            'serviceLocator' => $this->serviceLocator( ServiceLocator::class ),
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
        return StaticServices::get( $get );
    }
}
