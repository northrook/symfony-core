<?php

namespace Core\Service\AssetManager\Compiler;

use Northrook\Filesystem\File;
use Northrook\HTML\Element;

class Style extends AssetCompiler
{
    protected function compile() : string
    {
        $generator = new \MatthiasMullie\Minify\CSS();

        foreach ( $this->sources as $path ) {
            $generator->add( $path );
        }

        return $generator->minify();
    }
}