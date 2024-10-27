<?php

declare(strict_types=1);

namespace Core;

use Core\DependencyInjection\Compiler\{ApplicationPass, AssetManifestPass};
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
        '../config/controllers/admin.php',
        '../config/controllers/public.php',
        '../config/latte.php',
        '../config/security.php',
        '../config/services.php',
        '../config/response.php',
        '../config/telemetry.php',
        '../config/theme.php',
        '../config/ui.php',
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

        // Generate application config files and update kernel and public index files
        $container->addCompilerPass(
            pass : new ApplicationPass(),
            type : PassConfig::TYPE_OPTIMIZE,
        );

        // Assign default asset parameters, preload the %asset.manifest% file
        // $container->addCompilerPass(
        //     pass : new AssetManifestPass( $container->getParameterBag() ),
        //     type : PassConfig::TYPE_OPTIMIZE,
        // );
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
        $parameters = [
            'dir.root'   => '%kernel.project_dir%',
            'dir.var'    => '%dir.root%/var',
            'dir.public' => '%dir.root%/public',
            'dir.core'   => \dirname( __DIR__ ),

            // Assets
            'dir.assets'           => '%dir.root%/assets',
            'dir.public.assets'    => '%dir.root%/public/assets',
            'dir.assets.storage'   => '%dir.root%/var/assets',
            'path.asset_inventory' => '%dir.root%/var/assets/inventory.array.php',
            'path.asset_manifest'  => '%dir.root%/var/assets/manifest.array.php',
            'dir.core.assets'      => '%dir.core%/assets',
            'dir.assets.themes'    => '%dir.core%/assets',

            // Templates
            'dir.templates'      => '%dir.root%/templates',
            'dir.core.templates' => '%dir.core%/templates',

            // Cache
            'dir.cache'       => '%kernel.cache_dir%',
            'dir.cache.latte' => '%kernel.cache_dir%/latte',

            // Themes
            'path.theme.core' => '%dir.core%/config/themes/core.php',

            // Settings DataStore
            'path.settings_store' => '%dir.var%/settings.array.php',
        ];

        foreach ( $parameters as $name => $value ) {
            $builder->setParameter( $name, Normalize::path( $value ) );
        }
    }
}
