<?php

namespace Core\UI;

use Core\Latte\Compiler\NodeExporter;
use Core\Service\IconService\IconPack;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\Exception\E_Value;
use Northrook\Logger\Log;
use Support\{Normalize};
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use function String\hashKey;
use function Support\classBasename;
use const Cache\{AUTO, DISABLED, EPHEMERAL};

/*---
IconPack and tracking called/instantiated Components MUST be globally accessible

On __construct of the ResponseHandler, we could set $called as empty, as we will only call after deciding we need to send a response
Components rendered manually through a new Response( 'custom string' ) is the devs own problem.

The IconPack and Toast might benefit from a Facade-like ServiceContainer

----*/

final class RenderRuntime
{
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
     * @param array<class-string, callable> $argumentCallback
     */
    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private array                    $argumentCallback = [],
    ) {}

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
    }

    public function registerInvocation( string $className ) : void
    {
        if ( isset( $this->called[$className] ) ) {
            return;
        }
        $this->called[$className] = classBasename( $className );
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
