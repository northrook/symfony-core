<?php

namespace Core\Service\AssetManager\Asset;

use Northrook\Clerk;
use Northrook\Resource\Path;
use Support\ClassMethods;
use function String\hashKey;
use Stringable;

abstract class Asset implements Stringable
{
    use ClassMethods;

    public readonly string $type;      // stylesheet, script, image, etc

    public readonly string $assetID;   // manual or using hashKey

    public function __construct(
        protected array        $source,
        public readonly string $label,
    ) {
        Clerk::event( $this::class, 'document' );
        $this->type    = $this->assetType();
        $this->assetID = hashKey( $this );
        dump( 'Generating asset ID: '.$this->assetID.' from object:', $this );
    }

    /**
     * @param array $args
     *
     * @return string
     */
    final public function getHtml( array $args = [] ) : string
    {
        Clerk::event( $this::class )->stop();
        return $this->__toString();
    }

    /**
     * @return $this
     */
    final public function compile() : Asset
    {
        $this->source = $this->parseSources();

        $this->build();

        return $this;
    }

    abstract protected function build() : void;

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

    protected function assetType() : string
    {
        return \strtolower( $this->classBasename() );
    }
}