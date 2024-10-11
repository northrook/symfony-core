<?php

namespace Core\Service\AssetManager\Compiler;

use Northrook\JavaScriptMinifier;


class Script extends AssetCompiler
{
    protected function compile() : string
    {
        $generator = new JavaScriptMinifier( $this->sources );

        // Eventually minify using Uglify v3
        return $generator->minify();
    }
}