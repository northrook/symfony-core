<?php

namespace Core\Framework\DependencyInjection;

trait Settings
{
    use ServiceContainer;

    /**
     * @final
     *
     * @return \Core\Service\Settings
     */
    final protected function settings() : \Core\Service\Settings
    {
        return $this->serviceLocator( Settings::class );
    }
}
