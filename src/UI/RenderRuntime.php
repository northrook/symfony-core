<?php

namespace Core\UI;

use Core\Latte\Compiler\NodeExporter;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\Exception\E_Value;
use Northrook\Interface\Singleton;
use Northrook\Logger\Log;
use Support\{Normalize};
use Northrook\Trait\SingletonClass;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use function String\hashKey;
use function Support\classBasename;
use Closure;
use const Cache\{AUTO, DISABLED, EPHEMERAL};

final class RenderRuntime implements Singleton
{
    use SingletonClass;

    /** The method used to trigger a render callback from a .latte template */
    private const string METHOD = 'runtimeRender';

    /**
     * `[ className => componentName ]`.
     *
     * @var array<class-string, non-empty-string>
     */
    private array $called;

    private array $argumentCache = [];

    /**
     * @param ?CacheInterface               $cache
     * @param Closure                       $iconPack
     * @param array{class-string, callable} $argumentCallback
     */
    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private readonly Closure         $iconPack,
        private array                    $argumentCallback = [],
    ) {
        $this->instantiateSingleton();
    }

    /**
     * @param string                  $className
     * @param array<array-key, mixed> $arguments
     * @param null|int                $cache
     *
     * @return null|string
     */
    public function __invoke(
        string $className,
        array  $arguments = [],
        ?int   $cache = AUTO,
    ) : ?string {

        if ( ! $this->validate( $className ) ) {
            return null;
        }

        // DEBUGGING
        $cache = DISABLED;

        $this->registerInvocation( $className );

        $arguments = $this->invokedArguments( $className, $arguments );

        if ( EPHEMERAL <= $cache || ! $this->cache ) {
            return [$className, $this::METHOD]( ...$arguments );
        }

        try {
            return $this->cache->get(
                Normalize::key( [$className, hashKey( $arguments )], '.' ),
                function( CacheItem $item ) use ( $className, $arguments, $cache ) : string {
                    $item->expiresAfter( $cache );
                    return [$className, $this::METHOD]( ...$arguments );
                },
            );
        }
        catch ( \Psr\Cache\InvalidArgumentException $exception ) {
            Log::exception( $exception );
            return null;
        }
    }

    public static function getIconPack() : IconPack
    {
        return ( RenderRuntime::$__instance->iconPack )();
    }

    public static function auxiliaryNode(
        string $renderName,
        array  $arguments = [],
        ?int   $cache = AUTO,
    ) : AuxiliaryNode {

        $export = new NodeExporter();
        return new AuxiliaryNode(
            static fn() : string => <<<EOD
                echo \$this->global->render->__invoke(
                    className : {$export->string( $renderName )},
                    arguments : {$export->arguments( $arguments )},
                    cache     : {$export->cacheConstant( $cache )},
                );
                EOD,
        );

        //
        // return new AuxiliaryNode(
        //         static fn() : string => <<<'EOD'
        //         echo $this->global->render->__invoke(
        //                         className:
        //         EOD.NodeExporter::string( $renderName ).<<<'EOD'
        //         ,
        //                         arguments:
        //         EOD.NodeExporter::arguments( $arguments ).<<<'EOD'
        //         ,
        //                         cache    :
        //         EOD.NodeExporter::cacheConstant( $cache ).<<<'EOD'
        //
        //                      );
        //         EOD,
        // );
    }

    public static function registerInvocation( string $className ) : void
    {
        \assert( isset( RenderRuntime::$__instance ) );

        if ( isset( RenderRuntime::$__instance->called[$className] ) ) {
            return;
        }
        RenderRuntime::$__instance->called[$className] = classBasename( $className );
    }

    private function invokedArguments( string $className, array $arguments ) : array
    {
        if ( \array_key_exists( $className, $this->argumentCallback ) ) {
            $cacheKey  = "{$className}:".hashKey( $arguments );
            $arguments = $this->argumentCache[$cacheKey] ??= ( $this->argumentCallback[$className] )( $arguments );
        }

        return $arguments;
    }

    /**
     * @param class-string $className
     * @param callable     $callback
     *
     * @return void
     */
    public function addArgumentCallback( string $className, callable $callback ) : void
    {
        $this->argumentCallback[$className] = $callback;
    }

    public function getCalledInvocations() : array
    {
        return $this->called;
    }

    private function validate( string $className ) : bool
    {
        if ( ! \method_exists( $className, $this::METHOD ) ) {
            E_Value::error(
                'Runtime invocation of {className} aborted; the class does not have the {method} method.',
                [
                    'className' => $className,
                    'method'    => $this::METHOD,
                ],
            );
            return false;
        }
        return true;
    }
}
