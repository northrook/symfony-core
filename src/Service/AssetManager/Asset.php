<?php

namespace Core\Service\AssetManager;

use Northrook\Filesystem\File;
use Northrook\HTML\Element;
use Northrook\Logger\Log;
use Core\Service\AssetManager\Compiler\{Script, Style};
use const Support\AUTO;
use InvalidArgumentException;

final readonly class Asset
{
    public bool $canInline;

    /**
     * @param string       $id
     * @param string       $type
     * @param string       $label
     * @param string       $relativePath  `/assets/type/..`
     * @param string       $absolutePath  `~./src/public/assets/type/..`
     * @param class-string $compilerClass
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $label,
        public string $relativePath,
        public string $absolutePath,
        public string $compilerClass,
    ) {
        $this->canInline = match ( $compilerClass ) {
            Style::class, Script::class => true,
            default => false,
        };
    }

    public static function link( Asset $asset, array $attributes = [] ) : string
    {

        $attributes['id']          ??= $asset->id;
        $attributes['asset-label'] ??= $asset->label;

        if ( Script::class === $asset->compilerClass ) {
            $attributes['link'] = $asset->getPath();
            $attributes['defer'] ??= true;
            return (string) new Element( 'script', $attributes );
        }

        if ( Style::class === $asset->compilerClass ) {
            $attributes['rel']  = 'stylesheet';
            $attributes['href'] = $asset->getPath();
            return (string) new Element( 'style', $attributes );
        }

        throw new InvalidArgumentException();
    }

    public static function inline( Asset $asset, array $attributes = [] ) : string
    {
        if ( ! $asset->canInline ) {
            Log::error(
                'The {label}::{type} Asset cannot be inlined. Linked version returned.',
                ['label' => $asset->label, 'type' => $asset->type],
            );
            return Asset::link( $asset );
        }

        $content = File::read( $asset->absolutePath );

        $attributes['id']          ??= $asset->id;
        $attributes['asset-label'] ??= $asset->label;

        if ( Script::class === $asset->compilerClass ) {
            $attributes['defer'] ??= true;
            return (string) new Element( 'script', $attributes, $content );
        }

        if ( Style::class === $asset->compilerClass ) {
            return (string) new Element( 'style', $attributes, $content );
        }

        throw new InvalidArgumentException();
    }

    /**
     * Points to the relative location of the asset.
     *
     * ```
     * ~root/public/
     *            ./assets/type/filename.ext?v=HASH
     * ```
     *
     * @param ?string $version
     *
     * @return string
     */
    final public function getPath( ?string $version = AUTO ) : string
    {
        return $this->relativePath.'?v='.( $version ?? $this->id );
    }
}
