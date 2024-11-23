<?php declare(strict_types=1);
/**
 * CUrl.php
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
 * CUrl is a class for manipulating URLs.
 */
class CUrl extends CString
{
    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * Leading and trailing whitespace are trimmed when storing the specified
     * URL value.
     *
     * @param string|\Stringable $value (Optional)
     *   The URL value to store. If omitted, defaults to an empty string. If
     *   given a `CUrl` instance, its value is copied. For a `Stringable`
     *   instance, its string representation is used, and for a native string,
     *   the value is used directly.
     */
    public function __construct(string|\Stringable $value = '')
    {
        parent::__construct($value);
        $this->TrimInPlace();
    }

    /**
     * Joins multiple URL segments into a single URL.
     *
     * @param string ...$segments
     *   A list of URL segments to join.
     * @return CUrl
     *   A new `CUrl` instance representing the joined URL.
     */
    public static function Join(string ...$segments): CUrl
    {
        $segments = array_values(array_filter($segments,
            function(string $segment): bool {
                $segment = new CString($segment);
                return !$segment->TrimInPlace('/')->IsEmpty();
            }
        ));
        $url = new CUrl();
        $lastIndex = count($segments) - 1;
        foreach ($segments as $index => $segment) {
            $segment = new CUrl($segment);
            if ($index > 0) {
                $segment->TrimLeadingSlashes();
            }
            if ($index < $lastIndex) {
                $segment->EnsureTrailingSlash();
            }
            $url->AppendInPlace($segment);
        }
        return $url;
    }

    /**
     * Ensures the URL starts with a leading slash.
     *
     * If the URL does not already start with a slash, one is inserted at the
     * beginning.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function EnsureLeadingSlash(): self
    {
        if ($this->First() !== '/') {
            $this->PrependInPlace('/');
        }
        return $this;
    }

    /**
     * Ensures the URL ends with a trailing slash.
     *
     * If the URL does not already end with a slash, one is appended at the
     * end.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function EnsureTrailingSlash(): self
    {
        if ($this->Last() !== '/') {
            $this->AppendInPlace('/');
        }
        return $this;
    }

    /**
     * Removes all leading slashes.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function TrimLeadingSlashes(): self
    {
        $this->TrimLeftInPlace('/');
        return $this;
    }

    /**
     * Removes all trailing slashes.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function TrimTrailingSlashes(): self
    {
        $this->TrimRightInPlace('/');
        return $this;
    }

    #endregion public
}
