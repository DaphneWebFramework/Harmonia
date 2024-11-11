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
    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * @param array|CArray $value (Optional)
     *   The array value to store. If omitted, defaults to an empty array. If a
     *   `CArray` or `CSequentialArray` instance is provided, its array value
     *   is copied. Only arrays with sequential, zero-based integer indexes
     *   are accepted.
     * @throws \InvalidArgumentException
     *   If the provided array is non-sequential, not zero-based, or contains
     *   non-integer indexes.
     */
    public function __construct(array|CArray $value = [])
    {
        if (!self::isSequentialArray($value)) {
            throw new \InvalidArgumentException(
                'Array must have sequential, zero-based integer indexes.');
        }
        parent::__construct($value);
    }

    /**
     * Checks if the specified index exists.
     *
     * The `$index` parameter accepts both strings and integers to comply with
     * the `CArray::Has` signature. However, if a string is provided, an
     * exception is thrown because `CSequentialArray` only supports integer
     * indexing.
     *
     * @param string|int $index
     *   The index to check for existence. If a string is given, an exception is
     *   thrown.
     * @return bool
     *   Returns `true` if the index is within range, `false` otherwise.
     * @throws \InvalidArgumentException
     *   If the index is a string.
     *
     * @override
     */
    public function Has(string|int $index): bool
    {
        if (\is_string($index)) {
            throw new \InvalidArgumentException('Index cannot be a string.');
        }
        return 0 <= $index && $index < count($this->value);
    }

    /**
     * Returns the value at the specified index, or a default value if the
     * index does not exist.
     *
     * The `$index` parameter accepts both strings and integers to comply with
     * the `CArray::Get` signature. However, if a string is provided, an
     * exception is thrown because `CSequentialArray` only supports integer
     * indexing.
     *
     * @param string|int $index
     *   The zero-based index to look up. If a string is given, an exception
     *   is thrown.
     * @param mixed $defaultValue (Optional)
     *   The value to return if the index does not exist. Defaults to `null`.
     * @return mixed
     *   The value at the specified index if it exists, or the default value if
     *   the index is out of range.
     * @throws \InvalidArgumentException
     *   If the index is a string.
     *
     * @override
     */
    public function Get(string|int $index, mixed $defaultValue = null): mixed
    {
        if (!$this->Has($index)) {
            return $defaultValue;
        }
        return $this->value[$index];
    }

    /**
     * Updates the value at the specified index.
     *
     * The `$index` parameter accepts both strings and integers to comply with
     * the `CArray::Set` signature. However, if a string is provided, an
     * exception is thrown because `CSequentialArray` only supports integer
     * indexing.
     *
     * @param string|int $index
     *   The zero-based index at which to set the value. If a string is given,
     *   an exception is thrown. If the index is out of range, no changes are
     *   made.
     * @param mixed $value
     *   The value to set at the specified index.
     * @return CSequentialArray
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the index is a string.
     *
     * @override
     */
    public function Set(string|int $index, mixed $value): CSequentialArray
    {
        if (!$this->Has($index)) {
            return $this;
        }
        $this->value[$index] = $value;
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
     *   an exception is thrown. If the index is out of range, no changes are
     *   made.
     * @return CSequentialArray
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the index is a string.
     *
     * @override
     */
    public function Delete(string|int $index): CSequentialArray
    {
        if (!$this->Has($index)) {
            return $this;
        }
        \array_splice($this->value, $index, 1);
        return $this;
    }

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
     *   If the index is out of range, no changes are made.
     * @param mixed $element
     *   The new element to insert.
     * @return CSequentialArray
     *   The current instance.
     */
    public function InsertBefore(int $index, mixed $element): CSequentialArray
    {
        if ($index < 0 || $index >= $this->Count()) {
            return $this;
        }
        \array_splice($this->value, $index, 0, [$element]);
        return $this;
    }

    /**
     * Inserts a new element after an existing element at a specified index.
     *
     * @param int $index
     *   The zero-based index after which the new element should be inserted.
     *   If the index is out of range, no changes are made.
     * @param mixed $element
     *   The new element to insert.
     * @return CSequentialArray
     *   The current instance.
     */
    public function InsertAfter(int $index, mixed $element): CSequentialArray
    {
        if ($index < 0 || $index >= $this->Count()) {
            return $this;
        }
        \array_splice($this->value, $index + 1, 0, [$element]);
        return $this;
    }

    #endregion public

    #region private ------------------------------------------------------------

    /**
     * Determines if the array or `CArray` instance has sequential, zero-based
     * integer indexes.
     *
     * If a `CSequentialArray` instance is provided, it is assumed to be
     * sequential and `true` is returned immediately. If a `CArray` instance is
     * provided, the underlying array is checked. An empty array is considered
     * sequential by default.
     *
     * @param array|CArray $array
     *   The array or `CArray` instance to check for sequential integer indexing.
     * @return bool
     *   Returns `true` if the array has sequential, zero-based integer indexes,
     *   or if it is empty; `false` otherwise.
     */
    private static function isSequentialArray(array|CArray $array): bool
    {
        if ($array instanceof CSequentialArray) {
            return true;
        }
        if ($array instanceof CArray) {
            $array = $array->value;
        }
        if (empty($array)) {
            return true;
        }
        return \array_keys($array) === \range(0, \count($array) - 1);
    }

    #endregion private
}
