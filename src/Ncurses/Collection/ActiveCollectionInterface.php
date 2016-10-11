<?php

namespace Ncurses\Collection;

interface ActiveCollectionInterface extends CollectionInterface
{
    const SEEK_SET = 1;       // Seek from the start of the collection
    const SEEK_CUR = 2;       // Seek from current position in the collection
    const SEEK_END = 3;       // Seek from the end of the collection

    public function getActiveItem();
    public function isActive($item);

    public function setActiveByKey($key);
    public function setActiveByItem($activeItem);
    public function seekActive($offset, $mode = self::SEEK_SET);
}
