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
     */
    private CString $value;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * @param string|\Stringable $value (Optional)
     *   The path value to store. If omitted, defaults to an empty string. If
     *   given a `CPath` instance, its value is cloned. If given a `CString`
     *   instance, it is cloned as-is. For a `Stringable` instance, its string
     *   representation is used, and for a native string, the value is used
     *   directly.
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

    /**
     * Joins multiple path segments into a single path.
     *
     * @param string ...$segments
     *   A list of path segments to join.
     * @return CPath
     *   A new `CPath` instance representing the joined path.
     */
    public static function Join(string ...$segments): CPath
    {
        $segments = array_values(array_filter($segments, function(string $segment): bool {
            $segment = new CString($segment);
            return !$segment->Trim(self::getSlashes())->IsEmpty();
        }));
        $joined = new CPath();
        $lastIndex = count($segments) - 1;
        foreach ($segments as $index => $segment) {
            $segment = new CPath($segment);
            if ($index > 0) {
                $segment->TrimLeadingSlashes();
            }
            if ($index < $lastIndex) {
                $segment->EnsureTrailingSlash();
            }
            $joined->value->Append($segment);
        }
        return $joined;
    }

    /**
     * Ensures the path starts with a leading slash.
     *
     * If the path does not already start with a slash, one is inserted at the
     * beginning.
     *
     * @return CPath
     *   The current instance.
     */
    public function EnsureLeadingSlash(): self
    {
        if (!self::isSlash($this->value->First())) {
            $this->value->InsertAt(0, DIRECTORY_SEPARATOR);
        }
        return $this;
    }

    /**
     * Ensures the path ends with a trailing slash.
     *
     * If the path does not already end with a slash, one is appended at the
     * end.
     *
     * @return CPath
     *   The current instance.
     */
    public function EnsureTrailingSlash(): self
    {
        if (!self::isSlash($this->value->Last())) {
            $this->value->Append(DIRECTORY_SEPARATOR);
        }
        return $this;
    }

    /**
     * Removes all leading slashes.
     *
     * Leading slashes include both forward slashes and backslashes depending on
     * the operating system.
     *
     * @return CPath
     *   The current instance.
     */
    public function TrimLeadingSlashes(): self
    {
        $this->value = $this->value->TrimLeft(self::getSlashes());
        return $this;
    }

    /**
     * Removes all trailing slashes.
     *
     * Trailing slashes include both forward slashes and backslashes depending
     * on the operating system.
     *
     * @return CPath
     *   The current instance.
     */
    public function TrimTrailingSlashes(): self
    {
        $this->value = $this->value->TrimRight(self::getSlashes());
        return $this;
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

    #region private ------------------------------------------------------------

    /**
     * Returns the valid slash character(s) for the current operating system.
     *
     * On Linux, it returns the forward slash. On Windows, it returns both the
     * forward slash and the backslash.
     *
     * @return string
     *   The valid slash character(s) for the current operating system.
     */
    private static function getSlashes(): string
    {
        return DIRECTORY_SEPARATOR === '/' ? '/' : '/\\';
    }

    /**
     * Determines whether a given character is a valid slash for the current
     * operating system.
     *
     * This function checks if the given character matches a forward slash on
     * Linux or either a forward slash or backslash on Windows.
     *
     * @param string $char
     *   The character to check.
     * @return bool
     *   Returns `true` if the character is a valid slash for the current
     *   operating system; otherwise, `false`.
     */
    private static function isSlash(string $char): bool
    {
        return $char === '/' || $char === DIRECTORY_SEPARATOR;
    }

    #endregion private
}
