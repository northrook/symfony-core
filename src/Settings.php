<?php

namespace Core;

use Northrook\ArrayStore;

final class Settings extends ArrayStore
{
    /**
     * @param string $storageDirectory
     */
    public function __construct(
        string $storageDirectory,
    ) {
        parent::__construct( $storageDirectory, $this::class);
    }
}
