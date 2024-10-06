<?php

declare(strict_types=1);

namespace Core;

use Core\DependencyInjection\Compiler\{ApplicationPass, AssetManifestPass, SettingsPass};
use Override;
use Support\Normalize;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Assert\isCLI;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

final class CoreBundle extends AbstractBundle
{
    private const array CONFIG = [
        '../config/application.php',
        '../config/assets.php',
        '../config/controllers.php',
        '../config/latte.php',
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
        parent::boot();

        if ( isCLI() ) {
            return;
        }

        new App(
            $this->container->getParameter( 'kernel.environment' ),
            $this->container->getParameter( 'kernel.debug' ),
        );

        $this->container?->get( Settings::class );
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

        // if ( ! isCLI() ) {
        //     return;
        // }

        dump( __FUNCTION__);

        // Generate application config files and update kernel and public index files
        $container->addCompilerPass(
            pass     : new ApplicationPass(),
            type     : PassConfig::TYPE_OPTIMIZE,
            priority : 100,
        );

        // Settings
        $container->addCompilerPass(
                pass     : new SettingsPass( $container->getParameterBag() ),
                type     : PassConfig::TYPE_OPTIMIZE,
                priority : 90
        );

        // Assign default asset parameters, preload the %asset.manifest% file
        $container->addCompilerPass(
            pass     : new AssetManifestPass( $container->getParameterBag() ),
            type     : PassConfig::TYPE_OPTIMIZE,
            priority : 75,
        );
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
            'dir.assets.storage' => '%dir.var%/assets',
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