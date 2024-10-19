<?php

namespace Core\Service;

use Northrook\Exception\E_Value;
use Support\{Str};
use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;
use function Support\toString;

final class ThemeManager
{
    public const array CONFIG = [
        // :root
        'document' => [
            'offset_top'         => '--size-medium',
            'offset-left'        => '--size-medium',
            'offset-right'       => '--size-medium',
            'offset-bottom'      => '--size-medium',
            'scroll-padding-top' => '--offset-top', // maybe +--gap?
            'gutter'             => '2ch',    // left|right padding for elements
            'gap'                => '--size-medium',
            'gap-h'              => '--size-medium',
            'gap-v'              => '--size-medium',
            'min-width'          => '20rem',  // 320px
            'max-width'          => '75rem', // 1200px
        ],
        // :root
        'typography' => [
            'font-family'  => 'Arial, Helvetica, sans-serif',
            'line-height'  => '1.6em',
            'line-spacing' => '1em', // spacing between elements
            'line-length'  => '64ch', // limits inline text elements, like p and h#
        ],
        // :root
        'palette' => [
            'baseline' => [
                'name'        => 'Baseline', // [optional] ucFirst of key if undefined
                'description' => 'For text and surfaces.',       // [optional] used in the editor
                'var'         => 'baseline', // [optional] based on key if undefined
                'seed'        => [222, 9],
            ],
            'system' => [
                'shadow'  => 'baseline-600',
                'info'    => '#579dff',
                'notice'  => '#9f8fef',
                'success' => '#4bce97',
                'warning' => '#f5cd47',
                'danger'  => '#f87268',
            ],
        ],
        // :root
        'sizes' => [
            'none'   => '0',
            'point'  => '.125rem', // 2px
            'tiny'   => '.25rem',  // 4px
            'small'  => '.5rem',   // 8px
            'medium' => '1rem',    // 16px
            'large'  => '1.5rem',  // 24px
            'flow'   => '1em',

            // typography
            'text'       => '1rem',    // default body text size
            'text-small' => '.875rem', // small and .small classes
            'text-large' => '--size-h4', // small and .small classes
            // min-max :: use typography -> max-inline-size as a middle, from minimum till site width
            'h1' => ['1.8rem', '3.05rem'], // min-max
            'h2' => ['1.6rem', '2rem'],
            'h3' => ['1.25rem', '1.8rem'],
            'h4' => ['1.1rem', '1.5rem'],
        ],
        //
        // .class
        // should have .card, .box, .button, .tag, .meta, .media(image/video/etc), ..
        'box' => [
        ],
        'rules' => [
            'small' => ['font-size' => '.875rem'],
        ],
    ];

    private readonly string $theme;

    /** @var array<string, string> */
    private array $status = [];

    /** @var array = ThemeManager::CONFIG */
    private array $config = [];

    /** Stores generated CSS variables as `[--variable => value]`.
     *
     * @var array<string, string>
     */
    private array $generated = [];

    public function __construct( private readonly Pathfinder $pathfinder ) {}

    public function generateTheme() : ThemeManager\Theme
    {
        $this->generateVariables();
        return new ThemeManager\Theme( $this->theme, $this->generated );
    }

    /**
     * Provide an array in the Core Themes format.
     *
     * Usually from the ~/config/themes/_theme-file_.yaml|php
     *
     * @param array|string $theme
     *
     * @return $this
     */
    public function useTheme( null|string|array $theme ) : self
    {
        if ( ! $theme ) {
            $theme = $this->themeFromConfig( $this->pathfinder->get( 'path.theme.core' ) );
        }
        $this->theme = \ucfirst(
            \is_string( $theme ) ? \strrchr( \basename( $theme ), '.', true ) : $theme['name'] ?? $this::class,
        );

        $this->config = \is_string( $theme ) ? $this->themeFromConfig( $theme ) : $theme;

        return $this;
    }

    public function generateVariables() : self
    {
        if ( empty( $this->config ) ) {
            $this->status[__METHOD__] = 'Using default theme configuration.';
            $this->useTheme( $this->pathfinder->get( 'path.theme.core' ) );
        }

        foreach ( $this::CONFIG as $name => $config ) {
            $config = \array_merge( $config, $this->config[$name] ?? [] );

            $this->generated = [
                ...$this->generated,
                ...match ( $name ) {
                    'document'   => $this->parseDocument( $config ),
                    'sizes'      => $this->parseSizes( $config ),
                    'typography' => $this->parseTypography( $config ),
                    // 'palette'    => $this->parsePalette( $config ),
                    // 'box'        => $this->parseBox( $config ),
                    default => [],
                },
            ];
        }

        return $this;
    }

    // ✅
    private function parseDocument( array $config ) : array
    {
        $document = [];

        foreach ( $config as $name => $value ) {
            if ( \is_string( $value ) ) {
                $document[$this->var( $name )] = $this->value( $value, 'px', 'rem', 'em', 'ch' );

                continue;
            }
            E_Value::warning(
                'Unexpected value type for {parse}. Only {type} expected.',
                [
                    'parse' => __METHOD__,
                    'type'  => 'string',
                ],
            );
        }

        return $document;
    }

    // ✅
    private function parseSizes( array $config ) : array
    {
        $sizes = [];

        foreach ( $config as $name => $value ) {
            if ( \is_string( $value ) ) {
                $sizes[$this->var( 'size', $name )] = $this->value( $value, 'px', 'rem', 'em' );

                continue;
            }

            /**
             * Variable size item.
             *
             * @link https://websemantics.uk/tools/fluid-responsive-property-calculator/
             */
            if ( \is_array( $value ) ) {
                $sizes[$this->var( 'size', $name )] = $this->variableValue( $value );

                continue;
            }
        }

        return $sizes;
    }

    /**
     * @param array<string, string> $config
     *
     * @return array
     */
    private function parseTypography( array $config ) : array
    {
        $typography = [];

        foreach ( $config as $name => $value ) {
            [$variable, $value] = match ( $name ) {
                'font-family' => [
                    $this->var( $name ),
                    $this->fontFamily( $value ),
                ],
                'line-height', 'line-spacing' => [
                    $this->var( $name ),
                    $this->value( $value, 'rem', 'em', 'px' ),
                ],
                'line-length' => [
                    $this->var( $name ),
                    $this->value( $value, 'ch', 'rem', 'em' ),
                ],
            };

            $typography[$variable] = $value;
        }

        return $typography;
    }

    private function parsePalette( array $config ) : array
    {
        $palette = [];

        return $palette;
    }

    private function parseBox( array $config ) : array
    {
        $box = [];

        return $box;
    }

    /**
     * Parse a provided filepath string into an array.
     *
     * @param string $file
     *
     * @return array
     */
    private function themeFromConfig( string $file ) : array
    {
        $file = $this->pathfinder->getPath( $file );

        if ( ! $file || ! $file->exists ) {
            throw new InvalidArgumentException( 'The theme configuration file does not exists.' );
        }

        $config = match ( $file->extension ) {
            'php'   => require $file->path,
            'yaml'  => Yaml::parseFile( $file->path ),
            'json'  => \json_decode( $file->read ),
            default => E_Value::error(
                'The theme configuration file {path} does not have a valid extension. {extension}',
                [
                    'path'      => $file->path,
                    'extension' => $file->extension,
                    'accepted'  => ['php', 'yaml', 'json'],
                ],
            ),
        };

        if ( ! \is_array( $config ) ) {
            E_Value::error(
                'The theme configuration file {path} does not produce a valid array.',
                [
                    'path'      => $file->path,
                    'extension' => $file->extension,
                    'accepted'  => ['php', 'yaml', 'json'],
                ],
            );
        }

        return ['name' => \strrchr( \basename( $file ), '.', true ), ...$config];
    }

    // ::: MISC

    protected function var( string ...$string ) : string
    {
        // dump( \get_defined_vars() );
        // Convert to lowercase
        $string = \implode( '-', $string );

        $string = \trim( $string, " \n\r\t\v\0-" );

        // Enforce characters
        if ( ! \preg_match( '#^[a-zA-Z0-9_-]+$#', $string, $matches ) ) {
            throw new InvalidArgumentException( 'The provided string contains illegal characters. It must only accept ASCII letters, numbers, hyphens, and underscores.');
        }

        return "--{$string}";
    }

    protected function value( string|array $value, string ...$type ) : string
    {
        if ( \is_array( $value ) ) {
            return $this->variableValue( $value );
        }

        $value = \strtolower( \trim( $value ) );

        if ( \str_starts_with( $value, '--' ) ) {
            return $value;
        }

        if ( $type && '0' !== $value && ! Str::endsWith( $value, $type ) ) {
            E_Value::error(
                'The variable value {value} was expected to be one of {type}.',
                [
                    'value' => $value,
                    'type'  => toString( $type, '|' ),
                ],
                throw : true,
            );
        }

        return $value;
    }

    protected function variableValue( array $value ) : string
    {
        if ( \count( $value ) !== 2 ) {
            E_Value::error( 'Variable font sizes must only contain a minimum and maximum font size.' );
        }

        $font = [
            'min'  => $this->config['document']['min-width'],
            'max'  => $this->config['document']['max-width'],
            'from' => \array_shift( $value ),
            'to'   => \array_shift( $value ),
        ];

        foreach ( $font as $key => $property ) {
            if ( ! \str_ends_with( $property, 'em' ) ) {
                E_Value::error( 'Variable fonts ranges must be {em} or {rem}.', throw : false );
            }
        }

        [$min, $max, $from, $to] = \array_values( $font );

        $unit = \preg_replace( '#.+?([a-z].+)#', '$1', $min );
        $view = \trim( \floatval( $min ) / 100 .$unit, '0' );

        $mod = 100 * ( \floatval( $to ) - \floatval( $from ) ) / ( \floatval( $max ) - \floatval( $min ) );

        $diff = \rtrim( \ltrim( \number_format( $mod, 4 ), '0' ), '.0' );

        return "min(max({$from},calc({$from} + ((1vw - {$view}) * {$diff}))), {$to})";
    }

    private function fontFamily( mixed $font ) : string
    {
        // TODO : Validate against installed/available fonts
        // TODO : Offer a 'system:sans-serif' | 'system:serif' shorthand

        // Ensure simple fallback
        $fallback = \trim( \strrchr( $font, ',' ) ?: 'fallback', ", \n\r\t\v\0" );

        if ( ! \in_array( $fallback, ['sans-serif', 'serif', 'monospace', 'cursive', 'fantasy'] ) ) {
            $font .= ', system-ui';
        }

        return $font;
    }
}
