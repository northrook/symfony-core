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
    #[Override]
    public function getPath() : string
    {
        return \dirname( __DIR__ );
    }

    #[Override]
    public function boot() : void
    {
        parent::boot();
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
        $this->coreParameters( $builder );
    }

    private function coreParameters( ContainerBuilder $builder ) : void
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
