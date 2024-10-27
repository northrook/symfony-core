<?php

namespace Core\Latte\Compiler\Component;

use Core\Latte\Compiler\NodeCompiler\PrintedNode;
use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\Html\{AttributeNode, ElementNode};
use Latte\Compiler\Nodes\TextNode;
use Latte\Essential\Nodes\PrintNode;

final readonly class ComponentArguments
{
    public array $attributes;

    public array $variables;

    public function __construct( ElementNode $node )
    {

        $attributes = [];
        $variables  = [];

        foreach ( $node->attributes->children as $index => $attribute ) {
            if ( ! $attribute instanceof AttributeNode ) {
                continue;
            }

            if ( $attribute->name instanceof TextNode ) {
                $name              = NodeHelpers::toText( $attribute->name );
                $value             = NodeHelpers::toText( $attribute->value );
                $attributes[$name] = $value;

                continue;
            }

            if ( $attribute->name instanceof PrintNode ) {
                $attribute       = new PrintedNode( $attribute->name );
                $key             = \trim( $attribute->variable, '$' );
                $variables[$key] = $attribute->expression;
            }
        }

        $this->attributes = $attributes;
        $this->variables  = $variables;
    }
}
