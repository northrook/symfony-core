<?php

declare(strict_types=1);

namespace Core\Service;

use Core\Settings;
use Core\Response\Document;
use Northrook\Trait\PropertyAccessor;
use Support\Str;
use function Support\toString;
use InvalidArgumentException;
use const Support\TAB;

/**
 * @property-read array<int, string> $head
 */
final class DocumentService
{
    use PropertyAccessor;

    private array $head = [];

    public function __construct(
        private readonly Document $document,
        private readonly Settings $settings,
    ) {}

    public function __get( string $property )
    {
        return match ( $property ) {
            'head'  => $this->head,
            default => throw new InvalidArgumentException(),
        };
    }

    public function getHead(
        ?string $charset = null,
    ) : string {

        $html = $charset ? TAB.'<meta charset="UTF-8">' : '';

        foreach ( $this->head as $name => $value ) {
            $html .= TAB.$value.PHP_EOL;
        }

        return PHP_EOL.'<head>'.PHP_EOL.$html.'</head>'.PHP_EOL;
    }

    public function title() : self
    {
        $title = $this->document->pull( 'document.title' )
                 ?? $this->settings->get( 'site.name', $_SERVER['SERVER_NAME'] );

        $this->head[] = "<title>{$title}</title>";

        return $this;
    }

    public function meta( string $name, ?string $comment = null ) : self
    {
        if ( ! $value = $this->document->pull( $name ) ) {
            return $this;
        }

        if ( $comment ) {
            $this->head[] = '<!-- '.$comment.' -->';
        }

        $meta = \is_array( $value ) ? $value : [$name => $value];

        foreach ( $meta as $name => $content ) {
            $value = toString( $value );
            if ( $value ) {
                $name         = Str::after( $name, '.' );
                $this->head[] = "<meta name=\"{$name}\" content=\"{$value}\">";
            }
        }

        return $this;
    }

    public function assets( string $type, ?string $id = null ) : self
    {
        if ( ! $id ) {
            foreach ( $this->document->pull( "asset.{$type}" ) ?? [] as $asset ) {
                $this->head[] = $asset;
            }
            return $this;
        }

        $asset = $this->document->pull( "asset.{$type}.{$id}" );

        if ( $asset ) {
            $this->head[] = $asset;
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
}
