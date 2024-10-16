<?php

// -------------------------------------------------------------------
// config\assets
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{AssetManager, Request};
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

return static function( ContainerConfigurator $container ) : void {

    $container->services()

        //
        ->set( 'cache.assets', PhpFilesAdapter::class )
        ->args( ['assets', 0, '%dir.cache%/assets'] )
        ->tag( 'cache.pool' )

        // AssetManager
        ->set( AssetManager::class )
        ->args( [
            service( Request::class ),
            service( 'cache.assets' ),
            service( 'parameter_bag' ),
            param( 'path.asset_inventory' ),
            param( 'path.asset_manifest' ),
        ] );
};