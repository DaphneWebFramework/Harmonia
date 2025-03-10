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
class CArray implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * The array value stored in the instance.
     *
     * @var array
     */
    protected array $value;

    /**
     * Constructs a new instance.
     *
     * @param array|CArray $value
     *   (Optional) The array value to store. If omitted, defaults to an empty
     *   array. If a `CArray` instance is provided, the array value is copied
     *   from the original instance.
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
     * Retrieves a copy of the array stored in the instance.
     *
     * @return array
     *   A copy of the array stored in the instance.
     */
    public function ToArray(): array
    {
        return $this->value;
    }

    /**
     * Checks if the array is empty.
     *
     * @return bool
     *   Returns `true` if the array is empty, `false` otherwise.
     */
    public function IsEmpty(): bool
    {
        return empty($this->value);
    }

    /**
     * Checks if the specified key exists.
     *
     * @param string|int $key
     *   The key to check for existence.
     * @return bool
     *   Returns `true` if the key exists, `false` otherwise.
     */
    public function Has(string|int $key): bool
    {
        return \array_key_exists($key, $this->value);
    }

    /**
     * Returns the value at the specified key.
     *
     * @param string|int $key
     *   The key to look up.
     * @return mixed
     *   The value at the specified key if it exists, or `null` if the key is
     *   not found.
     */
    public function Get(string|int $key): mixed
    {
        return $this->value[$key] ?? null;
    }

    /**
     * Returns the value at the specified key, or a default value if the key
     * does not exist.
     *
     * @param string|int $key
     *   The key to look up.
     * @param mixed $defaultValue
     *   The value to return if the key does not exist.
     * @return mixed
     *   The value at the specified key if it exists, or the default value if
     *   the key is not found.
     */
    public function GetOrDefault(string|int $key, mixed $defaultValue): mixed
    {
        if (!$this->Has($key)) {
            return $defaultValue;
        }
        return $this->value[$key];
    }

    /**
     * Adds or updates the value at the specified key.
     *
     * @param string|int $key
     *   The key at which to set the value.
     * @param mixed $value
     *   The value to set at the specified key.
     * @return self
     *   The current instance.
     */
    public function Set(string|int $key, mixed $value): self
    {
        $this->value[$key] = $value;
        return $this;
    }

    /**
     * Removes an element by its key.
     *
     * @param string|int $key
     *   The key of the element to remove.
     * @return self
     *   The current instance.
     */
    public function Remove(string|int $key): self
    {
        unset($this->value[$key]);
        return $this;
    }

    /**
     * Clears all elements from the array, making it empty.
     *
     * @return self
     *   The current instance.
     */
    public function Clear(): self
    {
        $this->value = [];
        return $this;
    }

    /**
     * Applies a function to the current value.
     *
     * This version of the method directly modifies the current instance.
     *
     * @param callable $function
     *   The function to apply to the current value. It must accept an array
     *   as its first parameter. Any additional arguments passed to this method
     *   will be forwarded to the applied function.
     * @param mixed ...$args
     *   Additional arguments to pass to the applied function.
     * @return self
     *   The current instance.
     * @throws \UnexpectedValueException
     *   If the applied function returns a value that is not an array.
     *
     * @see Apply
     */
    public function ApplyInPlace(callable $function, mixed ...$args): self
    {
        $value = $function($this->value, ...$args);
        if (!\is_array($value)) {
            throw new \UnexpectedValueException(
                'Applied function must return an array.');
        }
        $this->value = $value;
        return $this;
    }

    /**
     * Applies a function to the current value.
     *
     * @param callable $function
     *   The function to apply to the current value. It must accept an array
     *   as its first parameter. Any additional arguments passed to this method
     *   will be forwarded to the applied function.
     * @param mixed ...$args
     *   Additional arguments to pass to the applied function.
     * @return CArray
     *   A new `CArray` instance containing the result of the applied function.
     * @throws \UnexpectedValueException
     *   If the applied function returns a value that is not an array.
     *
     * @see ApplyInPlace
     */
    public function Apply(callable $function, mixed ...$args): CArray
    {
        $clone = clone $this;
        return $clone->ApplyInPlace($function, ...$args);
    }

    #region Interface: ArrayAccess

    /**
     * Provides array-like access to check if a value exists at a given key.
     *
     * @param mixed $offset
     *   The key to check for existence.
     * @return bool
     *   Returns `true` if the key exists, `false` otherwise.
     * @throws \TypeError
     *   If the key is not a string or integer.
     *
     * @override
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->Has($offset);
    }

    /**
     * Provides array-like access to retrieve the value at a given key.
     *
     * @param mixed $offset
     *   The key to look up.
     * @return mixed
     *   The value at the specified key, or `null` if the key is not found.
     * @throws \TypeError
     *   If the key is not a string or integer.
     *
     * @override
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->Get($offset);
    }

    /**
     * Provides array-like access to set a value at a specified key.
     *
     * @param mixed $offset
     *   The key at which to set the value.
     * @param mixed $value
     *   The value to set at the specified key.
     * @throws \TypeError
     *   If the key is not a string or integer.
     *
     * @override
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->Set($offset, $value);
    }

    /**
     * Provides array-like access to unset a value at a specified key.
     *
     * @param mixed $offset
     *   The key of the element to unset.
     * @throws \TypeError
     *   If the key is not a string or integer.
     *
     * @override
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->Remove($offset);
    }

    #endregion Interface: ArrayAccess

    #region Interface: Countable

    /**
     * Returns the number of elements.
     *
     * Can be accessed interchangeably as `count($instance)`, `$instance->count()`,
     * or `$instance->Count()` due to PHP's case insensitivity and the `\Countable`
     * interface.
     *
     * @return int
     *   The number of elements.
     *
     * @override
     */
    public function count(): int
    {
        return \count($this->value);
    }

    #endregion Interface: Countable

    #region Interface: IteratorAggregate

    /**
     * Provides array-like traversal over each element.
     *
     * @return \Traversable
     *   An iterator yielding each element.
     *
     * @override
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->value);
    }

    #endregion Interface: IteratorAggregate
}
