<?php

declare(strict_types=1);

namespace Core\Service;

use Core\Model\Message;
use InvalidArgumentException;
use Stringable;
use Symfony\Component\HttpFoundation as Http;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use function String\hashKey;

final readonly class ToastService
{
    private FlashBagInterface $flashBag;

    public function __construct( private Http\RequestStack $requestStack ) {}

    private function getFlashBagMessage( string $key ) : ?Message
    {
        return $this->flashBag()->get( $key )[0] ?? null;
    }

    /**
     * Adds a simple flash message to the current session.
     *
     * @param string                  $type    = ['info', 'success', 'warning', 'error', 'notice'][$any]
     * @param array|string|Stringable $message
     *
     * @return $this
     */
    public function flash( string $type, string|Stringable|array $message ) : ToastService
    {
        $this->flashBag()->add( $type, $message );
        return $this;
    }

    public function message(
        string  $type,
        string  $title,
        ?string $description,
        ?int    $timeout = null,
    ) : Message {
        $flashKey = hashKey( [$type, $title] );

        /** @type ?\Core\Model\Message $message } */
        if ( $this->flashBag()->has( $flashKey ) ) {
            $message = $this->getFlashBagMessage( $flashKey );
            $message?->bump( $description );
        }

        $message ??= new Message( $type, $title, $description, $timeout );

        $this->flashBag()->add( $flashKey, $message );

        return $message;
    }

    public function success( string $message, ?string $description = null ) : Message
    {
        return $this->message( 'success', $message, $description );
    }

    public function info( string $message, ?string $description = null ) : Message
    {
        return $this->message( 'info', $message, $description );
    }

    public function notice( string $message, ?string $description = null ) : Message
    {
        return $this->message( 'notice', $message, $description );
    }

    public function warning( string $message, ?string $description = null ) : Message
    {
        return $this->message( 'warning', $message, $description );
    }

    public function danger( string $message, ?string $description = null ) : Message
    {
        return $this->message( 'danger', $message, $description );
    }

    /**
     * Retrieve the current {@see FlashBag} from the active {@see Session}.
     *
     * @return FlashBagInterface
     * @throws SessionNotFoundException if no session is active
     */
    private function flashBag() : FlashBagInterface
    {
        return $this->flashBag ??= ( function() {
            if ( ! $this->requestStack->getSession() instanceof FlashBagAwareSessionInterface ) {
                throw new InvalidArgumentException( 'The session does not support Flash Notifications.' );
            }
            return $this->requestStack->getSession()->getFlashBag();
        } )();
    }
}