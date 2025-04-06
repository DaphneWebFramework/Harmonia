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
     * Constructs a new instance.
     *
     * Leading and trailing whitespace are trimmed when storing the specified
     * path value.
     *
     * @param string|\Stringable $value
     *   (Optional) The path value to store. If omitted, defaults to an empty
     *   string. If given a `CPath` instance, its value is copied. For a
     *   `Stringable` instance, its string representation is used, and for a
     *   native string, the value is used directly.
     */
    public function __construct(string|\Stringable $value = '')
    {
        parent::__construct($value);
        if (!$this->IsEmpty()) {
            $this->TrimInPlace();
        }
    }

    /**
     * Joins multiple path segments into a single path.
     *
     * @param string|\Stringable ...$segments
     *   A list of path segments to join.
     * @return CPath
     *   A new `CPath` instance representing the joined path.
     */
    public static function Join(string|\Stringable ...$segments): CPath
    {
        $segments = \array_values(\array_filter($segments,
            function(string|\Stringable $segment): bool {
                $segment = new CString($segment);
                return !$segment->TrimInPlace(self::getSlashes())->IsEmpty();
            }
        ));
        $path = new CPath();
        $lastIndex = \count($segments) - 1;
        foreach ($segments as $index => $segment) {
            $segment = new CPath($segment);
            if ($index > 0) {
                $segment->TrimLeadingSlashes();
            }
            if ($index < $lastIndex) {
                $segment->EnsureTrailingSlash();
            }
            $path->AppendInPlace($segment);
        }
        return $path;
    }

    /**
     * Ensures the path starts with a leading slash.
     *
     * If the path does not already start with a slash, one is inserted at the
     * beginning.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function EnsureLeadingSlash(): self
    {
        if (!self::isSlash($this->First())) {
            $this->PrependInPlace(DIRECTORY_SEPARATOR);
        }
        return $this;
    }

    /**
     * Ensures the path ends with a trailing slash.
     *
     * If the path does not already end with a slash, one is appended at the
     * end.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function EnsureTrailingSlash(): self
    {
        if (!self::isSlash($this->Last())) {
            $this->AppendInPlace(DIRECTORY_SEPARATOR);
        }
        return $this;
    }

    /**
     * Removes all leading slashes.
     *
     * Leading slashes include both forward slashes and backslashes depending on
     * the operating system.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function TrimLeadingSlashes(): self
    {
        $this->TrimLeftInPlace(self::getSlashes());
        return $this;
    }

    /**
     * Removes all trailing slashes.
     *
     * Trailing slashes include both forward slashes and backslashes depending
     * on the operating system.
     *
     * This method directly modifies the current instance.
     *
     * @return self
     *   The current instance.
     */
    public function TrimTrailingSlashes(): self
    {
        $this->TrimRightInPlace(self::getSlashes());
        return $this;
    }

    /**
     * Determines whether the path points to an existing file.
     *
     * @return bool
     *   Returns `true` if the path points to a file; otherwise, `false`.
     */
    public function IsFile(): bool
    {
        return \is_file($this->value);
    }

    /**
     * Determines whether the path points to an existing directory.
     *
     * @return bool
     *   Returns `true` if the path points to a directory; otherwise, `false`.
     */
    public function IsDirectory(): bool
    {
        return \is_dir($this->value);
    }

    /**
     * Determines whether the path points to an existing symbolic link.
     *
     * @return bool
     *   Returns `true` if the path points to a symbolic link; otherwise, `false`.
     *
     * @see ReadLink
     */
    public function IsLink(): bool
    {
        return \is_link($this->value);
    }

    /**
     * Reads the target path of a symbolic link.
     *
     * @return ?CPath
     *   A new `CPath` instance containing the target of the symbolic link if
     *   successful, or `null` if the method fails.
     *
     * @see IsLink
     */
    public function ReadLink(): ?CPath
    {
        $targetPath = @\readlink($this->value);
        if ($targetPath === false) {
            return null;
        }
        return new CPath($targetPath);
    }

    /**
     * Returns the trailing name component of the path.
     *
     * The base name is the last component of a path, which is typically the
     * file or directory name.
     *
     * This method operates on the input string, and is not aware of the actual
     * filesystem.
     *
     * @return string
     *   The base name of the path.
     */
    public function BaseName(): string
    {
        return \basename($this->value);
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
