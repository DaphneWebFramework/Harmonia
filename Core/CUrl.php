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
use \Harmonia\Core\CArray;

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
     * @param string|\Stringable $value
     *   (Optional) The URL value to store. If omitted, defaults to an empty
     *   string. If given a `CUrl` instance, its value is copied. For a
     *   `Stringable` instance, its string representation is used, and for a
     *   native string, the value is used directly.
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
        $segments = \array_values(\array_filter($segments,
            function(string $segment): bool {
                $segment = new CString($segment);
                return !$segment->TrimInPlace('/')->IsEmpty();
            }
        ));
        $url = new CUrl();
        $lastIndex = \count($segments) - 1;
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

    /**
     * Parses the URL into its components.
     *
     * @return ?CArray
     *   A `CArray` instance containing the URL components if parsing is
     *   successful, or `null` if parsing fails.
     */
    public function Components(): ?CArray
    {
        $components = \parse_url((string)$this);
        if ($components === false) {
            return null;
        }
        return new CArray($components);
    }

    /**
     * Returns the canonical absolute form of the URL.
     *
     * If the instance represents a relative URL, it will be converted into an
     * absolute URL using the provided base URL. The path resolution is performed
     * relative to the current working directory of the script (`getcwd()`).
     *
     * @param string|\Stringable $baseUrl
     *   The base URL which serves as the prefix of the resulting absolute URL.
     * @param string|\Stringable $basePath
     *   The base directory path used both to validate and compute the resolved
     *   absolute URL.
     * @return CUrl
     *   A new `CUrl` instance representing the absolute URL. If the URL is
     *   already in absolute form, if parsing fails, if the resolved path is
     *   invalid or outside the base path, or if the base path cannot be resolved,
     *   the original URL instance is returned unchanged.
     */
    public function ToAbsolute(string|\Stringable $baseUrl,
                               string|\Stringable $basePath): CUrl
    {
        // 1
        $components = $this->Components();
        if ($components === null
         || $components->Has('scheme') || $components->Has('host')
         || !$components->Has('path'))
        {
            return clone $this;
        }
        // 2
        $pathComponent = new CPath($components->Get('path'));
        $absolutePath = $pathComponent->TrimLeadingSlashes()
                                      ->ApplyInPlace('rawurldecode')
                                      ->ToAbsolute();
        if ($absolutePath === null) {
            return clone $this;
        }
        // 3
        $isWindowsOS = \PHP_OS_FAMILY === 'Windows';
        // 4
        if ($absolutePath->IsDirectory()) {
            $absolutePath->EnsureTrailingSlash(); // avoid 301 redirects
        }
        if ($isWindowsOS) {
            $absolutePath->ReplaceInPlace('\\', '/');
        }
        // 5
        $basePath = new CPath($basePath);
        $basePath = $basePath->ToAbsolute();
        if ($basePath === null) {
            return clone $this;
        }
        if ($isWindowsOS) {
            $basePath->ReplaceInPlace('\\', '/');
        }
        if (!$absolutePath->StartsWith($basePath, $isWindowsOS ? false : true)) {
            return clone $this;
        }
        // 6
        $relativeUrl = $absolutePath->Middle($basePath->Length())
                                    ->ApplyInPlace('rawurlencode')
                                    ->ReplaceInPlace('%2F', '/'); // retain slashes
        $absoluteUrl = CUrl::Join($baseUrl, (string)$relativeUrl);
        // 7
        if ($components->Has('query')) {
            $absoluteUrl->AppendInPlace('?' . $components->Get('query'));
        }
        if ($components->Has('fragment')) {
            $absoluteUrl->AppendInPlace('#' . $components->Get('fragment'));
        }
        // 8
        return $absoluteUrl;
    }

    #endregion public
}
