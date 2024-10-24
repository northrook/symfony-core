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
trait Palette {

    final public function parseColorPalettes() : CompilerMethods
    {
        /** @var array{string, ResponsiveValue|Value} $config */
        $config = $this->config->get( 'document' );

        foreach ( $config as $name => $value ) {
            // $var   = $this->var( $name );
            // $value = $this->value( $value, 'px', 'rem', 'em', 'ch' );
            //
            // $this->generated[$var] = $value;
        }
        return $this;
    }
}