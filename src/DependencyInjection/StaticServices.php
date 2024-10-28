<?php

namespace Core\DependencyInjection;

use Exception;
use Northrook\Exception\E_Value;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class StaticServices
{
    private static ?self $static = null;

    public function __construct( private readonly ServiceLocator $serviceLocator )
    {
        if ( $this::$static ) {
            return;
        }
    }

    /**
     * Initialize the {@see StaticServices} {@see ServiceLocator} on every request.
     */
    public function onKernelRequest() : void
    {
        $this::$static = $this;
    }

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

    public function onKernelTerminate() : void
    {
        dump( $this::class.' was destroyed' );
        $this::$static = null;
    }
}
