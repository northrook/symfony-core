<?php

namespace Core\Service\AssetManager;

use Northrook\Resource\Path;
use Support\Normalize;

final class Asset
{
    /** @var Path[] */
    private array $sources = [];

    public readonly string $name;

    public function __construct(
        string $name,
    ) {
        $this->name = Normalize::key( $name );
    }

    public function addSource( Path $source ) : void
    {
        $this->sources[$source->mimeType][] = $source;
    }
}
