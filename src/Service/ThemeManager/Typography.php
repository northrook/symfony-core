<?php

declare( strict_types = 1 );

namespace Core\Service\ThemeManager;

use Core\Service\ThemeManager;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final readonly class Typography
{
    // public string $family;
    // public string $size;
    //
    // /**
    //  * @var object{height: string, spacing: string, length: string}
    //  */
    // public object $line;

    /**
     * @param array{'font-family': string, 'line-height': string, 'line-spacing': string, 'line-length': string}  $config
     */
    public function __construct( private array $config ) {}

    public function getVariables() : array
    {
        $variables = [];

        foreach ( $this->config as $name => $value ) {
            $value                                        = match ( $name ) {
                'font-family'                 => $this->fontFamily( $value ),
                'line-height', 'line-spacing' => ThemeManager::value( $value, 'rem', 'em', 'px' ),
                'line-length'                 => ThemeManager::value( $value, 'ch', 'rem', 'em' ),
            };
            $variables[ ThemeManager::variable( $name ) ] = $value;
        }

        return $variables;
    }

    private function fontFamily( mixed $font ) : string
    {
        // TODO : Validate against installed/available fonts
        // TODO : Offer a 'system:sans-serif' | 'system:serif' shorthand

        // Ensure simple fallback
        $fallback = \trim( \strrchr( $font, ',' ) ?: 'fallback', ", \n\r\t\v\0" );

        if ( !\in_array( $fallback, ['sans-serif', 'serif', 'monospace', 'cursive', 'fantasy'])) {
            $font .= ', sans-serif';
        }

        return $font;
    }
}