<?php

declare(strict_types=1);

namespace Core\Service\ThemeManager\Compiler;

use Core\Service\ThemeManager\CompilerMethods;

/**
 * @phpstan-type Selector non-empty-string
 * @phpstan-type Value non-empty-string
 * @phpstan-type ResponsiveValue array{0: non-empty-string, 1: non-empty-string}
 *
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
trait Typography
{
    public const string FONT_FALLBACK = 'system-ui';

    final public function parseTypography() : CompilerMethods
    {

        /** @var array{string, ResponsiveValue|Value} $config */
        $config = $this->config->get( 'document' );

        foreach ( $config as $name => $value ) {
            [$var, $value] = match ( $name ) {
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
            $this->generated[$var] = $value;
        }

        return $this;
    }

    private function fontFamily( mixed $font ) : string
    {
        // TODO : Validate against installed/available fonts
        // TODO : Offer a 'system:sans-serif' | 'system:serif' shorthand

        // Ensure simple fallback
        $fallback = \trim( \strrchr( $font, ',' ) ?: 'fallback', ", \n\r\t\v\0" );

        if ( ! \in_array( $fallback, ['sans-serif', 'serif', 'monospace', 'cursive', 'fantasy', $this::FONT_FALLBACK] ) ) {
            $font .= ', '.$this::FONT_FALLBACK;
        }

        return $font;
    }
}