<?php

namespace Ncurses\Collection;

class ActiveCollection extends Collection implements ActiveCollectionInterface
{
    /** @var int The current item position */
    protected $currentItemKey = 0;

    /** @var bool When set to true, the seek functionality will wraparound (-1 goes to last element and N+1 goes to first) */
    protected $wrapAround;

    /**
     * @param bool $wrapAround
     */
    public function __construct($wrapAround = false)
    {
        $this->wrapAround = $wrapAround;
    }

    /**
     * Returns current active item.
     *
     * @return null|mixed
     */
    public function getActiveItem()
    {
        $keys = array_keys($this->items);
        if (count($keys) == 0) {
            return;
        }

        return $this->items[$keys[$this->currentItemKey]];
    }

    /**
     * Returns true when item is the active item.
     *
     * @param mixed $item
     *
     * @return bool
     */
    public function isActive($item)
    {
        return $item == $this->getActiveItem();
    }

    /**
     * Sets active item by key.
     *
     * @param string $key
     */
    public function setActiveByKey($key)
    {
        if ($this->hasItem($key)) {
            $keys = array_keys($this->items);
            $this->currentItemKey = array_search($key, $keys);
        }
    }

    /**
     * Sets active item by item.
     *
     * @param mixed $activeItem
     */
    public function setActiveByItem($activeItem)
    {
        foreach ($this->items as $key => $item) {
            if ($item == $activeItem) {
                $this->currentItemKey = $key;
                break;
            }
        }
    }

    /**
     * Seeks active item in the same way as fseek.
     *
     * @see \fseek()
     *
     * @param int $offset offset to search, can be negative as well
     * @param int $mode   One of the SWITCH_* constants
     */
    public function seekActive($offset, $mode = self::SEEK_SET)
    {
        $keys = array_keys($this->items);
        if (count($keys) == 0) {
            // Nothing to do when no items
            return;
        }

        switch ($mode) {
            default :
            case self::SEEK_SET :
                $idx = $offset;
                break;
            case self::SEEK_CUR :
                $idx = $this->currentItemKey + $offset;
                break;
            case self::SEEK_END :
                // Mostly useful when using negative offsets here
                $idx = count($keys) + $offset;
                break;
        }

        if ($this->wrapAround) {
            $idx %= count($keys);
        } else {
            // cap idx when not a wrapped collection
            if ($idx < 0) {
                $idx = 0;
            }
            if ($idx >= count($keys) - 1) {
                $idx = count($keys) - 1;
            }
        }

        $this->currentItemKey = $idx;
    }
}
