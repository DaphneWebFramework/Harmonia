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
    protected array $value = [];

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
    public function Has(string|int $key): bool
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
    public function Get(string|int $key, mixed $defaultValue = null): mixed
    {
        if (!$this->Has($key)) {
            return $defaultValue;
        }
        return $this->value[$key];
    }

    /**
     * Removes an element by its key.
     *
     * @param string|int $key
     *   The key of the element to remove.
     * @return CArray
     *   The current instance.
     */
    public function Delete(string|int $key): CArray
    {
        unset($this->value[$key]);
        return $this;
    }
}
