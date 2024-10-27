<?php

namespace Core\UI\Component;

use Core\Latte\Compiler\NodeCompiler;
use Core\Model\Menu\Menu;
use Core\UI\AbstractComponent;
use Core\UI\RenderRuntime;
use Latte\Compiler\Node;
use Northrook\HTML\Element;
/**
 * @internal
 */
final class Navigation extends AbstractComponent
{
    public function __construct(
        private readonly null|string|Menu $menu,
        private array                           $attributes = [],
        private readonly ?string                $tag = null,
    ) {}

    protected function build() : string
    {
        $this->menu->attributes->add( 'class', 'navigation' );
        if ( $this->tag ) {
            return (string) new Element( $this->tag, $this->attributes, $this->menu->render() );
        }

        if ( $this->menu instanceof Menu ) {
            return (string) $this->menu->render( $this->attributes );
        }

        $menu = new Element( 'ul', $this->attributes, $this->menu );
        return (string) $menu;
    }

    public static function nodeCompiler( NodeCompiler $node ) : Node
    {
        $arguments    = $node->arguments();
        $menuVariable = \array_shift( $arguments );
        // dump( $menuVariable, $arguments );

        return RenderRuntime::auxiliaryNode(
            renderName : Navigation::class,
            arguments  : [
                $menuVariable,
                $node->attributes(),
                $node->tag( 'nav' ),
            ],
        );
    }

    public static function runtimeRender(
        null|string|Menu $items = null,
        array                  $attributes = [],
        ?string                $parent = null,
    ) : string {
        return (string) new Navigation( $items, $attributes, $parent );
    }
}
