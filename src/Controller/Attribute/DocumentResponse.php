<?php

declare(strict_types=1);

namespace Core\Controller\Attribute;

use Attribute;

#[Attribute( Attribute::TARGET_METHOD )]
final class DocumentResponse {}