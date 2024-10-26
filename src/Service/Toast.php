<?php

declare(strict_types=1);

namespace Core\Service;

use Core\Model\Message;
use Northrook\Interface\Singleton;
use Northrook\Trait\SingletonClass;
use function String\hashKey;

final class Toast implements Singleton
{
    use SingletonClass;

    /** @var array<string, Message> */
    private array $messages = [];

    public function __construct() {
        $this->instantiateSingleton();
    }

    public static function success( string $message, ?string $description = null ) : Message
    {
        return self::getInstance( true )->setMessage( 'success', $message, $description );
    }

    public static function info( string $message, ?string $description = null ) : Message
    {
        return self::getInstance( true )->setMessage( 'info', $message, $description );
    }

    public static function notice( string $message, ?string $description = null ) : Message
    {
        return self::getInstance( true )->setMessage( 'notice', $message, $description );
    }

    public static function warning( string $message, ?string $description = null ) : Message
    {
        return self::getInstance( true )->setMessage( 'warning', $message, $description );
    }

    public static function danger( string $message, ?string $description = null ) : Message
    {
        return self::getInstance( true )->setMessage( 'danger', $message, $description );
    }

    private function setMessage(
        string  $type,
        string  $title,
        ?string $description,
        ?int    $timeout = null,
    ) : Message {
        $flashKey = hashKey( [$type, $title] );

        if ( \array_key_exists( $flashKey, $this->messages ) ) {
            return $this->messages[$flashKey]->bump( $description );
        }

        return $this->messages[$flashKey] = new Message( $type, $title, $description, $timeout );
    }

    public function hasMessages() : bool
    {
        return ! empty( $this->messages );
    }

    public function getMessages() : array
    {
        return $this->messages;
    }

    public function pullMessages() : array
    {
        $messages = $this->getMessages();
        $this->messages = [];
        return $messages;
    }
}
