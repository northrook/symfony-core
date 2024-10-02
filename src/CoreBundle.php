<?php

declare(strict_types=1);

namespace Core;

use Core\DependencyInjection\Compiler\ApplicationCompilerPass;
use Override;
use Support\Normalize;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Assert\isCLI;

final class CoreBundle extends AbstractBundle
{
    private const array CONFIG = [
        // '../config/application.php',
        '../config/controllers.php',
        '../config/settings.php',
        '../config/services.php',
        '../config/telemetry.php',
    ];

    #[Override]
    public function getPath() : string
    {
        return \dirname( __DIR__ );
    }

    #[Override]
    public function boot() : void
    {
        dump( __FUNCTION__.' pre' );
        parent::boot();
        dump( __FUNCTION__.' post' );
        new App(
            $this->container->getParameter( 'kernel.environment' ),
            $this->container->getParameter( 'kernel.debug' ),
        );
    }

    #[Override]
    public function shutdown() : void
    {
        parent::shutdown();
    }

    #[Override]
    public function build( ContainerBuilder $container ) : void
    {
        parent::build( $container );

        if ( ! isCLI() ) {
            return;
        }

        $container->addCompilerPass( new ApplicationCompilerPass() );
    }

    /**
     * @param array<array-key, mixed> $config
     * @param ContainerConfigurator   $container
     * @param ContainerBuilder        $builder
     *
     * @return void
     */
    #[Override]
    public function loadExtension(
        array                 $config,
        ContainerConfigurator $container,
        ContainerBuilder      $builder,
    ) : void {
        $this->setCoreParameters( $builder );

        \array_map( [$container, 'import'], $this::CONFIG );
    }

    private function setCoreParameters( ContainerBuilder $builder ) : void
    {
        $directoryParameters = [
            'dir.root'           => '%kernel.project_dir%',
            'dir.var'            => '%dir.root%/var',
            'dir.assets'         => '%dir.root%/assets',
            'dir.templates'      => '%dir.root%/templates',
            'dir.public'         => '%dir.root%/public',
            'dir.public.assets'  => '%dir.root%/public/assets',
            'dir.core'           => \dirname( __DIR__ ),
            'dir.core.assets'    => '%dir.core%/assets',
            'dir.core.templates' => '%dir.core%/templates',
            'dir.cache'          => '%dir.var%/cache',
            'dir.cache.latte'    => '%dir.cache%/latte',
        ];

        foreach ( $directoryParameters as $name => $value ) {
            $builder->setParameter( $name, Normalize::path( $value ) );
        }
    }
}
