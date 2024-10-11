<?php

namespace Core\Service\AssetManager\Compiler;

use Northrook\StylesheetMinifier;

class Style extends AssetCompiler
{
    protected function compile() : string
    {
        $generator = new StylesheetMinifier( $this->sources );

        return $generator->minify();
    }
}