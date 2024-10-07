<?php

namespace Core\Service\AssetManager\Asset;

use Northrook\Clerk;
use Northrook\Resource\Path;
use Support\ClassMethods;
use function String\hashKey;
use const Support\AUTO;

abstract class Asset
{
    use ClassMethods;

    public readonly string $type;      // stylesheet, script, image, etc

    public readonly string $assetID;   // manual or using hashKey

    public function __construct(
        protected array           $source,
        public readonly string    $label,
        protected readonly string $publicDirectory,
    ) {
        Clerk::event( $this::class, 'document' );
        $this->type    = $this->assetType();
        $this->assetID = hashKey( $this );
    }

    abstract protected function build() : void;

    abstract public function getStatic() : string;

    abstract public function getElement() : string;

    abstract public function getContent() ;

    /**
     * Points to the relative location of the asset.
     *
     * ```
     * ~root/public/
     *            ./assets/type/filename.ext?v=HASH
     * ```
     *
     * @return string
     * @param  ?string $version
     */
    final public function getPath( ?string $version = AUTO ) : string
    {
        return $this->relativeFilePath().'?v='.( $version ?? $this->assetID );
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

    final protected function publicFilePath() : string
    {
        return $this->publicDirectory.$this->relativeFilePath();
    }

    final protected function relativeFilePath() : string
    {
        $filetype = \strrchr( \end( $this->source ), '.' );
        return "/assets/{$this->type}/{$this->label}{$filetype}";
    }

    protected function assetType() : string
    {
        return \strtolower( $this->classBasename() );
    }
}