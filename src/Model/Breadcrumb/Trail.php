<?php

namespace Core\Model\Breadcrumb;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

final class Trail implements Countable, IteratorAggregate
{
    /** @var Item[] */
    private array $breadcrumbs = [];

    public function add(
        string       $title,
        ?string      $href = null,
        array|string $class = [],
        ?string      $icon = null,
    ) : Trail {
        $this->breadcrumbs[] = new Item( $title, $href, (array) $class, $icon );
        return $this;
    }

    public function getBreadcrumbs() : array
    {
        return $this->breadcrumbs;
    }

    public function count() : int
    {
        return \count( $this->breadcrumbs );
    }

    /**
     * @return Traversable<Item>
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator( $this->breadcrumbs );
    }
}
