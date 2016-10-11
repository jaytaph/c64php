<?php

namespace Ncurses\Collection;

class Collection implements \IteratorAggregate, CollectionInterface
{
    /** @var array */
    protected $items = array();

    /**
     * Returns traversable object of all items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Adds an item to the collection.
     *
     * @param null  $key  Key to store the item under or null for numeric storage (like arrays)
     * @param mixed $item Item to store
     */
    public function addItem($key = null, $item)
    {
        if ($key == null) {
            $this->items[] = $item;
        } else {
            $this->items[$key] = $item;
        }
    }

    /**
     * Returns array of all items.
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Returns item found on specific key or null when nothing found.
     *
     * @param mixed $key Key to search
     *
     * @return array|null
     */
    public function getItem($key)
    {
        if ($this->hasItem($key)) {
            return $this->items[$key];
        }

        return;
    }

    /**
     * Returns true when key is found in the collection.
     *
     * @param mixed $key Key to search
     *
     * @return bool
     */
    public function hasItem($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * Removes item from list.
     *
     * @param mixed $key Key to search
     */
    public function removeItem($key)
    {
        if (!$this->hasItem($key)) {
            return;
        }

        unset($this->items[$key]);
    }

    /**
     * Returns number of items in collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }
}
