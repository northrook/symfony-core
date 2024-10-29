<?php

declare(strict_types=1);

namespace Core\Framework\Controller;

use Attribute;

#[Attribute( Attribute::TARGET_METHOD )]
final class ContentResponse {}
