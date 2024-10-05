<?php

namespace Core\DependencyInjection;

use Exception;
use Northrook\Exception\{E_Value};
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
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
        if ( ServiceLocator::class === $get ) {
            return $this->serviceLocator;
        }
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
}