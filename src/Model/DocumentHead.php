<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Core\Model;

use Core\Response\Document;
use Support\Str;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use function Support\toString;

final class DocumentHead implements IteratorAggregate
{
    private array $html = [];

    public function __construct( private readonly Document $document ) {}

    public function title() : self
    {
        $title = $this->document->pull( 'document.title' )
                    ?? \Northrook\Settings::get( 'site.name' )
                    ?? $_SERVER['SERVER_NAME'];

        $this->html[] = "<title>{$title}</title>" ;

        return $this;
    }

    public function meta( string $name, ?string $comment = null ) : self
    {
        if ( ! $value = $this->document->pull( $name ) ) {
            return $this;
        }

        if ( $comment ) {
            $this->html[] = '<!-- '.$comment.' -->' ;
        }

        $meta = \is_array( $value ) ? $value : [$name => $value];

        foreach ( $meta as $name => $content ) {
            $value = toString( $value );
            if ( $value ) {
                $name         = Str::after( $name, '.' );
                $this->html[] = "<meta name=\"{$name}\" content=\"{$value}\">";
            }
        }

        return $this;
    }

    public function assets( string $type, ?string $id = null ) : self
    {
        if ( ! $id ) {
            foreach ( $this->document->pull( "asset.{$type}" ) ?? [] as $asset ) {
                $this->html[] = $asset ;
            }
            return $this;
        }

        $asset = $this->document->pull( "asset.{$type}.{$id}" );

        if ( $asset ) {
            $this->html[] = $asset ;
        }

        return $this;
    }

    public function style( ?string $id = null ) : self
    {
        return $this->assets( 'style', $id );
    }

    public function script( ?string $id = null ) : self
    {
        return $this->assets( 'script', $id );
    }

    // ::: Return :::::

    public function getString( string $separator = '' ) : string
    {
        return \implode( $separator, $this->html );
    }

    public function getArray() : array
    {
        return $this->html;
    }

    public function getIterator() : Traversable
    {
        return new ArrayIterator( $this->html );
    }
}