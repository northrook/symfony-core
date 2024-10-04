<?php

// -------------------------------------------------------------------
// config\latte
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Latte\Extension\UrlGeneratorExtension;
use Core\Latte\Parameters;
use Northrook\Latte;
use Northrook\Latte\Extension\CacheExtension;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use const Cache\AUTO;

return static function( ContainerConfigurator $container ) : void {

    $container->parameters()->set( 'config.latte', [
        'autoRefresh' => false,
        'cacheTTL'    => AUTO,
    ] );

    $container->services()

        // Template cache
        ->set( 'cache.latte', PhpFilesAdapter::class )
        ->args( ['latte', 0, '%kernel.cache_dir%/latte'] )
        ->tag( 'cache.pool' )

        // The Latte Environment and Renderer
        ->set( Latte::class )
        ->args(
            [
                param( '%dir.root%' ),
                param( '%dir.cache.latte%' ),
                param( 'kernel.default_locale' ),
                service( 'debug.stopwatch' )->nullOnInvalid(),
                service( 'logger' )->nullOnInvalid(),
                param( 'kernel.debug' ),
            ],
        )
        ->call( 'addGlobalVariable', ['get', service( Parameters::class )] )
        ->call( 'addExtension', [service( UrlGeneratorExtension::class )] )

        // Global Parameters
        ->set( Parameters::class )
        ->args(
            [
                param( 'kernel.environment' ),
                param( 'kernel.debug' ),
                service( 'request_stack' ),
                service( 'security.csrf.token_storage' ),
                service( 'security.csrf.token_manager' ),
            ],
        )

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
