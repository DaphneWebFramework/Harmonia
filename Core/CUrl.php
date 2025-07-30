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
class CUrl extends CString
{
    #region public -------------------------------------------------------------

    /**
     * Joins multiple URL segments into a single URL.
     *
     * @param string|\Stringable ...$segments
     *   A list of URL segments to join.
     * @return CUrl
     *   A new `CUrl` instance representing the joined URL.
     */
    public static function Join(string|\Stringable ...$segments): CUrl
    {
        $filtered = [];
        foreach ($segments as $segment) {
            if (!$segment instanceof CUrl) {
                $segment = new CUrl($segment);
            }
            if (!$segment->Trim('/')->IsEmpty()) {
                $filtered[] = $segment;
            }
        }
        $joined = new CUrl();
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
     * Ensures the URL starts with a slash.
     *
     * If the URL does not already start with a slash, one is inserted at the
     * beginning.
     *
     * @return static
     *   A new instance that starts with a slash.
     */
    public function EnsureLeadingSlash(): static
    {
        if ($this->First() === '/') {
            return clone $this;
        }
        return $this->Prepend('/');
    }

    /**
     * Ensures the URL ends with a slash.
     *
     * If the URL does not already end with a slash, one is appended at the
     * end.
     *
     * @return static
     *   A new instance that ends with a slash.
     */
    public function EnsureTrailingSlash(): static
    {
        if ($this->Last() === '/') {
            return clone $this;
        }
        return $this->Append('/');
    }

    /**
     * Removes slashes from the start of the URL.
     *
     * @return static
     *   A new instance without slashes at the start.
     */
    public function TrimLeadingSlashes(): static
    {
        return $this->TrimLeft('/');
    }

    /**
     * Removes slashes from the end of the URL.
     *
     * @return static
     *   A new instance without slashes at the end.
     */
    public function TrimTrailingSlashes(): static
    {
        return $this->TrimRight('/');
    }

    #endregion public
}
