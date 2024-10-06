<?php

namespace Core\Service\AssetManager\Asset;

use Northrook\Resource\Path;
use Support\ClassMethods;

abstract class Asset
{
    use ClassMethods;

    public readonly string $type;      // stylesheet, script, image, etc

    public readonly string $assetID;   // manual or using hashKey

    public function __construct(
        protected array        $source,
        public readonly string $label,
    ) {}

    /**
     * @return $this
     */
    public function compile() : Asset
    {
        $this->source = $this->parseSources();

        return $this;
    }

    private function parseSources( ?array $sources = null ) : array
    {
        $array = [];

        foreach ( $sources ?? $this->source as $value ) {
            if ( \is_array( $value ) ) {
                $array = [...$array, ...$this->parseSources( $value )];
            }
            else {
                $source               = new Path( $value );
                $array[$source->path] = $source;
            }
        }
        return $array;
    }
}