<?php declare(strict_types=1);
/**
 * CArray.php
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
 * CArray is a wrapper for PHP's native `array` type, offering additional
 * methods for array manipulation and consistency in array operations.
 */
class CArray
{
    /**
     * The array value stored in the instance.
     *
     * @var array
     */
    private array $value = [];

    /**
     * Constructs a new instance of CArray.
     *
     * @param array|CArray $value (Optional)
     *   The array value to store. If omitted, defaults to an empty array. If a
     *   `CArray` instance is provided, the array value is copied from the
     *   original instance.
     */
    public function __construct(array|CArray $value = [])
    {
        if ($value instanceof self) {
            $this->value = $value->value;
        } else {
            $this->value = $value;
        }
    }

    /**
     * Checks if the specified key exists in the array.
     *
     * @param string|int $key
     *   The key to check for existence within the array.
     * @return bool
     *   Returns `true` if the key exists in the array, `false` otherwise.
     */
    public function ContainsKey(string|int $key): bool
    {
        return \array_key_exists($key, $this->value);
    }

    /**
     * Returns the value at the specified key, or a default value if the key
     * does not exist in the array.
     *
     * @param string|int $key
     *   The key to look up in the array.
     * @param mixed $defaultValue (Optional)
     *   The value to return if the key does not exist. Defaults to `null`.
     * @return mixed
     *   The value at the specified key if it exists, or the default value if
     *   the key is not found.
     */
    public function ValueOrDefault(string|int $key, mixed $defaultValue = null): mixed
    {
        if (!$this->ContainsKey($key)) {
            return $defaultValue;
        }
        return $this->value[$key];
    }

    /**
     * Adds an element to the end of the array.
     *
     * @param mixed $element
     *   The element to add to the array.
     * @return CArray
     *   The current instance.
     */
    public function PushBack(mixed $element): CArray
    {
        $this->value[] = $element;
        return $this;
    }

    /**
     * Adds an element to the beginning of the array.
     *
     * @param mixed $element
     *   The element to add to the array.
     * @return CArray
     *   The current instance.
     */
    public function PushFront(mixed $element): CArray
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
     * > This method is intended for use with sequentially indexed arrays.
     * After insertion, the array is reindexed to maintain sequential order.
     * If applied to associative arrays, existing string keys are preserved, and
     * the new element is added with the next available zero-based integer key.
     *
     * @param int $offset
     *   The zero-based offset before which the new element should be inserted.
     * @param mixed $element
     *   The new element to insert.
     * @return CArray
     *   The current instance.
     * @throws \OutOfRangeException
     *   If the offset is out of range.
     *
     * @see InsertAfter
     */
    public function InsertBefore(int $offset, mixed $element): CArray
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
     * > This method is intended for use with sequentially indexed arrays.
     * After insertion, the array is reindexed to maintain sequential order.
     * If applied to associative arrays, existing string keys are preserved, and
     * the new element is added with the next available zero-based integer key.
     *
     * @param int $offset
     *   The zero-based offset after which the new element should be inserted.
     * @param mixed $element
     *   The new element to insert.
     * @return CArray
     *   The current instance.
     * @throws \OutOfRangeException
     *   If the offset is out of range.
     *
     * @see InsertBefore
     */
    public function InsertAfter(int $offset, mixed $element): CArray
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
