<?php

declare(strict_types=1);

namespace Core\Framework\Controller;

use Attribute;

#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
final class Template
{
    public function __construct(
        public string  $document,
        public ?string $content = null,
    ) {}
}
