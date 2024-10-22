<?php

declare(strict_types=1);

namespace Core\Response\Attribute;

use Attribute;

#[Attribute( Attribute::TARGET_METHOD )]
final class ContentResponse {}
