<?php

// -------------------------------------------------------------------
// config\latte
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Latte\Extension\UrlGeneratorExtension;
use Core\Latte\Parameters;
use Northrook\Latte;
use Northrook\Latte\Extension\{CacheExtension, FormatterExtension, OptimizerExtension};
use Northrook\UI\Compiler\Latte\UiCompileExtension;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use const Cache\AUTO;

return static function( ContainerConfigurator $container ) : void {
    $container->parameters()->set(
        'config.latte',
        [
            'autoRefresh' => false,
            'cacheTTL'    => AUTO,
        ],
    );

    $container->services()

            // Template cache
        ->set( 'cache.latte', PhpFilesAdapter::class )
        ->args( ['latte', 0, '%kernel.cache_dir%/latte'] )
        ->tag( 'cache.pool' )

            // The Latte Environment and Renderer
        ->set( Latte::class )
        ->args(
            [
                param( 'dir.root' ),
                param( 'dir.cache.latte' ),
                param( 'kernel.default_locale' ),
                service( 'debug.stopwatch' )->nullOnInvalid(),
                service( 'logger' )->nullOnInvalid(),
                param( 'kernel.debug' ),
            ],
        )
        ->call( 'addGlobalVariable', ['get', service( Parameters::class )] )
        ->call( 'addExtension', [service( UrlGeneratorExtension::class )] )
        ->call( 'addTemplateDirectory', [param( 'dir.templates' ), 100] )
        ->call( 'addTemplateDirectory', [param( 'dir.core.templates' ), 10] )

            // Global Parameters
        ->set( Parameters::class )
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
        ->set( UiCompileExtension::class )
        ->args( [service( 'core.latte.cache' )->nullOnInvalid()] )

        // Provides a URL and Path resolver
        ->set( UrlGeneratorExtension::class )
        ->args( [service( 'router' )] )

            // Cache integration
        ->set( CacheExtension::class )
        ->args(
            [
                service( 'cache.latte' )->nullOnInvalid(),
                service( 'logger' )->nullOnInvalid(),
            ],
        );
};
