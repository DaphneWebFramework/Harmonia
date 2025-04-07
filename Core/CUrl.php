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
                $segment->TrimLeadingSlashes();
            }
            if ($index < $lastIndex) {
                $segment->EnsureTrailingSlash();
            }
            $joined->AppendInPlace($segment);
        }
        return $joined;
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
