<?php

declare(strict_types=1);

namespace Core\Latte;

use Core\Latte\Compiler\NodeCompiler;
use Latte\Compiler\Node;
use Latte\Runtime\HtmlStringable;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
interface RuntimeRenderInterface extends HtmlStringable
{
    /**
     * @param NodeCompiler $node
     *
     * @return Node
     */
    public static function nodeCompiler( NodeCompiler $node ) : Node;

    /**
     * Returns HTML.
     *
     * - Handles provided arguments.
     * - Instantiates the parent {@see __construct} method.
     * - Returns the {@see __toString} function.
     *
     * @return string
     */
    public static function runtimeRender() : string;

    /**
     * # ❗
     * Ensure all HTML is properly escaped and valid before returning this method.
     *
     * @return string of valid HTML
     */
    public function __toString() : string;
}
