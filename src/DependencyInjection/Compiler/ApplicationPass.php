<?php

declare(strict_types=1);

namespace Core\DependencyInjection\Compiler;

use Core\Console\Output;
use JetBrains\PhpStorm\{Deprecated, Language};
use Northrook\Resource\Path;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;
use Override;

final readonly class ApplicationPass implements CompilerPassInterface
{
    private string $projectDirectory;

    #[Override]
    public function process( ContainerBuilder $container ) : void
    {
        $this->projectDirectory = $container->getParameter( 'kernel.project_dir' );

        Output::info( 'Using project directory: '.$this->projectDirectory );

        // TODO : Purge debug.yaml, explain why
        // TODO : Update monolog.yaml

        $this
            ->removeFile( 'config/packages/debug.yaml' )
            ->appKernel( true )
            ->publicIndex( true )
            ->coreControllerRoutes()
            ->appControllerRouteConfiguration()
            ->createConfigServices()
            ->configurePreload();
    }

    public function appKernel( bool $override = false ) : self
    {
        $this->createPhpFile(
            'src/Kernel.php',
            <<<PHP
                <?php
                
                declare(strict_types=1);
                
                namespace App;
                
                use Symfony\Bundle\FrameworkBundle\Kernel as FrameworkKernel;
                use Symfony\Component\HttpKernel\Kernel as HttpKernel;
                
                final class Kernel extends HttpKernel
                {
                    use FrameworkKernel\MicroKernelTrait;
                }

                PHP,
            $override,
        );
        return $this;
    }

    public function publicIndex( bool $override = false ) : self
    {
        $this->createPhpFile(
            'public/index.php',
            <<<PHP
                <?php
                
                declare(strict_types=1);
                
                require_once \dirname(__DIR__).'/vendor/autoload_runtime.php';
                
                return function (array \$context): App\Kernel {
                    return new App\Kernel(\$context['APP_ENV'], (bool) \$context['APP_DEBUG']);
                };

                PHP,
            $override,
        );

        return $this;
    }

    public function coreControllerRoutes() : self
    {
        $routes = [
            // 'core.controller.api' => [
            //     'resource' => '@CoreBundle/config/routes/api.php',
            //     'prefix'   => '/api',
            // ],
            'core.controller.admin' => [
                'resource' => '@CoreBundle/config/routes/admin.php',
                'prefix'   => '/admin',
            ],
            'core.controller.public' => [
                'resource' => '@CoreBundle/config/routes/public.php',
                'prefix'   => '/',
            ],
            // 'core.controller.security' => [
            //     'resource' => '@CoreBundle/config/routes/security.php',
            //     'prefix'   => '/',
            // ],
        ] ;

        $this->createYamlFile( 'config/routes/core.yaml', $routes, true );

        return $this;
    }

    public function appControllerRouteConfiguration( bool $override = false ) : self
    {
        if ( $this->path( 'config/routes.php' )->exists ) {
            return $this;
        }

        $this->removeFile( 'config/routes.yaml' );
        $this->createPhpFile(
            'config/routes.php',
            <<<PHP
                <?php
                
                declare( strict_types = 1 );
                
                use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
                
                return static function ( RoutingConfigurator \$routes ) : void {
                    \$routes->import(
                        [
                            'path'      => '../src/Controller/',
                            'namespace' => 'App\Controller',
                        ],
                        'attribute',
                    );
                };
                PHP,
        );

        return $this;
    }

    public function createConfigServices( bool $override = false ) : self
    {
        if ( ! $this->path( 'config/services.php' )->exists ) {
            return $this;
        }
        $this->removeFile( 'config/services.yaml' );
        $this->createPhpFile(
            'config/services.php',
            <<<PHP
                <?php
                
                declare( strict_types = 1 );
                
                use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
                
                return static function ( ContainerConfigurator \$container ) : void {
                
                    \$services = \$container->services();
                
                    // Defaults for App services.
                    \$services
                        ->defaults()
                        ->autowire()
                        ->autoconfigure();
                
                    \$services
                        // Make classes in src/ available to be used as services.
                        ->load( "App\\\\", __DIR__ . '/../src/' )
                        // We do not want to autowire DI, ORM, or Kernel classes.
                        ->exclude(
                            [
                                __DIR__ . '/../src/DependencyInjection/',
                                __DIR__ . '/../src/Entity/',
                                __DIR__ . '/../src/Kernel.php',
                            ],
                        );
                };
                PHP,
        );
        return $this;
    }

    public function configurePreload( bool $override = false ) : self
    {
        $this->createPhpFile(
            'config/preload.php',
            <<<'PHP'
                <?php
                
                declare(strict_types=1);
                
                if (\file_exists(\dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php')) {
                    \opcache_compile_file(\dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php');
                }
                PHP,
            $override,
        );

        return $this;
    }

    #[Deprecated]
    public function removeDefaultRouteConfiguration() : self
    {
        $this->removeFile( 'config/routes.yaml' );
        return $this;
    }

    private function createYamlFile(
        string $fromProjectDir,
        #[Language( 'PHP' )] mixed  $input,
        bool   $overwrite = false,
    ) : void {
        $path = new Path( "{$this->projectDirectory}/{$fromProjectDir}" );

        if ( $path->exists && false === $overwrite ) {
            return;
        }

        $content = Yaml::dump( $input );

        if ( $content && $path->save( $content ) ) {
            Output::info( "Compiler generated {$fromProjectDir}." );
        }
    }

    private function createPhpFile(
        string $fromProjectDir,
        #[Language( 'PHP' )] string $php,
        bool   $override = false,
    ) : void {
        $path = $this->path( $fromProjectDir );

        if ( $path->exists && false === $override ) {
            return;
        }

        $content = $this->parsePhpString( $php );

        if ( $content && $path->save( $content ) ) {
            Output::info( "Compiler generated {$fromProjectDir}." );
        }
    }

    private function parsePhpString(
        #[Language( 'PHP' )] string $php,
    ) : string {
        if ( ! \str_starts_with( $php, '<?php' ) ) {
            throw new UnexpectedValueException( 'Autoconfigure was provided a PHP string without an opening tag.' );
        }

        $content = \preg_replace(
            pattern     : '#<\?php\s+?(?=\S)#A',
            replacement : "<?php\n\n// Generated by ".$this::class."\n\n",
            subject     : $php,
        );

        if ( ! \is_string( $content ) ) {
            throw new UnexpectedValueException( 'Autoconfigure encountered an unexpected error preparing the PHP string.' );
        }
        return $content;
    }

    private function removeFile( string $fromProjectDir ) : self
    {
        $path = new Path( "{$this->projectDirectory}/{$fromProjectDir}" );

        if ( $path->delete() ) {
            Output::info( "Compiler removed {$fromProjectDir}." );
        }

        return $this;
    }

    private function path( string $fromProjectDir ) : Path
    {
        return new Path( "{$this->projectDirectory}/{$fromProjectDir}" );
    }
}