<?php

namespace Core\Service\AssetManager\Compiler;

class Script extends AssetCompiler
{
    protected function compile() : string
    {
        $generator = new \MatthiasMullie\Minify\JS();

        foreach ( $this->sources as $path ) {
            $generator->add( $path );
        }

        // Eventually minify using Uglify v3
        return $generator->minify();
    }
}