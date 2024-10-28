<?php

namespace Core\UI\Component;

use Core\Latte\Compiler\NodeCompiler;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Core\UI\{AbstractComponent, RenderRuntime};
use Northrook\HTML\Element;
use Northrook\Logger\Log;
use Support\Str;
use Tempest\Highlight\Highlighter;

use const Support\{EMPTY_STRING, WHITESPACE};
// TODO : Copy-to-clipboard integration - toggle via attr copyToClipboard="true/false|line"
//        Block will enable this by default, inline will not.
//        Allow copy whole block, and line-by-line

class Code extends AbstractComponent
{
    protected const string
        INLINE = 'inline',
        BLOCK  = 'block';

    protected readonly Element $component;

    public function __construct(
        private string           $string,
        private readonly ?string $language = null,
        private ?string          $type = null,
        private readonly bool    $tidyCode = true,
        array                    $attributes = [],
    ) {

        if ( Code::INLINE === ( $this->type ??= Code::INLINE ) ) {
            $this->component = new Element( 'code', $attributes );
            $this->component->class( 'inline', prepend : true );
            $this->string = \preg_replace( '#\s+#', WHITESPACE, $this->string );
        }
        else {
            $this->blockCodeContent();
            $this->component = new Element( 'pre', $attributes );
            $this->component->class( 'block', prepend : true );
        }
    }

    private function blockCodeContent() : void
    {
        $leftPadding = [];
        $lines       = \explode( "\n", $this->string );

        foreach ( $lines as $line ) {
            $line = \str_replace( "\t", '    ', $line );
            if ( \preg_match( '#^(\s+)#m', $line, $matches ) ) {
                $leftPadding[] = \strlen( $matches[0] );
            }
        }

        $trimSpaces = \min( $leftPadding );

        foreach ( $lines as $line => $string ) {
            if ( \str_starts_with( $string, \str_repeat( ' ', $trimSpaces ) ) ) {
                $string = \substr( $string, $trimSpaces );
            }

            \preg_match( '#^(\s*)#m', $string, $matches ) ;
            $leftPad      = \strlen( $matches[0] ?? 0 );
            $string       = \str_repeat( ' ', $leftPad ).\trim( $string );
            $lines[$line] = \str_replace( '    ', "\t", $string );
        }

        $this->string = \implode( "\n", $lines );
    }

    protected function build() : string
    {
        if ( $this->tidyCode ) {
            $this->string = Str::replaceEach(
                [' ), );' => ' ) );'],
                $this->string,
            );
        }

        if ( $this->language ) {
            $content = "{$this->highlight( $this->string )}";
            $lines   = \substr_count( $content, PHP_EOL );
            $this->component->attributes( 'language', $this->language );

            if ( $lines ) {
                $this->component->attributes( 'line-count', (string) $lines );
            }
        }
        else {
            $content = $this->string;
        }

        // dump( $content);
        // dump( $this );
        return (string) $this->component->content( $content );
    }

    final protected function highlight( string $code, ?int $gutter = null ) : string
    {
        if ( ! $this->language || ! $code ) {
            return EMPTY_STRING;
        }

        if ( Code::INLINE === $this->type && $gutter ) {
            Log::warning( 'Inline code snippets cannot have a gutter' );
            $gutter = null;
        }

        $highlighter = new Highlighter();
        if ( $gutter ) {
            return $highlighter->withGutter( $gutter )->parse( $code, $this->language );
        }
        return $highlighter->parse( $code, $this->language );
    }

    final public static function nodeCompiler( NodeCompiler $node ) : AuxiliaryNode
    {
        $attributes = $node->attributes();
        $exploded   = \explode( ':', $node->name );

        $tag      = \array_shift( $exploded );
        $type     = 'pre' === $tag ? 'block' : 'inline';
        $language = null;
        $tidyCode = \array_key_exists( 'tidyCode', $attributes );
        unset( $attributes['tidyCode'] );

        $hasType = \array_search( 'inline', $exploded )
                ?: \array_search( 'block', $exploded );

        if ( \is_int( $hasType ) ) {
            $type = $exploded[$hasType];
            unset( $exploded[$hasType] );
        }

        if ( ! empty( $exploded ) ) {
            $language = \implode( ', ', $exploded );
            unset( $exploded );
        }

        if ( \array_key_exists( 'lang', $attributes ) ) {
            $language ??= $attributes['lang'];
            unset( $attributes['lang'] );
        }

        // dd(
        //         $node->htmlContent(),
        //         $language,
        //         $type,
        //         $tidyCode,
        //         $attributes,);
        return RenderRuntime::auxiliaryNode(
            renderName : Code::class,
            arguments  : [
                $node->htmlContent(),
                $language,
                $type,
                $tidyCode,
                $attributes,
            ],
        );
    }

    public static function runtimeRender(
        ?string $string = null,
        ?string $language = null,
        ?string $type = null,
        bool    $tidyCode = true,
        array   $attributes = [],
    ) : string {
        if ( ! $string ) {
            return EMPTY_STRING;
        }

        return (string) new Code( $string, $language, $type, $tidyCode, $attributes );
    }
}
