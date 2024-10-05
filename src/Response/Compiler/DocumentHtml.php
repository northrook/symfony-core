<?php

declare(strict_types=1);

namespace Core\Response\Compiler;

use Core\DependencyInjection\ServiceContainer;
use Core\Response\{Document};
use Core\Service\CurrentRequest;
use Core\Settings;
use Core\View\Message;
use InvalidArgumentException;
use Northrook\HTML\{Element};
use Northrook\HTML\Element\Attributes;
use Northrook\Latte;
use Northrook\UI\Component\Notification;
use Support\Str;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function Support\toString;
use const Support\{EMPTY_STRING, TAB, WHITESPACE};
/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class DocumentHtml
{
    use ServiceContainer;

    private array $head = [];

    public function __construct(
        protected readonly Document     $document,
        private readonly ServiceLocator $serviceLocator,
    ) {}

    /**
     * @param string       $template
     * @param array|object $parameters
     *
     * @return string
     */
    public function latte( string $template, array|object $parameters = [] ) : string
    {
        if ( ! \str_ends_with( $template, '.latte' ) ) {
            throw new InvalidArgumentException( "The '{$template}' string is not valid.\nIt should end with '.latte' and point to a valid template file.}'" );
        }
        return $this->serviceLocator( Latte::class )->render( $template, $parameters );
    }

    public function template( string $content ) : string
    {
        return toString(
            [
                ...$this->getHeadElements(),
                $this->flashBagHandler(), $content,
            ],
            PHP_EOL,
        );
    }

    public function document( string $content ) : string
    {
        return toString(
            [
                '<!DOCTYPE html>',
                '<html'.$this->htmlAttributes().'>',
                ...$this->documentHead(),
                $this->documentBody( $this->flashBagHandler(), $content ),
                '</html>',
            ],
            PHP_EOL,
        );
    }

    /**
     * @return string
     */
    private function flashBagHandler() : string
    {
        $flashes       = $this->serviceLocator( CurrentRequest::class )->flashBag()->all();
        $notifications = EMPTY_STRING;

        foreach ( $flashes as $type => $flash ) {
            foreach ( $flash as $toast ) {
                $notification = match ( $toast instanceof Message ) {
                    true => new Notification(
                        $toast->type,
                        $toast->message,
                        $toast->description,
                        $toast->timeout,
                    ),
                    false => new Notification( $type, toString( $toast ) ),
                };

                if ( ! $notification->description ) {
                    $notification->attributes->add( 'class', 'compact' );
                }

                if ( ! $notification->timeout && 'error' !== $notification->type ) {
                    $notification->setTimeout( Settings::get( 'notification.timeout' ) ?? 5_000 );
                }
                $notifications .= $notification;

            }
        }

        return $notifications;
    }

    private function htmlAttributes() : string
    {
        $attributes = $this->document->pull( 'html' );
        if ( ! $attributes || ! \is_array( $attributes ) ) {
            return EMPTY_STRING;
        }
        return WHITESPACE.Attributes::from( $attributes );
    }

    private function documentHead() : array
    {
        $this->head( '<meta charset="UTF-8">' )
            ->meta( 'meta.viewport' )
            ->title()
            ->meta( 'document' )
            ->meta( 'robots' )
            ->meta( 'theme' )
            ->meta( 'meta' )
            ->assets( 'script' )
            ->assets( 'style' )
            ->assets( 'link' );

        return ['<head>', ...\array_map( static fn( $line ) : string => TAB.$line, $this->head ), '</head>'];
    }

    /**
     * Parse and return all Document head elements.
     *
     * @return array
     */
    private function getHeadElements() : array
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

    private function documentBody(
        string ...$content,
    ) : string {
        $attributes = $this->document->pull( 'body' ) ?? [];
        $body       = new Element( 'body', $attributes, $content );

        return $body->toString( PHP_EOL );
    }

    // ::: Meta Element Methods

    // TODO : Make each keyed, to prevent rendering duplicates
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

    public function title() : self
    {
        $title = $this->document->pull( 'document.title' )
                    ?? \Northrook\Settings::get( 'site.name' )
                    ?? $_SERVER['SERVER_NAME'];

        $this->head( "<title>{$title}</title>" );

        return $this;
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

    public function style( ?string $id = null ) : self
    {
        return $this->assets( 'style', $id );
    }

    public function script( ?string $id = null ) : self
    {
        return $this->assets( 'script', $id );
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