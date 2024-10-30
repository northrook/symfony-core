<?php

declare(strict_types=1);

namespace Core\Response;

use Northrook\{ArrayAccessor, Logger\Log, Resource\Path, Exception\Trigger};
use Northrook\HTML\Element;
use Support\Normalize;
use function Support\toString;

/**
 * Handles all Document related properties.
 *
 * @author Martin Nielsen <mn@northrook.com>
 */
final class Document extends ArrayAccessor
{
    private const array META_GROUPS = [
        'html'     => ['id', 'status'],
        'document' => ['title', 'description', 'author', 'keywords'],
        'theme'    => ['color', 'scheme', 'name'],
    ];

    protected bool $locked = false;

    /** @var bool Determines how robot tags will be set */
    public bool $isPublic = false;

    private function isLocked() : bool
    {
        if ( ! $this->locked ) {
            return false;
        }

        Log::warning(
            'The {class} is locked. No further changes can be made at this time.',
            ['class' => $this::class, 'document' => $this, 'reason' => 'Locked by the RequestResponseHandler.'],
        );

        return true;
    }

    public function set( array|int|string $keys, mixed $value = null ) : Document
    {
        if ( $this->isLocked() ) {
            return $this;
        }
        return parent::set( $keys, $value );
    }

    public function add( array|int|string $keys, mixed $value = null ) : Document
    {
        if ( $this->isLocked() ) {
            return $this;
        }
        return parent::add( $keys, $value );
    }

    public function __invoke(
        ?string           $title = null,
        ?string           $description = null,
        null|string|array $keywords = null,
        ?string           $author = null,
        ?string           $id = null,
        ?string           $status = null,
    ) : Document {
        $set = \array_filter( \get_defined_vars() );

        foreach ( $set as $name => $value ) {
            $this->meta( $name, $value );
        }

        return $this;
    }

    /**
     * Set an arbitrary meta tag.
     *
     * - This method does not validate the name or content.
     * - The name is automatically prefixed with the group if relevant.
     *
     * @param string            $name    = ['title', 'description', 'author', 'keywords'][$any]
     * @param null|array|string $content
     *
     * @return $this
     */
    public function meta( string $name, null|string|array $content ) : Document
    {
        $this->set( $this->metaGroup( $name ), toString( $content, ', ' ) );

        return $this;
    }

    public function head( string $key, string|Element $html ) : Document
    {
        $value = $html instanceof Element ? $html->toString() : $html;

        // TODO : Cache
        // TODO : Linting / validation step

        $this->set( 'head.'.Normalize::key( $key ), $value );

        return $this;
    }

    /**
     * @param string $bot     = [ 'googlebot', 'bingbot', 'yandexbot'][$any]
     * @param string ...$rule = [
     *                        'index', 'noindex', 'follow', 'nofollow',
     *                        'index, follow', 'noindex, nofollow',
     *                        'noarchive', 'nosnippet', 'nositelinkssearchbox'
     *                        ][$any]
     *
     * @return Document
     *
     * @see https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag Documentation
     */
    public function robots( string $bot, string ...$rule ) : Document
    {
        $rules = [];

        foreach ( $rule as $content ) {
            if ( ! \is_string( $content ) ) {
                Trigger::valueError(
                    message : 'Invalid robots rule for {bot}, a string is required, but {type} was provided.',
                    context : ['bot' => $bot, 'type' => \gettype( $content )],
                );

                continue;
            }

            if ( \str_contains( $content, ',' ) ) {
                foreach ( \explode( ',', $content ) as $value ) {
                    $rules[] = \trim( $value );
                }
            }
            else {
                $rules[] = \trim( $content );
            }
        }

        $this->set( "robots.{$bot}", \implode( ', ', $rules ) );

        return $this;
    }

    public function assets( string ...$enqueue ) : Document
    {
        foreach ( $enqueue as $asset ) {
            $this->add( 'assets', $asset );
        }

        return $this;
    }

    /**
     * @param Path|string $path
     * @param ?string     $id
     * @param bool        $inline
     *
     * @return $this
     */
    public function style(
        string|Path $path, // 'core.{name}' | path
        ?string     $id = null,
        bool        $inline = false,
    ) : Document {
        return $this->add( 'style', [
            'path'   => $path,
            'id'     => $id,
            'inline' => $inline,
        ] );
    }

    /**
     * @param Path|string $path
     * @param ?string     $id
     * @param bool        $inline
     *
     * @return $this
     */
    public function script(
        string|Path $path, // 'core.{name}' | path
        ?string     $id = null,
        bool        $inline = false,
    ) : Document {
        return $this->add( 'script', [
            'path'   => $path,
            'id'     => $id,
            'inline' => $inline,
        ] );
    }

    /**
     * @param string $href
     * @param        $attributes
     *
     * @return Document
     *
     * @see MDN https://developer.mozilla.org/en-US/docs/Web/HTML/Element/link
     */
    public function link( string $href, ...$attributes ) : Document
    {
        return $this->add( 'link', ['href' => $href] + $attributes );
    }

    public function theme(
        string  $color,
        string  $scheme = 'dark light',
        ?string $name = 'system',
    ) : Document {
        // Needs to generate theme.scheme.color,
        // this is to allow for different colors based on light/dark

        foreach ( [
            'color'  => $color,
            'scheme' => $scheme,
            'name'   => $name,
        ] as $metaName => $content ) {
            $this->meta( "theme.{$metaName}", $content );
        }
        return $this;
    }

    public function body( ...$set ) : Document
    {
        foreach ( $set as $name => $value ) {
            $separator = match ( $name ) {
                'class' => ' ',
                'style' => ';',
                default => null,
            };

            $value = match ( $name ) {
                'class', 'style' => \is_array( $value ) ? $value : \explode( $separator, $value ),
                default => $value,
            };

            $this->set( 'body.'.Normalize::key( $name ), $value );
        }
        return $this;
    }

    private function metaGroup( string $name ) : string
    {
        foreach ( $this::META_GROUPS as $group => $names ) {
            if ( \in_array( $name, $names ) ) {
                return "{$group}.{$name}";
            }
        }
        return $name;
    }
}
