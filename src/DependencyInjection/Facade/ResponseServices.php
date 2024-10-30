<?php

namespace Core\DependencyInjection\Facade;

use Core\Settings;
use Core\Service\ToastService;
use Core\DependencyInjection\{StaticServices};
use Core\UI\RenderRuntime;

trait ResponseServices
{
    final protected function settings() : Settings
    {
        return StaticServices::get( Settings::class );
    }

    final protected function renderRuntime() : RenderRuntime
    {
        return StaticServices::get( RenderRuntime::class );
    }

    final protected function toastService() : ToastService
    {
        return StaticServices::get( ToastService::class );
    }
}
