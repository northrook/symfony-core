<?php

declare(strict_types=1);

namespace Core\UI;

use Core\Framework\DependencyInjection\ServiceContainer;
use Core\Latte\RuntimeRenderInterface;
use Core\UI\Component\Icon;
use Northrook\HTML\Element;
use function String\hashKey;
use function Support\classBasename;
use const Support\WHITESPACE;

abstract class AbstractComponent implements RuntimeRenderInterface
{
    use ServiceContainer;

    /**
     * Called when the Component is stringified.
     *
     * @return string
     */
    abstract protected function build() : string;

    final public function __toString() : string
    {
        $this->serviceLocator( RenderRuntime::class )->registerInvocation( $this::class );
        return $this->build();
    }

    final public function componentName() : string
    {
        return \strtolower( classBasename( $this::class ) );
    }

    final public function componentHash() : string
    {
        return hashKey( [$this, \spl_object_id( $this )] );
    }

    final protected static function parseContentArray( array $array ) : array
    {
        return self::recursiveElement( $array );
    }

    private static function recursiveElement( array $array, null|string|int $key = null ) : string|array
    {
        // If $key is string, this iteration is an element
        if ( \is_string( $key ) ) {
            $tag        = \strrchr( $key, ':', true );
            $attributes = $array['attributes'];
            $array      = $array['content'];

            if ( \str_ends_with( $tag, 'icon' ) && $get = $attributes['get'] ?? null ) {
                unset( $attributes['get'] );
                return (string) new Icon( $tag, $get, $attributes );
            }
        }

        $content = [];

        foreach ( $array as $elementKey => $value ) {
            $elementKey = self::recursiveKey( $elementKey, \gettype( $value ) );

            if ( \is_array( $value ) ) {
                $content[$elementKey] = self::recursiveElement( $value, $elementKey );
            }
            else {
                self::appendTextString( $value, $content );
            }
        }

        if ( \is_string( $key ) ) {
            $element = new Element( $tag, $attributes, $content );

            return $element->toString( WHITESPACE );
        }

        return $content;
    }

    private static function recursiveKey( string|int $element, string $valueType ) : string|int|null
    {
        if ( \is_int( $element ) ) {
            return $element;
        }

        $index = \strrpos( $element, ':' );

        // Treat parsed string variables as simple strings
        if ( 'string' === $valueType && \str_starts_with( $element, '$' ) ) {
            return (int) \substr( $element, $index++ );
        }

        return $element;
    }

    private static function appendTextString( string $value, array &$content ) : void
    {
        // Trim $value, and bail early if empty
        if ( ! $value = \trim( $value ) ) {
            return;
        }

        $lastIndex = \array_key_last( $content );
        $index     = \count( $content );

        if ( \is_int( $lastIndex ) ) {
            if ( $index > 0 ) {
                $index--;
            }
        }

        if ( isset( $content[$index] ) ) {
            $content[$index] .= " {$value}";
        }
        else {
            $content[$index] = $value;
        }
    }
}
