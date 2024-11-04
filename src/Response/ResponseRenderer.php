<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Core\Response;

use Core\Framework\Autowire\Settings;
use Core\Framework\DependencyInjection\{ServiceContainer};
use Core\Framework\Response\Document;
use Core\Response\Document\AssetResolver;
use Core\Service\ToastService;
use Core\UI\Component\Notification;
use Core\UI\RenderRuntime;
use Northrook\HTML\Element\Attributes;
use Stringable;
use Support\Str;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function Support\toString;
use const Support\TAB;

final class ResponseRenderer implements Stringable
{
    use ServiceContainer, Settings;

    private array $head = [];

    private array $notifications = [];

    public function __construct(
            private readonly bool             $isHtmxRequest,
            private readonly Document         $document,
            private string                    $innerHtml,
            protected readonly ServiceLocator $serviceLocator,
    ) {
        $this->resolveNotifications();
    }

    public function __toString() : string
    {
        return $this->render();
    }

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

        return ' '.$attributes->toString();
    }

    private function documentHead() : string
    {
        $this
                ->meta( 'meta.viewport' )
                ->meta( 'document' )
                ->meta( 'robots' )
                ->meta( 'meta' )
                ->assets( 'font' )
                ->assets( 'script' )
                ->assets( 'style' )
                ->assets( 'link' );

        \array_unshift( $this->head, '<meta charset="UTF-8">' );

        $html = '';

        foreach ( $this->head as $name => $value ) {
            $html .= TAB.$value.PHP_EOL;
        }

        return PHP_EOL.'<head>'.PHP_EOL.$html.'</head>'.PHP_EOL;
    }

    private function contentHead() : string
    {
        $this
                ->meta( 'document' )
                ->meta( 'meta' )
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

        $html = $this->isHtmxRequest
            // HTMX contentful response
                ? <<<CONTENT
                    {$this->contentHead()}
                    {$this->notifications()}
                    {$this->innerHtml}
                    CONTENT
            // Fully rendered Document response
                : <<<DOCUMENT
                    <!DOCTYPE html>
                    <html{$this->attributes( 'html' )}>
                        {$this->documentHead()}
                    <body{$this->attributes( 'body' )}>
                        {$this->notifications()}
                        {$this->innerHtml}
                    </body>
                    </html>
                    DOCUMENT;

        $this->innerHtml = '';

        return $html;
    }

    private function resolveNotifications() : void
    {
        return;
        // foreach ( $this->serviceLocator( ToastService::class )->getMessages() as $id => $message ) {
        //     $this->notifications[$id] = new Notification(
        //             $message->type,
        //             $message->title,
        //             $message->description,
        //             $message->timeout,
        //     );
        //
        //     if ( ! $message->description ) {
        //         $this->notifications[$id]->attributes->add( 'class', 'compact' );
        //     }
        //
        //     if ( ! $message->timeout && 'error' !== $message->type ) {
        //         $this->notifications[$id]->setTimeout( 5_000 );
        //     }
        //
        //     $this->notifications[$id] = (string) $this->notifications[$id];
        // }
    }

    private function enqueueInvokedAssets() : void
    {
        $this->document->assets( ...$this->serviceLocator( RenderRuntime::class )->getCalledInvocations() );

        // $resolver = new AssetResolver();
        dump( $this->document->get( 'assets' ) );
    }

    // : end

    // ?? Document

    protected function metaTitle( ?string $value ) : string
    {
        $value ??= $this->settings()->get( 'site.name', $_SERVER['SERVER_NAME'] );

        return "<title>{$value}</title>";
    }

    public function meta( string $name, ?string $comment = null ) : self
    {
        if ( ! $value = $this->document->pull( $name ) ) {
            return $this;
        }

        if ( $comment ) {
            $this->head[] = '<!-- '.$comment.' -->';
        }

        // dump(
        //         $this->document,
        //         $name,
        //         $value);

        $meta = \is_array( $value ) ? $value : [$name => $value];

        foreach ( $meta as $name => $value ) {
            if ( $value = toString( $value ) ) {
                $name         = Str::after( $name, '.' );
                $this->head[] = match ( $name ) {
                    'title' => $this->metaTitle( $value ),
                    default => "<meta name=\"{$name}\" content=\"{$value}\">",
                };
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

        // foreach ( $type as $asset ) {
        //     // dump( $asset );
        //     //
        //     // dump( $this->document->pull( $asset ) );
        // }

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
}
