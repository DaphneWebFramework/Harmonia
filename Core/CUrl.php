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

/**
 * CUrl is a class for manipulating URLs.
 */
class CUrl implements \Stringable
{
    /**
     * The URL value stored in the instance.
     *
     * @var string
     */
    private string $value;

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
        if ($value instanceof self) {
            $value = $value->value;
        } elseif ($value instanceof \Stringable) {
            $value = (string)$value;
        }
        $this->value = \trim($value);
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
        $segments = array_values(array_filter($segments, function(string $segment): bool {
            return '' !== \trim($segment, '/');
        }));
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
            $url->value .= $segment->value;
        }
        return $url;
    }

    /**
     * Ensures the URL starts with a leading slash.
     *
     * If the URL does not already start with a slash, one is inserted at the
     * beginning.
     *
     * @return CUrl
     *   The current instance.
     */
    public function EnsureLeadingSlash(): self
    {
        if ($this->value === '' || $this->value[0] !== '/') {
            $this->value = '/' . $this->value;
        }
        return $this;
    }

    /**
     * Ensures the URL ends with a trailing slash.
     *
     * If the URL does not already end with a slash, one is appended at the
     * end.
     *
     * @return CUrl
     *   The current instance.
     */
    public function EnsureTrailingSlash(): self
    {
        if ($this->value === '' || $this->value[-1] !== '/') {
            $this->value .= '/';
        }
        return $this;
    }

    /**
     * Removes all leading slashes.
     *
     * @return CUrl
     *   The current instance.
     */
    public function TrimLeadingSlashes(): self
    {
        $this->value = \ltrim($this->value, '/');
        return $this;
    }

    /**
     * Removes all trailing slashes.
     *
     * @return CUrl
     *   The current instance.
     */
    public function TrimTrailingSlashes(): self
    {
        $this->value = \rtrim($this->value, '/');
        return $this;
    }

    #region Interface: Stringable

    /**
     * Returns the string representation for use in string contexts.
     *
     * @return string
     *   The URL value stored in the instance.
     *
     * @override
     */
    public function __toString(): string
    {
        return $this->value;
    }

    #endregion Interface: Stringable

    #endregion public
}
