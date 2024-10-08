<?php

namespace Core\Service\AssetManager;

use const Support\AUTO;

final readonly class Asset
{
    /**
     * @param string       $assetID
     * @param string       $type
     * @param string       $label
     * @param string       $relativePath  `/assets/type/..`
     * @param string       $absolutePath  `~./src/public/assets/type/..`
     * @param class-string $compilerClass
     */
    public function __construct(
        public string $assetID,
        public string $type,
        public string $label,
        public string $relativePath,
        public string $absolutePath,
        public string $compilerClass,
    ) {}

    /**
     * Points to the relative location of the asset.
     *
     * ```
     * ~root/public/
     *            ./assets/type/filename.ext?v=HASH
     * ```
     *
     * @return string
     * @param  ?string $version
     */
    final public function getPath( ?string $version = AUTO ) : string
    {
        return $this->relativePath.'?v='.( $version ?? $this->assetID );
    }
}