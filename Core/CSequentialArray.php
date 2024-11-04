<?php declare(strict_types=1);
/**
 * CSequentialArray.php
 *
 * (C) 2024 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Core;

/**
 * CSequentialArray is an extension of CArray designed specifically for
 * zero-based, sequentially indexed arrays.
 */
class CSequentialArray extends CArray
{
    /**
     * Adds an element to the end of the array.
     *
     * @param mixed $element
     *   The element to add to the array.
     * @return CSequentialArray
     *   The current instance.
     */
    public function PushBack(mixed $element): CSequentialArray
    {
        $this->value[] = $element;
        return $this;
    }

    /**
     * Adds an element to the beginning of the array.
     *
     * @param mixed $element
     *   The element to add to the array.
     * @return CSequentialArray
     *   The current instance.
     */
    public function PushFront(mixed $element): CSequentialArray
    {
        \array_unshift($this->value, $element);
        return $this;
    }

    /**
     * Removes and returns the last element of the array.
     *
     * @return mixed
     *   The last element, or `null` if the array is empty.
     */
    public function PopBack(): mixed
    {
        return \array_pop($this->value);
    }

    /**
     * Removes and returns the first element of the array.
     *
     * @return mixed
     *   The first element, or `null` if the array is empty.
     */
    public function PopFront(): mixed
    {
        return \array_shift($this->value);
    }

    /**
     * Inserts a new element before an existing element at a specified offset.
     *
     * @param int $offset
     *   The zero-based offset before which the new element should be inserted.
     * @param mixed $element
     *   The new element to insert.
     * @return CSequentialArray
     *   The current instance.
     * @throws \OutOfRangeException
     *   If the offset is out of range.
     */
    public function InsertBefore(int $offset, mixed $element): CSequentialArray
    {
        if ($offset < 0) {
            throw new \OutOfRangeException('Offset cannot be negative.');
        }
        if ($offset > \count($this->value)) {
            throw new \OutOfRangeException('Offset exceeds array size.');
        }
        \array_splice($this->value, $offset, 0, [$element]);
        return $this;
    }

    /**
     * Inserts a new element after an existing element at a specified offset.
     *
     * @param int $offset
     *   The zero-based offset after which the new element should be inserted.
     * @param mixed $element
     *   The new element to insert.
     * @return CSequentialArray
     *   The current instance.
     * @throws \OutOfRangeException
     *   If the offset is out of range.
     */
    public function InsertAfter(int $offset, mixed $element): CSequentialArray
    {
        if ($offset < 0) {
            throw new \OutOfRangeException('Offset cannot be negative.');
        }
        if ($offset >= \count($this->value)) {
            throw new \OutOfRangeException('Offset exceeds array size.');
        }
        \array_splice($this->value, $offset + 1, 0, [$element]);
        return $this;
    }
}
