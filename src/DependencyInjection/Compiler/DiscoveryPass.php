<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Core\Console\Output;
use Core\Service\AssetManager\Manifest;
use Core\Settings;
use Support\{Str};
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
        $this->parameterBag = $container->getParameterBag();
        $manifestDefinition = $container->getDefinition( Manifest::class );
        $settingsDefinition = $container->getDefinition( Settings::class );

        $settings = $this->parseParameters();

        $settingsDefinition->addMethodCall( 'setDefault', [$settings] );

        Output::info( 'Set default Settings' );
    }

    private function parseParameters() : array
    {
        $settings = [];

        foreach ( $this->parameterBag->all() as $name => $parameter ) {
            if ( \is_string( $parameter ) && Str::startsWith( $name, ['dir', 'path', 'asset'] ) ) {
                $settings[$name] = $parameter;
            }

            if ( 'kernel.environment' === $name ) {
                $settings[$name] = $parameter;
            }

            if ( 'kernel.debug' === $name ) {
                $settings[$name] = $parameter;
            }

            if ( 'kernel.default_locale' === $name ) {
                $settings[$name] = $parameter;
            }

            if ( 'kernel.enabled_locales' === $name ) {
                $settings[$name] = $parameter;
            }

            if ( 'router.request_context.host' === $name ) {
                $settings[$name] = $parameter;
            }

            if ( 'router.request_context.scheme' === $name ) {
                $settings[$name] = $parameter;
            }

            if ( 'router.request_context.base_url' === $name ) {
                $settings[$name] = $parameter;
            }
        }

        return $settings;
    }
}
