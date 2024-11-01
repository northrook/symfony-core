<?php

namespace Core\DependencyInjection;

use Core\Service\IconService as Service;
use Core\Framework\DependencyInjection\ServiceContainer;
use Northrook\HTML\Element;

trait IconService
{
    use ServiceContainer;

    final protected function iconService() : Service
    {
        return $this->serviceLocator( Service::class );
    }

    final protected function getIcon( string $icon, ?string $pack = null ) : string|Element
    {
        return $this->iconService()->getIconPack( $pack )->get( $icon );
    }
}
