<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Core\Console\Output;
use Core\Service\AssetManager\{Asset, Manifest};
use Core\Settings;
use Northrook\CSS\Stylesheet;
use Support\{Str};
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Override;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class DiscoveryPass implements CompilerPassInterface
{
    private ParameterBagInterface $parameterBag;

    #[Override]
    public function process( ContainerBuilder $container ) : void
    {
        $this->parameterBag = $container->getParameterBag();
        // $manifestDefinition = $container->getDefinition( Manifest::class );
        $settingsDefinition = $container->getDefinition( Settings::class );

        $settings = $this->parseParameters();

        // : Should discover assets in the `dir.assets`, `core.assets`, and `ui.assets` directories by default
        // : Assets loaded from here will be considered as 'core' and cannot be _REMOVED_ only _DISABLED_.

        $settingsDefinition->addMethodCall( 'setDefault', [$settings] );

        $this->generateCoreStyles();

        // $manifestDefinition->addMethodCall( 'register', [
        //     'core', $this->parameterBag->get( 'asset.core.stylesheet' ),
        // ] );
        //
        //
        // foreach(  \glob( $this->parameterBag->get( 'dir.assets' ).'/styles/*.css' ) as $stylesheetPath ) {
        //     $manifestDefinition->addMethodCall( 'register', [
        //             'core', $stylesheetPath,
        //     ] );
        // }

        Output::info( 'Set default Settings' );
    }

    private function coreStylesheetAsset() : Asset
    {
        // register as core.assetID (the generated hash)
        // each asset will have the data attribute data-asset='core', denoted as the 'group type'
        // each asset will have the id='assetID_hash' as a unique identifier
        // When the AssetManager is asked to fetch 'core' or 'ui:button', it will fetch all in tht group.

        $asset = new Asset( 'core' );
        $asset->addSource( \glob( $this->parameterBag->get( 'dir.assets' ).'/styles/*.css' ) );
        return $asset;
    }

    private function generateCoreStyles() : void
    {
        $css = new Stylesheet(
            $this->parameterBag->get( 'asset.core.stylesheet' ),
            \glob( $this->parameterBag->get( 'dir.assets' ).'/styles/*.css' ),
        );

        $css->addReset()
            ->addBaseline()
            ->addDynamicRules();

        $css->save( force: true );
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