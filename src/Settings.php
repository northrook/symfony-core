<?php

namespace Core;

use Northrook\ArrayStore;
use Northrook\Trait\{SingletonClass};

final class Settings
{
    use SingletonClass;

    private readonly ArrayStore $settings;

    /**
     * @param string $storageDirectory
     */
    public function __construct(
        string $storageDirectory,
    ) {
        $this->instantiationCheck();
        $this->settings  = new ArrayStore( $this::class, $storageDirectory );
        $this::$instance = $this;
    }

    public static function get( ?string $name = null ) : mixed
    {
        return $name ? Settings::$instance->get( $name ) : Settings::$instance->settings;
    }

    /**
     * @param array<array-key, mixed> $settings
     *
     * @return void
     */
    final public function setDefault( array $settings ) : void
    {
        $this->settings->setDefault( $settings );
    }
}