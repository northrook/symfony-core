<?php

declare(strict_types=1);

namespace Core\Service\ThemeManager\Theme;

use Override;

final class ThemeLayout extends AbstractTheme
{
    /**
     * @param array{document: array,sizes: array} $config
     */
    public function __construct( protected array $config ) {}

    #[Override]
    public function parseConfig() : void
    {
        $this->parseDocument();
        $this->parseSizes();
    }

    protected function parseDocument() : void
    {
        foreach ( $this->config['document'] as $name => $value ) {
            $this->set(
                $this->var( $name ),
                $this->value( $value, 'px', 'rem', 'em', 'ch' ),
            );
        }
    }

    protected function parseSizes() : void
    {
        foreach ( $this->config['sizes'] as $name => $value ) {
            $this->set(
                $this->var( $name ),
                $this->value( $value, 'px', 'rem', 'em', 'ch' ),
            );
        }
    }
}