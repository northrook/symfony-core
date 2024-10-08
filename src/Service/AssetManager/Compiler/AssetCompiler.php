<?php

namespace Core\Service\AssetManager\Compiler;

use Core\Service\AssetManager\Asset;
use Northrook\Clerk;
use Northrook\Filesystem\File;
use Northrook\Resource\Path;
use Support\ClassMethods;
use function String\hashKey;

abstract class AssetCompiler
{
    use ClassMethods;

    protected readonly array $sources;

    public readonly string $type;      // stylesheet, script, image, etc

    public readonly string $assetID;   // manual or using hashKey

    public readonly Asset $asset;

    public function __construct(
        array                     $sources,
        public readonly string    $label,
        protected readonly string $publicDirectory,
    ) {
        Clerk::event( $this::class, 'document' );
        $this->type    = $this->assetType();
        $this->assetID = hashKey( $this );

        $this->sources = $this->parseSources( $sources );

        File::save( $this->publicFilePath(), $this->compile() );

        $this->asset = new Asset(
            $this->assetID,
            $this->type,
            $this->label,
            $this->relativeFilePath(),
            $this->publicFilePath(),
            $this::class,
        );
    }

    abstract protected function compile() : string;

    protected function assetType() : string
    {
        return \strtolower( $this->classBasename() );
    }

    final protected function publicFilePath() : string
    {
        return $this->publicDirectory.$this->relativeFilePath();
    }

    final protected function relativeFilePath() : string
    {
        $filetype = \strrchr( \array_key_first( $this->sources ), '.', true );
        return "/{$this->type}/{$this->label}{$filetype}";
    }

    // TODO : If any provided source is a URL, fetch the external resource and cache it
    private function parseSources( array $sources ) : array
    {
        $array = [];

        foreach ( $sources as $value ) {
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