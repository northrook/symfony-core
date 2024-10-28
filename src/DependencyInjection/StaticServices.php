<?php

namespace Core\DependencyInjection;

use Exception;
use LogicException;
use Northrook\Exception\E_Value;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class StaticServices
{
    private static ?self $static = null;

    public function __construct( private readonly ServiceLocator $serviceLocator )
    {
        if ( $this::$static ) {
            dump( $this::class.' already set' );
        }
    }

    public function __invoke( RequestEvent $event ) : void
    {
        dump( $this::class.' has been set by '.__METHOD__ );
        $this::$static = $this;
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
            return $this::get( $get );
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
            throw new LogicException( message  : "The '".StaticServices::class."' does not provide access to the '".$get."' service.", code     : 500, previous : $exception );
        }
    }

    public function __destruct()
    {
        dump( $this::class.' was destroyed' );
        $this::$static = null;
    }
}
