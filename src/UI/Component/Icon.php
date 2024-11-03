<?php

namespace Core\UI\Component;

use Core\Latte\Compiler\NodeCompiler;
use Core\UI\{AbstractComponent, RenderRuntime};
use Core\Service\IconService;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Northrook\HTML\Element;
use const Support\EMPTY_STRING;

final class Icon extends AbstractComponent
{
    private readonly string $icon;

    public function __construct(
        protected string         $tag,
        string                   $icon,
        protected readonly array $attributes = [],
    ) {
        // if ( ! \str_starts_with( $icon, '<svg' ) ) {
        //     $this->icon = $this->iconPack()->get( $icon, $tag ? [] : $attributes );
        // }
        // else {
        //     $this->icon = $icon;
        // }
            $this->icon = $icon;
    }

    protected function build() : string
    {
        if ( 'svg' !== $this->tag ) {
            return (string) new Element( $this->tag, $this->attributes, $this->icon );
        }

        return $this->icon;
    }

    public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        $tag        = $node->tag( 'i', 'span' );
        $attributes = $node->attributes();

        $get = $node->arguments()['get'] ?? $attributes['get'] ?? null;

        unset( $attributes['get'] );

        $icon = 'default';
        // $icon = StaticServices::get( IconService::class )->getIconPack(  )->get( $get, $tag ? [] : $attributes );

        return RenderRuntime::auxiliaryNode(
            renderName : Icon::class,
            arguments  : [
                $tag,
                $icon,
                $attributes,
            ],
        );
    }

    public static function runtimeRender(
        string $tag = 'svg',
        string $get = EMPTY_STRING,
        array  $attributes = [],
    ) : string {
        return (string) new Icon( $tag, $get, $attributes );
    }
}
