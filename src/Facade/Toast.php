<?php

namespace Core\Facade;

use Core\DependencyInjection\{StaticServices};
use Core\Model\Message;
use Core\Service\ToastService;

/**
 * @author Martin Nielsen <mn@northrook.com>
 */
final class Toast
{
    public static function success( string $message, ?string $description = null ) : Message
    {
        return Toast::facade()->message( 'success', $message, $description );
    }

    public static function info( string $message, ?string $description = null ) : Message
    {
        return Toast::facade()->message( 'info', $message, $description );
    }

    public static function notice( string $message, ?string $description = null ) : Message
    {
        return Toast::facade()->message( 'notice', $message, $description );
    }

    public static function warning( string $message, ?string $description = null ) : Message
    {
        return Toast::facade()->message( 'warning', $message, $description );
    }

    public static function danger( string $message, ?string $description = null ) : Message
    {
        return Toast::facade()->message( 'danger', $message, $description );
    }

    /**
     * @return ToastService
     */
    private static function facade() : ToastService
    {
        return StaticServices::get( ToastService::class );
    }
}
