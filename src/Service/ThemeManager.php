<?php

namespace Core\Service;

use Northrook\Exception\E_Value;
use Northrook\Resource\Path;
use Symfony\Component\Yaml\Yaml;
use Tempest\Highlight\Theme;
use InvalidArgumentException;

final class ThemeManager
{
    private array $status = [];

    private array $config = [];

    public function __construct(
        private Pathfinder $pathfinder,
    ) {}

    /**
     * Provide an array in the Core Themes format.
     *
     * Usually from the ~/config/themes/_theme-file_.yaml|php
     *
     * @param array|string $theme
     *
     * @return $this
     */
    public function useTheme( string|array $theme ) : self
    {
        $this->config = \is_string( $theme ) ? $this->themeFromConfig( $theme ) : $theme;

        return $this;
    }

    public function generateVariables() : self
    {
        if ( empty( $this->config ) ) {
            $this->status[__METHOD__] = 'Using default theme configuration.';
            $this->themeFromConfig( $this->pathfinder->get( 'path.theme.core' ) );
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
        $file = new Path( $file );

        if ( ! $file->exists ) {
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

        return $config;
    }
}
