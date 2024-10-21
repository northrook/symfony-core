<?php

declare(strict_types=1);

namespace Core\Controller\Attribute;

use Attribute;

#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class Template
{
    public function __construct(
        public string  $html,
        public ?string $htmx,
    ) {}
}