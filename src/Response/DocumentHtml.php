<?php

declare(strict_types=1);

namespace Core\Response;

use Northrook\HTML\Element\Attributes;
use Northrook\HTML\HtmlNode;
use Northrook\Interface\Printable;
use Northrook\Settings;
use RuntimeException;
use Support\Str;
use function Support\toString;
use const Support\{EMPTY_STRING, TAB, WHITESPACE};
/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class DocumentHtml
{
    private readonly string $charset;

    private array $head = [];

    public function __construct(
        public readonly Document $document,
        ?string                  $charset = null,
    ) {
        $this->charset = $charset ? \strtoupper( $charset ) : 'UTF-8';
    }

    public function render( string|Printable $body, ?string $prepend, ?string $append ) : string
    {
        $this->head( '<meta charset="'.$this->charset.'">' )
            ->meta( 'meta.viewport' )
            ->title()
            ->meta( 'document' )
            ->meta( 'robots' )
            ->meta( 'theme' )
            ->meta( 'meta' )
            ->assets( 'script' )
            ->assets( 'style' )
            ->assets( 'link' );

        return toString(
            [
                '<!DOCTYPE html>',
                '<html'.$this->documentHtml().'>',
                ...$this->documentHead(),
                ...$this->documentBody( $body, $prepend, $append ),
                '</html>',
            ],
            PHP_EOL,
        );
    }

    public function title() : self
    {
        $title = $this->document->pull( 'document.title' )
                    ?? Settings::get( 'site.name' )
                    ?? $_SERVER['SERVER_NAME'];

        $this->head( "<title>{$title}</title>" );

        return $this;
    }

    // : TODO : Make each keyed, to prevent rendering duplicates

    public function meta( string $name, ?string $comment = null ) : self
    {
        if ( ! $value = $this->document->pull( $name ) ) {
            return $this;
        }

        if ( $comment ) {
            $this->head( '<!-- '.$comment.' -->' );
        }

        if ( \is_array( $value ) ) {
            foreach ( $value as $name => $content ) {
                $this->head( $this->printMeta( $name, $content ) );
            }
        }
        else {
            $this->head( $this->printMeta( $name, $value ) );
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

    public function assets( string $type, ?string $id = null ) : self
    {
        if ( ! $id ) {
            foreach ( $this->document->pull( "asset.{$type}" ) ?? [] as $asset ) {
                $this->head( $asset );
            }
            return $this;
        }

        $asset = $this->document->pull( "asset.{$type}.{$id}" );

        if ( $asset ) {
            $this->head( $asset );
        }

        return $this;
    }

    /**
     * Parse and return all Document head elements.
     *
     * @return array
     */
    public function getHead() : array
    {
        $this->title()
            ->meta( 'document' )
            ->meta( 'robots' )
            ->meta( 'theme' )
            ->assets( 'script' )
            ->assets( 'style' )
            ->assets( 'link' );
        return $this->head;
    }

    private function documentHtml() : string
    {
        $attributes = $this->document->pull( 'html' );
        if ( ! $attributes || ! \is_array( $attributes ) ) {
            return EMPTY_STRING;
        }
        return WHITESPACE.Attributes::from( $attributes );
    }

    private function documentHead() : array
    {
        $head = ['<head>'];

        foreach ( $this->head as $line ) {
            $head[] = TAB.$line;
        }

        $head[] = '</head>';
        return $head;
    }

    private function documentBody(
        string|Printable $content,
        ?string          $prepend = null,
        ?string          $append = null,
    ) : array {
        $attributes = $this->document->pull( 'body' ) ?? [];
        $content    = \trim( $content );

        if ( \str_starts_with( $content, '<body' ) ) {
            // Sanity check
            if ( ! \str_ends_with( $content, '</body>' ) ) {
                throw new RuntimeException( <<<'EOD'
                    The provided content has malformed HTML.
                                        It opens with a <body tag, but does not end with one.
                    EOD, );
            }

            // Get the substring length of the <body .. element>
            $bodyLength = \strpos( $content, '>' ) + 1;
            // Extract a substring with the <body .. element>
            $body = \substr( $content, 0, $bodyLength );
            // Remove the <body .. element> and its closing </body> tag
            $content = \substr( $content, $bodyLength, -\strlen( '</body>' ) );

            foreach ( HtmlNode::extractAttributes( $body ) as $name => $value ) {
                $separator = match ( $name ) {
                    'class' => ' ',
                    'style' => ';',
                    default => null,
                };

                $value = match ( $name ) {
                    'class', 'style' => \is_array( $value ) ? $value : \explode( $separator, $value ),
                    default => $value,
                };

                if ( isset( $attributes[$name] ) ) {
                    if ( \is_array( $attributes[$name] ) && \is_array( $value ) ) {
                        $attributes[$name] = [...$attributes[$name], ...$value];
                    }
                    elseif ( \is_string( $attributes[$name] ) ) {
                        $attributes[$name] .= ' '.toString( $value, $separator );
                    }
                }
                else {
                    $attributes[$name] = $value;
                }
            }
        }

        if ( $attributes ) {
            $body = 'body '.Attributes::from( $attributes )->toString();
        }
        else {
            $body = 'body';
        }

        $html = [];

        foreach ( [
            "<{$body}>",
            $prepend,
            $content,
            $append,
            '</body>',
        ] as $item ) {
            if ( ! $item ) {
                continue;
            }

            $html[] = \trim( $item );
        }

        return $html;
    }

    private function head( string $html ) : self
    {
        $this->head[] = $html;
        return $this;
    }

    private function printMeta( string $name, mixed $value ) : ?string
    {
        $name  = Str::after( $name, '.' );
        $value = toString( $value );
        if ( ! $value ) {
            return null;
        }
        return "<meta name=\"{$name}\" content=\"{$value}\">";
    }
}