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
}
