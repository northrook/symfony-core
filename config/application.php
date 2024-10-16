<?php

// -------------------------------------------------------------------
// config\application
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\Service\{Request, ToastService};
use Northrook\Latte;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

return static function( ContainerConfigurator $container ) : void {

    $core_services = [
        // Core
        Request::class      => service( Request::class ),
        Latte::class        => service( Latte::class ),
        ToastService::class => service( ToastService::class ),

        // Security
        TokenStorageInterface::class         => service( 'security.token_storage' )->nullOnInvalid(),
        AuthorizationCheckerInterface::class => service( 'security.authorization_checker' ),
    ];

    $container->services()
        /** @used-by ServiceContainer */
        ->set( 'core.service_locator' )
        ->tag( 'container.service_locator' )
        ->args( [$core_services] );
};
