<?php

namespace Core\Model\Menu;

use JetBrains\PhpStorm\Language;
use Northrook\HTML\Element;
use Northrook\HTML\Element\Attributes;
use Stringable;
use Support\Normalize;

/**
 * This is effectively building the `ol > lo` item stack.
 *
 * Which means it should be expected to exist within a `nav` element when used as a menu,
 * and stand-alone when used as dropdown or possibly breadcrumbs.
 *
 * [Menubar](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/menubar_role)
 *
 *
 *
 *
 * ```
 * <ul role="menubar" class="navigation"> // each Item will wrap in this
 *     <li class="menu-item"> // each Item will be this
 *         <div> // icon, label, buttons, etc
 *             <svg>{icon}</svg>
 *             <a:span>{label}</a:span>
 *             <button aria-expanded>{toggle}</button>
 *         </div>
 *         <ul> // submenu
 *             <li class="menu-item">
 *                 <div class="item"></div>
 *                 <ol></ol> // recursive..
 *             </li class="menu-item">
 *         </ul>
 *     </li class="menu-item">
 * </ul>
 * ```
 */
class Menu implements Stringable
{
    private readonly Element $element;

    private array $items = [];

    public readonly Attributes $attributes;

    public readonly string $name;

    public readonly string $root;

    public readonly ?string $id;

    public function __construct(
        string                  $name,
        string                  $root,
        public readonly ?string $current = null,
        null|string|false       $id = null,
        array                   $attributes = [],
    ) {
        $this->element    = new Element( 'ul', $attributes );
        $this->attributes = $this->element->attributes;
        $this->name       = Normalize::key( $name );
        $this->root       = Normalize::url( $root );
        $this->id         = false !== $id ? Normalize::key( $id ?? $this->name ) : null;

        if ( $this->id ) {
            $this->element->id( $this->id );
        }
    }

    final public function items( Item ...$menu ) : static
    {
        foreach ( $menu as $item ) {
            $item->parent( $this );
            $this->items[$item->id] = $item;
        }
        return $this;
    }

    public static function html(
        #[Language( 'HTML' )] string $html,
    ) : void {}

    public static function link(
        string       $title,
        string       $href,
        string|false $icon = false,
        ?string      $description = null,
        ?string      $id = null,
        bool         $render = true,
        array        $attributes = [],
    ) : Item {
        return new Item(
            title       : $title,
            href        : $href,
            icon        : $icon,
            description : $description,
            id          : $id,
            canRender   : $render,
            attributes  : $attributes,
        );
    }

    public static function item(
        string       $title,
        ?string      $href = null,
        string|false $icon = false,
        ?string      $description = null,
        ?string      $id = null,
        bool         $render = true,
        array        $attributes = [],
    ) : Item {
        return new Item(
            title       : $title,
            href        : $href,
            icon        : $icon,
            description : $description,
            id          : $id,
            isLink      : false,
            canRender   : $render,
            attributes  : $attributes,
        );
    }

    final public function render( array $attributes = [], ?string $tag = null ) : string
    {
        if ( $tag ) {
            $this->element->tag( $tag );
        }

        foreach ( $this->items as $item ) {
            $this->element->content( $item->render( $this->root ) );
        }

        $this->element->attributes->merge( $attributes );

        $this->element->class( 'menu', prepend : true );

        return $this->element->toString( PHP_EOL );
    }

    public function __toString() : string
    {
        return $this->render();
    }
}
