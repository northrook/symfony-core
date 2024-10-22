<?php

declare(strict_types=1);

namespace Core\View;

use \Core\Response as Response;
use Northrook\HTML\Element\Attributes;
use function Support\toString;
use Stringable;
use const Support\EMPTY_STRING;
use const Support\WHITESPACE;

/**
 * @internal
 * @used-by CoreController
 *
 * @author  Martin Nielsen <mn@northrook.com>
 */
final class Document implements Stringable
{
    public function __construct(
            private readonly Response\Document $document,
        private ?string $content = null,
    ) {}

    public function __toString() : string
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

    private function htmlAttributes() : string
    {
        $attributes = $this->document->pull( 'html' );
        if ( ! $attributes || ! \is_array( $attributes ) ) {
            return EMPTY_STRING;
        }
        return WHITESPACE.Attributes::from( $attributes );
    }
}