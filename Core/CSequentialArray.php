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
     * Inserts a new element before an existing element at a specified index.
     *
     * @param int $index
     *   The zero-based index before which the new element should be inserted.
     * @param mixed $element
     *   The new element to insert.
     * @return CSequentialArray
     *   The current instance.
     * @throws \OutOfRangeException
     *   If the index is out of range.
     */
    public function InsertBefore(int $index, mixed $element): CSequentialArray
    {
        if ($index < 0) {
            throw new \OutOfRangeException('Index cannot be negative.');
        }
        if ($index > \count($this->value)) {
            throw new \OutOfRangeException('Index exceeds array size.');
        }
        \array_splice($this->value, $index, 0, [$element]);
        return $this;
    }

    /**
     * Inserts a new element after an existing element at a specified index.
     *
     * @param int $index
     *   The zero-based index after which the new element should be inserted.
     * @param mixed $element
     *   The new element to insert.
     * @return CSequentialArray
     *   The current instance.
     * @throws \OutOfRangeException
     *   If the index is out of range.
     */
    public function InsertAfter(int $index, mixed $element): CSequentialArray
    {
        if ($index < 0) {
            throw new \OutOfRangeException('Index cannot be negative.');
        }
        if ($index >= \count($this->value)) {
            throw new \OutOfRangeException('Index exceeds array size.');
        }
        \array_splice($this->value, $index + 1, 0, [$element]);
        return $this;
    }

    /**
     * Removes an element at the specified index.
     *
     * The `$index` parameter accepts both strings and integers to comply with
     * the `CArray::Delete` signature. However, if a string is provided, an
     * exception is thrown because `CSequentialArray` only supports integer
     * indexing.
     *
     * @param string|int $index
     *   The zero-based index of the element to remove. If a string is given,
     *   an exception is thrown.
     * @return CSequentialArray
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the index is a string.
     * @throws \OutOfRangeException
     *   If the index is out of range.
     */
    public function Delete(string|int $index): CSequentialArray
    {
        if (\is_string($index)) {
            throw new \InvalidArgumentException('Index cannot be a string.');
        }
        if ($index < 0) {
            throw new \OutOfRangeException('Index cannot be negative.');
        }
        if ($index >= \count($this->value)) {
            throw new \OutOfRangeException('Index exceeds array size.');
        }
        \array_splice($this->value, $index, 1);
        return $this;
    }
}
