<?php

// -------------------------------------------------------------------
// config\assets
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Response\AssetResolver;
use Core\Service\{AssetManager, Request};
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        //
        ->set( 'cache.assets', PhpFilesAdapter::class )
        ->args( ['assets', 0, '%dir.cache%/assets'] )
        ->tag( 'cache.pool' )

        // AssetResolver
        ->set( AssetResolver::class )
        ->args( [
            '%dir.public.assets%',
            [
                'app'  => '%dir.assets%',
                'core' => '%core.assets%',
            ],
            '%path.asset_manifest%',
            '%dir.assets.storage%',
            service( 'cache.assets' ),
        ] )

        // AssetManager
        // DEPRECATED
        ->set( AssetManager::class )
        ->args( [
            service( Request::class ),
            service( 'cache.assets' ),
            service( 'parameter_bag' ),
            param( 'path.asset_inventory' ),
            param( 'path.asset_manifest' ),
        ] );
};
