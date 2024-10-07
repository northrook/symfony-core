<?php

namespace Core\Service\AssetManager\Asset;

class Script extends Asset
{
    protected function build() : void
    {
        dump( $this->source );
    }

    public function __toString() : string
    {
        return __CLASS__;
    }
}
