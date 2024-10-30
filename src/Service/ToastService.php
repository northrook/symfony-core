<?php

declare(strict_types=1);

namespace Core\Service;

use Core\Model\Message;
use Stringable;
use Symfony\Component\HttpFoundation as Http;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

final readonly class ToastService
{
    public function __construct( private Http\RequestStack $requestStack ) {}

    private function getFlashBagMessage( string $key ) : ?Message
    {
        return $this->getFlashBag()->get( $key )[0] ?? null;
    }

    /**
     * Adds a simple flash message to the current session.
     *
     * @param string            $type       = ['info', 'success', 'warning', 'error', 'notice'][$any]
     * @param string|Stringable ...$message
     *
     * @return $this
     */
    public function flash( string $type, string|Stringable ...$message ) : ToastService
    {
        $this->getFlashBag()->add( $type, $message );
        return $this;
    }

    public function message(
        string  $type,
        string  $title,
        ?string $description,
        ?int    $timeout = null,
    ) : Message {
        $flashKey = \hash( 'xxh3', $type.$title );

        /** @type ?\Core\Model\Message $message } */
        if ( $this->getFlashBag()->has( $flashKey ) ) {
            $message = $this->getFlashBagMessage( $flashKey );
            $message?->bump( $description );
        }

        $message ??= new Message( $type, $title, $description, $timeout );

        $this->getFlashBag()->add( $flashKey, $message );

        return $message;
    }

    /**
     * Retrieve the current {@see getFlashBag} from the active {@see Session}.
     *
     * @return FlashBagInterface
     */
    public function getFlashBag() : FlashBagInterface
    {
        \assert( $this->requestStack->getSession() instanceof FlashBagAwareSessionInterface );

        return $this->requestStack->getSession()->getFlashBag();
    }

    /**
     * @return Message[]
     */
    public function getMessages() : array
    {
        $messages = [];

        foreach ( $this->getFlashBag()->all() as $type => $message ) {
            \assert( \is_array( $message ) );
            if ( $message[0] instanceof Message ) {
                $messages[$type] = $message[0];

                continue;
            }

            foreach ( $message as $title ) {
                $flashKey            = \hash( 'xxh3', $type.$title );
                $messages[$flashKey] = new Message( $type, $title );
            }
        }

        return $messages;
    }
}
