<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Core\Response\Document;

use Core\DependencyInjection\Facade\ResponseServices;
use Core\Response\Document;
use Core\UI\Component\Notification;
use Northrook\HTML\Element\Attributes;
use Stringable;
use Support\Str;
use function Support\toString;
use const Support\TAB;

final class ResponseRenderer implements Stringable
{
    use ResponseServices;

    private array $head = [];

    private array $notifications = [];

    public function __construct(
        private readonly bool     $isHtmxRequest,
        private readonly Document $document,
        private string            $innerHtml,
    ) {
        $this->resolveNotifications();
    }

    public function __toString() : string
    {
        return $this->render();
    }

    // ?? Document

    public function title() : self
    {
        $title = $this->document->pull( 'document.title' )
                 ?? $this->settings()->get( 'site.name', $_SERVER['SERVER_NAME'] );

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

    /**
     * @param null|'link'|'script'|'style' $type
     *
     * @return $this
     */
    public function assets( ?string $type = null ) : self
    {
        $type = $type ? [$type] : ['script', 'style', 'link'];

        foreach ( $type as $asset ) {
            dump( $asset );

            dump( $this->document->pull( $asset ) );
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

    // :: Document

    private function notifications() : string
    {
        return toString( $this->notifications );
    }

    private function attributes( string $document ) : string
    {
        $attributes = $this->document->pull( $document, null );

        if ( ! $attributes ) {
            return '';
        }

        $attributes = new Attributes( $attributes );

        return $attributes->toString();
    }

    private function documentHead() : string
    {

        $this->title()
            ->meta( 'document' )
            ->meta( 'robots' )
            ->meta( 'theme' )
            ->assets( 'font' )
            ->assets( 'script' )
            ->assets( 'style' )
            ->assets( 'link' );

        $html = TAB.'<meta charset="UTF-8">';

        foreach ( $this->head as $name => $value ) {
            $html .= TAB.$value.PHP_EOL;
        }

        return PHP_EOL.'<head>'.PHP_EOL.$html.'</head>'.PHP_EOL;
    }

    private function contentHead() : string
    {
        $this->title()
            ->meta( 'document' )
            ->meta( 'theme' )
            ->assets( 'font' )
            ->assets( 'script' )
            ->assets( 'style' )
            ->assets( 'link' );

        return \implode( PHP_EOL, $this->head ).PHP_EOL;
    }

    public function render() : string
    {
        // ? Filter out existing assets from HTMX requests
        $this->enqueueInvokedAssets();

        return $this->isHtmxRequest
                // Fully rendered Document response
                ? <<<DOCUMENT
                    <!DOCTYPE html>
                    <html{$this->attributes( 'head' )}>
                        {$this->documentHead()}
                    <body{$this->attributes( 'body' )}>
                        {$this->notifications()}
                        {$this->innerHtml}
                    </body>
                    </html>
                    DOCUMENT
                // HTMX contentful response
                : <<<CONTENT
                    {$this->contentHead()}
                    {$this->notifications()}
                    {$this->innerHtml}
                    CONTENT;
    }

    private function resolveNotifications() : void
    {
        foreach ( $this->toastService()->getMessages() as $id => $message ) {
            $this->notifications[$id] = new Notification(
                $message->type,
                $message->title,
                $message->description,
                $message->timeout,
            );

            if ( ! $message->description ) {
                $this->notifications[$id]->attributes->add( 'class', 'compact' );
            }

            if ( ! $message->timeout && 'error' !== $message->type ) {
                $this->notifications[$id]->setTimeout( 5_000 );
            }
        }
    }

    private function enqueueInvokedAssets() : void
    {
        $this->document->assets( ...$this->renderRuntime()->getCalledInvocations() );

        $resolver = new AssetResolver();
        dump( $resolver, $this->document->get( 'assets' ) );
    }

    // : end
}
