<?php

// -------------------------------------------------------------------
// config\latte
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\UI\{ComponentFactory, RenderRuntime};
use Core\Latte\{FrameworkExtension, GlobalVariables};
use Northrook\Latte;
use Northrook\Latte\Extension\{CacheExtension, FormatterExtension, OptimizerExtension};
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use const Cache\AUTO;

return static function( ContainerConfigurator $container ) : void {
    $container->parameters()->set(
        'settings.latte',
        [
            'autoRefresh' => false,
            'cacheTTL'    => AUTO,
        ],
    );

    $container->services()
        ->set( FrameworkExtension::class )
        ->args(
            [
                service( ComponentFactory::class ),
                service( RenderRuntime::class ),
            ],
        )

            // / !!!!

            // Template cache
        ->set( 'cache.latte', PhpFilesAdapter::class )
        ->args( ['latte', 0, '%dir.cache.latte%'] )
        ->tag( 'cache.pool' )

            // The Latte Environment and Renderer
        ->set( Latte::class )
        ->tag( 'core.service_locator' )
        ->args(
            [
                '%dir.root%',
                '%dir.cache.latte%',
                '%kernel.default_locale%',
                '%kernel.debug%',
            ],
        )
        ->call( 'addGlobalVariable', ['get', service( GlobalVariables::class )] )
        ->call(
            'addExtension',
            [
                service( FrameworkExtension::class ),
                // service( UrlGeneratorExtension::class ),
            ],
        )
        ->call( 'addTemplateDirectory', [param( 'dir.templates' ), 100] )
        ->call( 'addTemplateDirectory', [param( 'dir.core.templates' ), 10] )

            // Global Parameters
        ->set( GlobalVariables::class )
        ->args(
            [
                param( 'kernel.environment' ),
                param( 'kernel.debug' ),
                service( 'request_stack' ),
                service( 'security.token_storage' )->ignoreOnInvalid(),
                service( 'security.csrf.token_manager' ),
            ],
        )

            //
        ->set( FormatterExtension::class )
        ->set( OptimizerExtension::class )
            // ->set( UiCompileExtension::class )
            // ->args( [service( 'core.latte.cache' )->nullOnInvalid()] )

            // Provides a URL and Path resolver
            // ->set( UrlGeneratorExtension::class )
            // ->args( [service( 'router' )] )

            // Cache integration
        ->set( CacheExtension::class )
        ->args(
            [
                service( 'cache.latte' )->nullOnInvalid(),
                service( 'logger' )->nullOnInvalid(),
            ],
        );
};
