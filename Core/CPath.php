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

/**
 * CPath is a class for manipulating file system paths.
 */
class CPath extends CString
{
    #region public -------------------------------------------------------------

    /**
     * Joins multiple path segments into a single path.
     *
     * @param string|\Stringable ...$segments
     *   A list of path segments to join.
     * @return static
     *   A new instance representing the joined path.
     *
     * @see Extend
     */
    public static function Join(string|\Stringable ...$segments): static
    {
        $joined = new static();
        $filtered = [];
        $slashes = self::getSlashes();
        foreach ($segments as $index => $segment) {
            if (!$segment instanceof static) {
                $segment = new static($segment);
            }
            if (($index === 0 && !$segment->IsEmpty()) ||
                !$segment->Trim($slashes)->IsEmpty()
            ) {
                $filtered[] = $segment;
            }
        }
        $lastIndex = \count($filtered) - 1;
        foreach ($filtered as $index => $segment) {
            if ($index > 0) {
                $segment = $segment->TrimLeadingSlashes();
            }
            if ($index < $lastIndex) {
                $segment = $segment->EnsureTrailingSlash();
            }
            $joined->AppendInPlace($segment);
        }
        return $joined;
    }

    /**
     * Appends one or more segments to the current path.
     *
     * @param string|\Stringable ...$segments
     *   One or more path segments to append.
     * @return static
     *   A new instance with the segments appended.
     *
     * @see Join
     */
    public function Extend(string|\Stringable ...$segments): static
    {
        return static::Join($this, ...$segments);
    }

    /**
     * Ensures the path starts with a slash.
     *
     * If the path does not already start with a slash, one is inserted at the
     * beginning.
     *
     * @return self
     *   The current instance if the instance already starts with a slash.
     * @return static
     *   If a slash is prepended, a new instance that starts with a slash.
     */
    public function EnsureLeadingSlash(): static
    {
        if (self::isSlash($this->First())) {
            return $this;
        }
        return $this->Prepend(\DIRECTORY_SEPARATOR);
    }

    /**
     * Ensures the path ends with a slash.
     *
     * If the path does not already end with a slash, one is appended at the
     * end.
     *
     * @return self
     *   The current instance if the instance already ends with a slash.
     * @return static
     *   If a slash is appended, a new instance that ends with a slash.
     */
    public function EnsureTrailingSlash(): static
    {
        if (self::isSlash($this->Last())) {
            return $this;
        }
        return $this->Append(\DIRECTORY_SEPARATOR);
    }

    /**
     * Removes slashes from the start of the path.
     *
     * The slashes include both forward slashes and backslashes depending on
     * the operating system.
     *
     * @return self
     *   The current instance if the instance has no leading slashes.
     * @return static
     *   If leading slashes are removed, a new instance without slashes
     *   at the start.
     */
    public function TrimLeadingSlashes(): static
    {
        if (!self::isSlash($this->First())) {
            return $this;
        }
        return $this->TrimLeft(self::getSlashes());
    }

    /**
     * Removes slashes from the end of the path.
     *
     * The slashes include both forward slashes and backslashes depending on
     * the operating system.
     *
     * @return self
     *   The current instance if the instance has no trailing slashes.
     * @return static
     *   If trailing slashes are removed, a new instance without slashes
     *   at the end.
     */
    public function TrimTrailingSlashes(): static
    {
        if (!self::isSlash($this->Last())) {
            return $this;
        }
        return $this->TrimRight(self::getSlashes());
    }

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
        return \DIRECTORY_SEPARATOR === '/' ? '/' : '/\\';
    }

    /**
     * Determines whether a given character is a valid slash for the current
     * operating system.
     *
     * This function checks if the given character matches a forward slash on
     * Linux, or either a forward slash or backslash on Windows.
     *
     * @param string $char
     *   The character to check.
     * @return bool
     *   Returns `true` if the character is a valid slash for the current
     *   operating system; otherwise, `false`.
     */
    private static function isSlash(string $char): bool
    {
        return $char === '/' || $char === \DIRECTORY_SEPARATOR;
    }

    #endregion private
}
