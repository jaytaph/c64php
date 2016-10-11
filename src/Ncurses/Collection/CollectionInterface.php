<?php

namespace Ncurses\Collection;

interface CollectionInterface extends \Countable
{
    public function addItem($key = null, $item);
    public function getItems();
    public function hasItem($key);
    public function removeItem($key);
}
