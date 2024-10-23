<?php

namespace Core\DependencyInjection;

use Core\Response\{Document, Parameters};
use Core\Service\{Headers, Request};
use Exception;
use Northrook\Exception\{E_Value};
use Northrook\Latte;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @property-read Request $request
 * @property-read Latte $latte
 * @property-read Document $document
 * @property-read Parameters $parameters
 * @property-read Headers $headers
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
trait ServiceContainer
{
    private readonly ServiceLocator $serviceLocator;

    public function __get( string $service )
    {
        return match ( $service ) {
            'request'    => $this->serviceLocator( Request::class ),
            'latte'      => $this->serviceLocator( Latte::class ),
            'document'   => $this->serviceLocator( Document::class ),
            'parameters' => $this->serviceLocator( Parameters::class ),
            'headers'    => $this->serviceLocator( Headers::class ),
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
            return $this->serviceLocator->get( $get );
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
        $this->serviceLocator = $serviceLocator;
    }
}
