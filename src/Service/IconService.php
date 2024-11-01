<?php

namespace Core\Service;

use Core\Service\IconService\IconPack;

final class IconService
{
    public function __construct(

    ) {}

    public function getIconPack( ?string $pack = null ) : IconPack
    {
        return new IconPack();
    }
}
