<?php

declare(strict_types=1);

namespace Core\Service\ThemeManager;

/**
 * @author Martin Nielsen <mn@northrook.com>
 */
final class ThemePalette extends AbstractThemeConfig
{


    final protected function parseConfig( array $config ) : array
    {
        foreach ( $config as $name => $value ) {
            $var   = $this->var( $name );
            $value = $this->value( $value, 'px', 'rem', 'em', 'ch' );
            //
            // $this->generated[$var] = $value;
        }

        return $config;
    }
}