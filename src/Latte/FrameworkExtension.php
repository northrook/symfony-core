<?php

namespace Core\Latte;

use Core\Latte\Compiler\{NodeCompiler, NodeCompilerMethods};
use Latte\Compiler\{Node, NodeTraverser};
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\TemplateNode;
use Core\UI\{Component\Button, Component\Code, Component\Heading, Component\Icon, RenderRuntime};
use Latte;
use Override;

final class FrameworkExtension extends Latte\Extension
{
    use NodeCompilerMethods;

    public function __construct( public readonly RenderRuntime $runtime ) {}

    #[Override]
    public function getPasses() : array
    {
        return [
            self::class => fn( TemplateNode $templateNode ) => (
            new NodeTraverser() )->traverse( $templateNode, [$this, 'parseTemplate'] )(),
        ];
    }

    public function parseTemplate( Node $node ) : int|Node
    {
        if ( $node instanceof ExpressionNode ) {
            return NodeTraverser::DontTraverseChildren;
        }

        if ( ! $node instanceof ElementNode ) {
            return $node;
        }

        $component = new NodeCompiler( $node );

        $parsed = match ( true ) {
            $this::isElement( $node, 'code' ) => Code::nodeCompiler( $component ),
            $this::isHeading( $node )         => Heading::nodeCompiler( $component ),
            // $this::isImage( $node )             => Image::nodeCompiler( $node ),
            // $this::isElement( $node, 'a' )      => Anchor::nodeCompiler( $node ),
            // $this::isElement( $node, 'code' )   => Code::nodeCompiler( $node ),
            $component->is( 'button' )          => Button::nodeCompiler( $component ),
            $component->is( 'icon' )            => Icon::nodeCompiler( $component ),
            // $component->is( 'menu' )            => Menu::nodeCompiler( $component ),
            // $component->is( 'breadcrumbs' )     => Breadcrumbs::nodeCompiler( $component ),
            // $component->is( 'ui:notification' ) => Notification::nodeCompiler( $component ),
            default                             => null,
        };

        return $parsed ?? match ( $node->name ) {
            // 'ui:breadcrumbs' => Breadcrumbs::nodeCompiler( $component ),
            // 'ui:notification', 'ui:toast' => Notification::nodeCompiler( $node ),
            default => $node,
        };
    }

    #[Override]
    public function getProviders() : array
    {
        return [
            'render' => $this->runtime,
        ];
    }
}
