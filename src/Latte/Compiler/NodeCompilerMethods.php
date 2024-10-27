<?php

declare(strict_types=1);

namespace Core\Latte\Compiler;

use Latte\CompileException;
use Latte\Compiler\{Node, PrintContext};
use Latte\Compiler\Nodes\{FragmentNode, TextNode};
use Latte\Compiler\Nodes\Html\ElementNode;
use Latte\Essential\Nodes\PrintNode;
use Northrook\HTML\Element\Tag;
use Northrook\Logger\Log;

trait NodeCompilerMethods
{
    /**
     * Check if the provided string is considered a {@see Tag::HEADING}.
     *
     * - Case-insensitive
     *
     * @param Node|string $element
     *
     * @return bool
     */
    protected static function isImage( string|Node $element ) : bool
    {
        if ( $element instanceof Node ) {
            if ( $element instanceof ElementNode ) {
                $element = $element->name;
            }
            else {
                return false;
            }
        }

        return \in_array( \strtolower( $element ), ['img', 'picture'], true );
    }

    /**
     * Check if the provided string is considered a {@see Tag::HEADING}.
     *
     * - Case-insensitive
     *
     * @param Node|string $element
     *
     * @return bool
     */
    protected static function isHeading( string|Node $element ) : bool
    {
        if ( $element instanceof Node ) {
            if ( $element instanceof ElementNode ) {
                $element = $element->name;
            }
            else {
                return false;
            }
        }

        return \in_array( \strtolower( $element ), Tag::HEADING, true );
    }

    /**
     * Check if the provided {@see Node} is a valid {@see ElementNode}.
     *
     * - Case-insensitive
     * - Will match namespaced tags by default
     *
     * @param Node    $node
     * @param ?string $tag
     * @param bool    $strict
     *
     * @return bool
     */
    protected static function isElement( Node $node, ?string $tag = null, bool $strict = false ) : bool
    {
        if ( ! $node instanceof ElementNode ) {
            return false;
        }

        if ( ! $tag ) {
            return true;
        }

        $nodeTag = \strtolower( $node->name );
        $tag     = \strtolower( $tag );

        if ( true === $strict || ! \str_contains( $nodeTag, ':' ) ) {
            return $nodeTag === $tag;
        }
        // dump( "$nodeTag : $tag", \str_starts_with( $nodeTag, $tag ) );

        return \str_starts_with( $nodeTag, $tag );
    }

    protected static function stringifyCodeContent( ElementNode|TextNode|PrintNode $node ) : ?string
    {
        if ( $node instanceof TextNode ) {
            return $node->content;
        }
        if ( $node instanceof PrintNode ) {
            try {
                return $node->print( new PrintContext() );
            }
            catch ( CompileException $exception ) {
                Log::exception( $exception );
            }
        }

        if ( $node->content instanceof FragmentNode ) {
            $string = '';

            foreach ( $node->content->children as $childNode ) {
                if ( $childNode instanceof TextNode && ! \trim( $childNode->content ) ) {
                    continue;
                }

                if ( $childNode instanceof TextNode ) {
                    $string .= $childNode->content;

                    continue;
                }
                if ( $childNode instanceof ElementNode ) {
                    $string .= self::stringifyCodeContent( $childNode );
                }
                if ( $childNode instanceof PrintNode ) {
                    $string .= self::stringifyCodeContent( $childNode );
                }
            }
            return $string;
        }

        return null;
    }
}
