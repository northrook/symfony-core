<?php

namespace Core;

use Northrook\ArrayStore;
use Northrook\Trait\{SingletonClass};

final class Settings extends ArrayStore
{
    use SingletonClass;

    /**
     * @param string $storageDirectory
     */
    public function __construct(
        string $storageDirectory,
    ) {
        $this->instantiationCheck();
        parent::__construct( $this::class, $storageDirectory );
        $this::$instance = $this;
    }

    public static function __callStatic( string $method, array $arguments )
    {
        return match ( $method ) {
            'get'   => Settings::$instance->get( $arguments[0] ),
            default => null,
        };
    }

    /**
     * @param array<array-key, mixed> $settings
     *
     * @return void
     */
    final public function setDefault( array $settings ) : void
    {
        $this->arrayValue( $settings, true );
    }
}
