<?php

namespace Core\DependencyInjection;

use Exception;
use Northrook\Exception\E_Value;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class StaticServices
{
    private static ?self $static = null;

    public function __construct( private readonly ServiceLocator $serviceLocator ) {}

    /**
     * @template Service
     *
     * @param class-string<Service> $get
     *
     * @return Service
     */
    public static function get( string $get ) : mixed
    {
        if ( ServiceLocator::class === $get ) {
            return self::$static->serviceLocator;
        }

        try {
            return self::$static->serviceLocator->get( $get );
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
     * Initialize the {@see StaticServices} {@see ServiceLocator} on every request.
     */
    public function onKernelRequest() : void
    {
        $this::$static ??= $this;
    }

    /**
     * Clear the {@see StaticServices} instance for next request.
     */
    public function onKernelTerminate() : void
    {
        $this::$static = null;
    }
}
