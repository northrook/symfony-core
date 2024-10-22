<?php

namespace Core\DependencyInjection;

use Exception;
use Northrook\Exception\{E_Value};
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @author Martin Nielsen <mn@northrook.com>
 */
trait ServiceContainer
{
    private readonly ServiceLocator $serviceLocator;

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