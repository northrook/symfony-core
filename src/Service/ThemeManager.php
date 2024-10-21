<?php

namespace Core\Service;

use Northrook\Exception\E_Value;
use Support\{Str};
use Symfony\Component\Yaml\Yaml;
use InvalidArgumentException;
use function Support\toString;

final class ThemeManager
{

    private readonly string $theme;

    /** @var array<string, string> */
    private array $status = [];

    /** @var array = ThemeManager::CONFIG */
    private array $config = [];

    /** Stores generated CSS variables as `[--variable => value]`.
     *
     * @var array<string, string>
     */
    private array $generated = [];

    public function __construct( private readonly Pathfinder $pathfinder ) {}

    public function generateTheme() : ThemeManager\Theme
    {
        $this->generateVariables();
        return new ThemeManager\Theme( $this->theme, $this->generated );
    }

    /**
     * Provide an array in the Core Themes format.
     *
     * Usually from the ~/config/themes/_theme-file_.yaml|php
     *
     * @param array|string $theme
     *
     * @return $this
     */
    public function useTheme( null|string|array $theme ) : self
    {
        if ( ! $theme ) {
            $theme = $this->themeFromConfig( $this->pathfinder->get( 'path.theme.core' ) );
        }
        $this->theme = \ucfirst(
            \is_string( $theme ) ? \strrchr( \basename( $theme ), '.', true ) : $theme['name'] ?? $this::class,
        );

        $this->config = \is_string( $theme ) ? $this->themeFromConfig( $theme ) : $theme;

        return $this;
    }

    public function generateVariables() : self
    {
        if ( empty( $this->config ) ) {
            $this->status[__METHOD__] = 'Using default theme configuration.';
            $this->useTheme( $this->pathfinder->get( 'path.theme.core' ) );
        }

        foreach ( $this::CONFIG as $name => $config ) {
            $config = \array_merge( $config, $this->config[$name] ?? [] );

            $this->generated = [
                ...$this->generated,
                ...match ( $name ) {
                    // 'document'   => $this->parseDocument( $config ),
                    // 'sizes'      => $this->parseSizes( $config ),
                    // 'typography' => $this->parseTypography( $config ),
                    // 'palette'    => $this->parsePalette( $config ),
                    // 'box'        => $this->parseBox( $config ),
                    default => [],
                },
            ];
        }

        return $this;
    }

    /**
     * Parse a provided filepath string into an array.
     *
     * @param string $file
     *
     * @return array
     */
    private function themeFromConfig( string $file ) : array
    {
        $file = $this->pathfinder->getPath( $file );

        if ( ! $file || ! $file->exists ) {
            throw new InvalidArgumentException( 'The theme configuration file does not exists.' );
        }

        $config = match ( $file->extension ) {
            'php'   => require $file->path,
            'yaml'  => Yaml::parseFile( $file->path ),
            'json'  => \json_decode( $file->read ),
            default => E_Value::error(
                'The theme configuration file {path} does not have a valid extension. {extension}',
                [
                    'path'      => $file->path,
                    'extension' => $file->extension,
                    'accepted'  => ['php', 'yaml', 'json'],
                ],
            ),
        };

        if ( ! \is_array( $config ) ) {
            E_Value::error(
                'The theme configuration file {path} does not produce a valid array.',
                [
                    'path'      => $file->path,
                    'extension' => $file->extension,
                    'accepted'  => ['php', 'yaml', 'json'],
                ],
            );
        }

        return ['name' => \strrchr( \basename( $file ), '.', true ), ...$config];
    }

    // ::: MISC

}