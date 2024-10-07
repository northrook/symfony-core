<?php

namespace Core\Service\AssetManager\Asset;

use Northrook\Filesystem\File;
use Northrook\HTML\Element;

class Link extends Asset
{
    public bool $inline = false;

    protected function build() : void
    {
        $generator = new \MatthiasMullie\Minify\CSS();

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
        return (string) new Element( 'link', [
                'id'         => $this->assetID,
                'data-asset' => $this->label,
                'rel'        => 'stylesheet',
                'href'        => $this->getPath(),
        ] );
    }

    public function getElement() : string
    {
        return (string) new Element(
                'style',
                [
                        'id'         => $this->assetID,
                        'data-asset' => $this->label,
                ],
                $this->getContent(),
        );
    }
}