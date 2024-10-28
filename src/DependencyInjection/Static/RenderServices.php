<?php

namespace Core\DependencyInjection\Static;

use Core\DependencyInjection\StaticServices;
use Core\Service\IconService;
use Core\Service\IconService\IconPack;
use Core\UI\RenderRuntime;

trait RenderServices
{
    final protected function iconPack( ?string $name = null ) : IconPack
    {
        return StaticServices::get( IconService::class )->getIconPack( $name );
    }

    final protected function registerInvocation( string $className ) : void
    {
        StaticServices::get( RenderRuntime::class )->registerInvocation( $className );
    }
}
