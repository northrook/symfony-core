<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Core\Console\Output;
use Core\Service\AssetManager\Manifest;
use Core\Settings;
use Northrook\Resource\Path;
use Support\{Normalize, Str};
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Override;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class DiscoveryPass implements CompilerPassInterface
{
    private string $projectDirectory;

    private ParameterBagInterface $parameterBag;

    #[Override]
    public function process( ContainerBuilder $container ) : void
    {
        $this->projectDirectory = $container->getParameter( 'kernel.project_dir' );
        $this->parameterBag     = $container->getParameterBag();
        $manifestDefinition     = $container->getDefinition( Manifest::class );
        $settingsDefinition     = $container->getDefinition( Settings::class );

        [$paths, $settings] = $this->parseParameters();

        dump(
            $manifestDefinition,
            $settingsDefinition,
            $settings,
            $paths,
            $this->parameterBag->all(),
        );

        Output::info( 'Using project directory: '.$this->projectDirectory );

    }

    private function parseParameters() : array
    {
        $paths    = [];
        $settings = $this->parameterBag->all();

        foreach ( $settings as $name => $parameter ) {
            if ( \is_string( $parameter ) && Str::startsWith( $name, ['dir', 'path', 'asset'] ) ) {
                $paths[$name] = $parameter;
            }
        }

        return [$paths, $settings];
    }

    /**
     *  Get path parameters from the {@see ParameterBag}.
     *
     * - Parses through all `string` parameters
     * - Only keys containing `dir` or `path` will be considered
     * - Only values starting with the {@see projectDir} are used
     *
     * @return array
     */
    private function getPathEntries() : array
    {
        $paths = [];

        foreach ( $this->parameterBag->all() as $name => $parameter ) {

        }

        $paths = \array_filter(
            array    : $this->parameterBag->all(),
            callback : fn( $value, $key ) => \is_string( $value )
                                                 && ( \str_starts_with( $key, 'dir' )
                                                      || \str_starts_with( $key, 'path' ) )
                                                 && \str_starts_with( $value, $this->projectDirectory ),
            mode     : ARRAY_FILTER_USE_BOTH,
        );

        // Sort and normalise
        foreach ( $paths as $key => $value ) {
            // Simple sorting; unsetting 'dir' and 'path' prefixed keys, appending them after all Symfony-defined directories
            if ( \str_starts_with( $key, 'dir' ) || \str_starts_with( $key, 'path' ) ) {
                unset( $paths[$key] );
            }

            // Normalise each path
            $paths[$key] = Normalize::path( $value );
        }

        return $paths;
    }

    private function path( string $fromProjectDir ) : Path
    {
        return new Path( "{$this->projectDirectory}/{$fromProjectDir}" );
    }
}
