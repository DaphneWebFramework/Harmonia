<?php declare(strict_types=1);
/**
 * CPath.php
 *
 * (C) 2024 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Core;

use \Harmonia\Core\CString;

/**
 * CPath is a class for manipulating file system paths.
 */
class CPath implements \Stringable
{
    /**
     * The path value stored in the instance.
     *
     * @var CString
     */
    private CString $value;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * @param string|\Stringable $value (Optional)
     *   The path value to store. If omitted, defaults to an empty string.
     *   If a `CPath` or `CString` instance is provided, its value is cloned.
     *   If a native string is provided, it is directly used. For other
     *   `Stringable` instances, their string representations are used.
     */
    public function __construct(string|\Stringable $value = '')
    {
        $this->value = match (true) {
            $value instanceof self        => clone $value->value,
            $value instanceof CString     => clone $value,
            $value instanceof \Stringable => new CString((string)$value),
            default                       => new CString($value)
        };
    }

    #region Interface: Stringable

    /**
     * Returns the string representation for use in string contexts.
     *
     * @return string
     *   The path value stored in the instance.
     *
     * @override
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }

    #endregion Interface: Stringable

    #endregion public
}
