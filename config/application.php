<?php

// -------------------------------------------------------------------
// config\application
// -------------------------------------------------------------------

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Core\DependencyInjection\ServiceContainer;
use Core\Security\Security;
use Core\Response\{Document, Parameters};
use Core\Service\{Headers, Pathfinder, Request, ToastService};
use Northrook\Latte;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

return static function( ContainerConfigurator $container ) : void {

    $core_services = [
        // Core
        Security::class     => service( Security::class ),
        Pathfinder::class   => service( Pathfinder::class ),
        Request::class      => service( Request::class ),
        Latte::class        => service( Latte::class ),
        ToastService::class => service( ToastService::class ),

        // Document
        Document::class     => service( Document::class ),
        Parameters::class   => service( Parameters::class ),
        Headers::class      => service( Headers::class ),

        // Symfony
        RouterInterface::class     => service( 'router' ),
        HttpKernelInterface::class => service( 'http_kernel' ),
        SerializerInterface::class => service( 'serializer' ),

        // Security
        TokenStorageInterface::class         => service( 'security.token_storage' )->nullOnInvalid(),
        CsrfTokenManagerInterface::class     => service( 'security.csrf.token_manager' )->nullOnInvalid(),
        AuthorizationCheckerInterface::class => service( 'security.authorization_checker' ),
    ];

    $container->services()
        /** @used-by ServiceContainer */
        ->set( 'core.service_locator' )
        ->tag( 'container.service_locator' )
        ->args( [$core_services] );

    //
    // ->set( ServiceContainer::class )
    // ->args( [service( 'core.service_locator' )] );
};