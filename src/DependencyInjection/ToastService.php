<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Core\Framework\DependencyInjection\ServiceContainer;
use Core\Model\Message;

trait ToastService
{
    use ServiceContainer;

    /**
     * @final
     *
     * @return \Core\Service\ToastService
     */
    final protected function toastService() : \Core\Service\ToastService
    {
        return $this->serviceLocator( ToastService::class );
    }

    public function success( string $message, ?string $description = null ) : Message
    {
        return $this->toastService()->message( 'success', $message, $description );
    }

    public function info( string $message, ?string $description = null ) : Message
    {
        return $this->toastService()->message( 'info', $message, $description );
    }

    public function notice( string $message, ?string $description = null ) : Message
    {
        return $this->toastService()->message( 'notice', $message, $description );
    }

    public function warning( string $message, ?string $description = null ) : Message
    {
        return $this->toastService()->message( 'warning', $message, $description );
    }

    public function danger( string $message, ?string $description = null ) : Message
    {
        return $this->toastService()->message( 'danger', $message, $description );
    }
}
