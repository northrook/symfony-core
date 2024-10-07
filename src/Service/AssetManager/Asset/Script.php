<?php

namespace Core\Service\AssetManager\Asset;

use Northrook\Filesystem\File;
use Northrook\HTML\Element;

class Script extends Asset
{
    protected function build() : void
    {
        $generator = new \MatthiasMullie\Minify\JS();

        foreach ( $this->source as $path ) {
            $generator->add( $path );
        }

        File::save( $this->publicFilePath(), $generator->minify() );
    }

    public function getContent() : string
    {
        return File::read( $this->publicFilePath() );
    }

    public function getStatic() : string
    {
        return (string) new Element( 'script', [
            'id'         => $this->assetID,
            'data-asset' => $this->label,
            'src'        => $this->getPath(),
        ] );
    }

    public function getElement() : string
    {
        return (string) new Element(
            'script',
            [
                'id'         => $this->assetID,
                'data-asset' => $this->label,
            ],
            $this->getContent(),
        );
    }
}
