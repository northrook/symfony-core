<?php

declare(strict_types=1);

namespace Core\Model;

use Countable;
use InvalidArgumentException;
use Northrook\Logger\Log;
use Northrook\Time;
use Northrook\Trait\PropertyAccessor;
use function String\hashKey;

/**
 * @internal
 *
 * @property-read string     $key           // Unique key to identify this object internally
 * @property-read  string    $type          // One of 'info', 'success', 'warning', 'error', or 'notice'
 * @property-read  string    $message       // The main message to show the user
 * @property-read  ?string   $description   // [optional] Provide more details.
 * @property-read  ?int      $timeout       // How long before the message should time out, in milliseconds
 * @property-read  array     $instances     // All the times this exact Notification has been created since it was last rendered
 * @property-read  Time $timestamp     // The most recent timestamp object
 * @property-read  int       $unixTimestamp // The most recent timestamps' unix int
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
final class Message implements Countable
{
    use PropertyAccessor;

    private array $occurrences = [];

    private string $type;

    private string $message;

    private ?string $description;

    private ?int $timeout;

    /**
     * @param string      $type        = [ 'info', 'success', 'warning', 'error', 'notice' ][$any]
     * @param string      $message
     * @param null|string $description
     * @param null|int    $timeout
     */
    public function __construct(
        string  $type,
        string  $message,
        ?string $description = null,
        ?int    $timeout = null,
    ) {
        $this->setMessageType( $type );
        $this->message = \trim( $message );
        $this->bump( $description );
        $this->timeout( $timeout );
    }

    public function __get( string $property ) : null|string|int|array
    {
        return match ( $property ) {
            'key'           => hashKey( [$this->type, $this->message, $this->description, $this->timeout] ),
            'type'          => $this->type,
            'message'       => $this->message,
            'description'   => $this->description,
            'timeout'       => $this->timeout,
            'instances'     => $this->instances,
            'timestamp'     => $this->getTimestamp(),
            'unixTimestamp' => $this->getTimestamp()->unixTimestamp,
            default         => null,
        };
    }

    public function count() : int
    {
        return \count( $this->occurrences );
    }

    public function timeout( ?int $set = null ) : Message
    {
        $this->timeout = $set;
        return $this;
    }

    /**
     * Indicate that this notification has been seen before.
     *
     * - Adds a timestamp to the {@see Notification::$instances} array.
     *
     * @return $this
     * @param  ?string $description
     */
    public function bump( ?string $description ) : Message
    {
        $timestamp                                    = new Time();
        $this->occurrences[$timestamp->unixTimestamp] = $timestamp;
        $this->setMessageDescription( $description );
        return $this;
    }

    private function setMessageDescription( ?string $description ) : void
    {
        $this->description = $description ? \trim( $description ) : null;
    }

    private function setMessageType( string $type ) : void
    {
        try {
            // If the $type is a valid level, add it, otherwise throw an exception for incident management
            $this->type = \in_array( $type, ['info', 'success', 'warning', 'error', 'notice'] )
                ? $type
                : throw new InvalidArgumentException( "Invalid type '{$type}' used for ".Message::class );
        }
        catch ( InvalidArgumentException $exception ) {
            // Immediately catch and log the exception, then set the type to 'notice'
            Log::exception( $exception );
            $this->type = 'notice';
        }
    }

    private function getTimestamp() : Time
    {
        return $this->occurrences[\array_key_last( $this->occurrences )];
    }
}