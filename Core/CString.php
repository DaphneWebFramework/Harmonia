<?php declare(strict_types=1);
/**
 * CString.php
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
 * CString is a wrapper for PHP's native `string` type, providing enhanced
 * methods for string manipulation, with support for both single-byte and
 * multibyte encodings.
 *
 * This class requires PHP's `mbstring` extension for multibyte encoding support.
 */
class CString implements \Stringable, \ArrayAccess, \IteratorAggregate
{
    /**
     * Bitwise flag for `Split` options, performing a straightforward split with
     * no additional processing.
     */
    public const SPLIT_OPTION_NONE = 0;

    /**
     * Bitwise flag for `Split` options, trimming whitespace from each resulting
     * substring.
     */
    public const SPLIT_OPTION_TRIM = 1 << 0;

    /**
     * Bitwise flag for `Split` options, excluding empty substrings from the
     * result.
     *
     * Note: `SPLIT_OPTION_EXCLUDE_EMPTY` interacts with `SPLIT_OPTION_TRIM`.
     * When both are present, a substring can become empty after trimming and
     * will then be excluded. Without `SPLIT_OPTION_TRIM`, `SPLIT_OPTION_EXCLUDE_EMPTY`
     * only applies to substrings that are already empty as they appear in the
     * original string.
     */
    public const SPLIT_OPTION_EXCLUDE_EMPTY = 1 << 1;

    /**
     * The string value stored in the instance.
     *
     * @var string
     */
    private string $value;

    /**
     * The string's encoding, which should be one of the mbstring module's
     * supported encodings.
     *
     * @var string
     */
    private readonly string $encoding;

    /**
     * A boolean indicating if the encoding is single-byte or multibyte.
     *
     * @var bool
     */
    private readonly bool $isSingleByte;

    #region public -------------------------------------------------------------

    /**
     * Constructs a new instance.
     *
     * @param string|\Stringable $value (Optional)
     *   The string value to store. If omitted, defaults to an empty string. If
     *   given a `CString` instance, its value, encoding, and single-byte/multibyte
     *   status are copied. If given a `Stringable` instance, its string
     *   representation is used, and for a native string, the value is used
     *   directly.
     * @param ?string $encoding (Optional)
     *   The encoding to use (e.g., 'UTF-8', 'ISO-8859-1'). If omitted or set to
     *   `null`, defaults to the return value of `mb_internal_encoding`. This
     *   parameter is ignored when `$value` is an instance of `CString`. Note
     *   that encoding names are case-insensitive.
     */
    public function __construct(string|\Stringable $value = '', ?string $encoding = null)
    {
        if ($value instanceof self) {
            $this->value = $value->value;
            $this->encoding = $value->encoding;
            $this->isSingleByte = $value->isSingleByte;
        } else {
            if ($value instanceof \Stringable) {
                $this->value = (string)$value;
            } else { // \is_string($value)
                $this->value = $value;
            }
            $this->encoding = $encoding ?: \mb_internal_encoding();
            $this->isSingleByte = self::isSingleByteEncoding($this->encoding);
        }
    }

    /**
     * Checks if the string is empty.
     *
     * @return bool
     *   Returns `true` if the string is empty, `false` otherwise.
     */
    public function IsEmpty(): bool
    {
        return $this->value === '';
    }

    /**
     * Returns the length.
     *
     * @return int
     *   The number of characters.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function Length(): int
    {
        if ($this->isSingleByte) {
            return \strlen($this->value);
        } else {
            return \mb_strlen($this->value, $this->encoding);
        }
    }

    /**
     * Returns the first character.
     *
     * @return string
     *   The first character, or an empty string if the string is empty.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see Last
     */
    public function First(): string
    {
        if ($this->IsEmpty()) {
            return '';
        }
        if ($this->isSingleByte) {
            return $this->value[0];
        } else {
            return \mb_substr($this->value, 0, 1, $this->encoding);
        }
    }

    /**
     * Returns the last character.
     *
     * @return string
     *   The last character, or an empty string if the string is empty.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see First
     */
    public function Last(): string
    {
        if ($this->IsEmpty()) {
            return '';
        }
        if ($this->isSingleByte) {
            return $this->value[-1];
        } else {
            return \mb_substr($this->value, -1, 1, $this->encoding);
        }
    }

    /**
     * Returns the character at a specified offset.
     *
     * @param int $offset
     *   The zero-based offset of the character to return.
     * @return string
     *   The character at the specified offset, or an empty string if the offset
     *   is out of range.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see SetAt
     */
    public function At(int $offset): string
    {
        if ($offset < 0) {
            return '';
        }
        if ($this->isSingleByte) {
            if ($offset >= $this->Length()) {
                return '';
            }
            return $this->value[$offset];
        } else {
            return \mb_substr($this->value, $offset, 1, $this->encoding);
        }
    }

    /**
     * Sets the character at the specified offset.
     *
     * @param int $offset
     *   The zero-based offset where the character will be set. If the offset is
     *   negative or greater than or equal to the length of the string, no
     *   changes will be made.
     * @param string $character
     *   The character to set at the specified offset. If more than one character
     *   is provided, no changes will be made.
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see At
     */
    public function SetAt(int $offset, string $character): self
    {
        if ($offset < 0) {
            return $this;
        }
        $length = $this->Length();
        if ($offset >= $length) {
            return $this;
        }
        $character = $this->wrap($character);
        if ($character->IsEmpty()) {
            return $this;
        }
        if ($character->Length() > 1) {
            return $this;
        }
        if ($this->isSingleByte) {
            $this->value[$offset] = (string)$character;
        } else {
            $this->value =
                \mb_substr($this->value, 0, $offset, $this->encoding)
              . (string)$character
              . \mb_substr($this->value, $offset + 1, $length - $offset - 1,
                    $this->encoding);
        }
        return $this;
    }

    /**
     * Inserts a substring at the specified offset.
     *
     * @param int $offset
     *   The zero-based offset where the insertion will start. If the offset is
     *   negative or greater than the length of the string, no changes will be
     *   made. If the offset equals the length, the substring will be appended.
     * @param string|\Stringable $substring
     *   The substring to insert. If an empty string is provided, no changes
     *   will be made.
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see Append
     * @see AppendInPlace
     */
    public function InsertAt(int $offset, string|\Stringable $substring): self
    {
        if ($offset < 0) {
            return $this;
        }
        $length = $this->Length();
        if ($offset > $length) {
            return $this;
        }
        if ($substring instanceof \Stringable) {
            $substring = (string)$substring;
        }
        if ($substring === '') {
            return $this;
        }
        if ($offset === $length) {
            $this->value .= $substring;
        } else {
            if ($this->isSingleByte) {
                $this->value =
                    \substr($this->value, 0, $offset)
                  . $substring
                  . \substr($this->value, $offset);
            } else {
                $this->value =
                    \mb_substr($this->value, 0, $offset, $this->encoding)
                  . $substring
                  . \mb_substr($this->value, $offset, null, $this->encoding);
            }
        }
        return $this;
    }

    /**
     * Deletes a range of characters starting from the specified offset.
     *
     * @param int $offset
     *   The zero-based offset where the deletion will start. If the offset is
     *   negative or greater than or equal to the length of the string, no
     *   changes will be made.
     * @param int $count (Optional)
     *   The number of characters to delete. If this value is less than 1, no
     *   changes will be made. If it exceeds the remaining characters, it will
     *   delete up to the end. Defaults to 1.
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function DeleteAt(int $offset, int $count = 1): self
    {
        if ($offset < 0) {
            return $this;
        }
        if ($count < 1) {
            return $this;
        }
        $length = $this->Length();
        if ($offset >= $length) {
            return $this;
        }
        $availableLength = min($count, $length - $offset);
        $endOffset = $offset + $availableLength;
        if ($this->isSingleByte) {
            $this->value =
                \substr($this->value, 0, $offset)
              . \substr($this->value, $endOffset);
        } else {
            $this->value =
                \mb_substr($this->value, 0, $offset, $this->encoding)
              . \mb_substr($this->value, $endOffset, null, $this->encoding);
        }
        return $this;
    }

    /**
     * Inserts the specified string at the beginning.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @param string|\Stringable $substring
     *   The string to prepend.
     * @return self
     *   The current instance.
     *
     * @see Prepend
     * @see InsertAt
     */
    public function PrependInPlace(string|\Stringable $substring): self
    {
        if ($substring instanceof \Stringable) {
            $substring = (string)$substring;
        }
        $this->value = $substring . $this->value;
        return $this;
    }

    /**
     * Inserts the specified string at the beginning.
     *
     * @param string|\Stringable $substring
     *   The string to prepend.
     * @return CString
     *   A new `CString` instance with the string prepended.
     *
     * @see PrependInPlace
     * @see InsertAt
     */
    public function Prepend(string|\Stringable $substring): CString
    {
        $clone = clone $this;
        return $clone->PrependInPlace($substring);
    }

    /**
     * Inserts the specified string at the end.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @param string|\Stringable $substring
     *   The string to append.
     * @return self
     *   The current instance.
     *
     * @see Append
     * @see InsertAt
     */
    public function AppendInPlace(string|\Stringable $substring): self
    {
        if ($substring instanceof \Stringable) {
            $substring = (string)$substring;
        }
        $this->value .= $substring;
        return $this;
    }

    /**
     * Inserts the specified string at the end.
     *
     * @param string|\Stringable $substring
     *   The string to append.
     * @return CString
     *   A new `CString` instance with the substring appended.
     *
     * @see AppendInPlace
     * @see InsertAt
     */
    public function Append(string|\Stringable $substring): CString
    {
        $clone = clone $this;
        return $clone->AppendInPlace($substring);
    }

    /**
     * Extracts a specified number of characters from the left side of the
     * string.
     *
     * @param int $count
     *   The number of characters to return. If greater than or equal to the
     *   length of the string, the entire string is returned.
     * @return CString
     *   A new `CString` instance with the leftmost characters, or an empty
     *   instance if `$count` is not positive.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see Right
     * @see Middle
     */
    public function Left(int $count): CString
    {
        if ($count <= 0) {
            return $this->empty();
        }
        if ($this->isSingleByte) {
            $substring = \substr($this->value, 0, $count);
        } else {
            $substring = \mb_substr($this->value, 0, $count, $this->encoding);
        }
        return new CString($substring, $this->encoding);
    }

    /**
     * Extracts a specified number of characters from the right side of the
     * string.
     *
     * @param int $count
     *   The number of characters to return. If greater than or equal to the
     *   length of the string, the entire string is returned.
     * @return CString
     *   A new `CString` instance with the rightmost characters, or an empty
     *   instance if `$count` is not positive.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see Left
     * @see Middle
     */
    public function Right(int $count): CString
    {
        if ($count <= 0) {
            return $this->empty();
        }
        if ($this->isSingleByte) {
            $substring = \substr($this->value, -$count);
        } else {
            $substring = \mb_substr($this->value, -$count, $count, $this->encoding);
        }
        return new CString($substring, $this->encoding);
    }

    /**
     * Extracts a specified number of characters starting from a specified
     * offset.
     *
     * @param int $offset
     *   The zero-based starting offset of the characters to return.
     * @param int $count (Optional)
     *   The number of characters to return. If omitted, or if fewer characters
     *   are available from the offset, only the available characters are returned.
     * @return CString
     *   A new `CString` instance with the specified middle characters, or an
     *   empty instance if `$offset` is out of range or `$count` is not positive.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see Left
     * @see Right
     */
    public function Middle(int $offset, int $count = PHP_INT_MAX): CString
    {
        if ($offset < 0 || $count <= 0) {
            return $this->empty();
        }
        if ($this->isSingleByte) {
            $substring = \substr($this->value, $offset, $count);
        } else {
            $substring = \mb_substr($this->value, $offset, $count, $this->encoding);
        }
        return new CString($substring, $this->encoding);
    }

    /**
     * Trims whitespace or specified characters from both sides of the string.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @param ?string $characters (Optional)
     *   Characters to trim. Defaults to trimming whitespace characters.
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see Trim
     * @see TrimLeftInPlace
     * @see TrimRightInPlace
     */
    public function TrimInPlace(?string $characters = null): self
    {
        if ($this->isSingleByte) {
            if ($characters === null) {
                $this->value = \trim($this->value);
            } else {
                $this->value = \trim($this->value, $characters);
            }
        } else {
            if (PHP_VERSION_ID >= 80400) {
                $this->value = \mb_trim($this->value, $characters, $this->encoding);
            } else {
                $this->value = $this->withMultibyteRegexEncoding(function()
                    use($characters)
                {
                    if ($characters === null) {
                        return \mb_ereg_replace(
                            '^[[:space:]]+|[[:space:]]+$', '', $this->value);
                    } elseif ($characters !== '') {
                        $characters = \preg_quote($characters);
                        return \mb_ereg_replace(
                            "^[$characters]+|[$characters]+\$", '', $this->value);
                    }
                    return $this->value;
                });
            }
        }
        return $this;
    }

    /**
     * Trims whitespace or specified characters from both sides of the string.
     *
     * @param ?string $characters (Optional)
     *   Characters to trim. Defaults to trimming whitespace characters.
     * @return CString
     *   A new `CString` instance with the trimmed string.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see TrimInPlace
     * @see TrimLeft
     * @see TrimRight
     */
    public function Trim(?string $characters = null): CString
    {
        $clone = clone $this;
        return $clone->TrimInPlace($characters);
    }

    /**
     * Trims whitespace or specified characters from the start (left) of the
     * string.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @param ?string $characters (Optional)
     *   Characters to trim. Defaults to trimming whitespace characters.
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see TrimLeft
     * @see TrimInPlace
     * @see TrimRightInPlace
     */
    public function TrimLeftInPlace(?string $characters = null): self
    {
        if ($this->isSingleByte) {
            if ($characters === null) {
                $this->value = \ltrim($this->value);
            } else {
                $this->value = \ltrim($this->value, $characters);
            }
        } else {
            if (PHP_VERSION_ID >= 80400) {
                $this->value = \mb_ltrim($this->value, $characters, $this->encoding);
            } else {
                $this->value = $this->withMultibyteRegexEncoding(function()
                    use($characters)
                {
                    if ($characters === null) {
                        return \mb_ereg_replace(
                            '^[[:space:]]+', '', $this->value);
                    } elseif ($characters !== '') {
                        $characters = \preg_quote($characters);
                        return \mb_ereg_replace(
                            "^[$characters]+", '', $this->value);
                    }
                    return $this->value;
                });
            }
        }
        return $this;
    }

    /**
     * Trims whitespace or specified characters from the start (left) of the
     * string.
     *
     * @param ?string $characters (Optional)
     *   Characters to trim. Defaults to trimming whitespace characters.
     * @return CString
     *   A new `CString` instance with the trimmed string.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see TrimLeftInPlace
     * @see Trim
     * @see TrimRight
     */
    public function TrimLeft(?string $characters = null): CString
    {
        $clone = clone $this;
        return $clone->TrimLeftInPlace($characters);
    }

    /**
     * Trims whitespace or specified characters from the end (right) of the
     * string.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @param ?string $characters (Optional)
     *   Characters to trim. Defaults to trimming whitespace characters.
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see TrimRight
     * @see TrimInPlace
     * @see TrimLeftInPlace
     */
    public function TrimRightInPlace(?string $characters = null): self
    {
        if ($this->isSingleByte) {
            if ($characters === null) {
                $this->value = \rtrim($this->value);
            } else {
                $this->value = \rtrim($this->value, $characters);
            }
        } else {
            if (PHP_VERSION_ID >= 80400) {
                $this->value = \mb_rtrim($this->value, $characters, $this->encoding);
            } else {
                $this->value = $this->withMultibyteRegexEncoding(function()
                    use($characters)
                {
                    if ($characters === null) {
                        return \mb_ereg_replace(
                            '[[:space:]]+$', '', $this->value);
                    } elseif ($characters !== '') {
                        $characters = \preg_quote($characters);
                        return \mb_ereg_replace(
                            "[$characters]+\$", '', $this->value);
                    }
                    return $this->value;
                });
            }
        }
        return $this;
    }

    /**
     * Trims whitespace or specified characters from the end (right) of the
     * string.
     *
     * @param ?string $characters (Optional)
     *   Characters to trim. Defaults to trimming whitespace characters.
     * @return CString
     *   A new `CString` instance with the trimmed string.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see TrimRightInPlace
     * @see Trim
     * @see TrimLeft
     */
    public function TrimRight(?string $characters = null): CString
    {
        $clone = clone $this;
        return $clone->TrimRightInPlace($characters);
    }

    /**
     * Converts to lowercase.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see UppercaseInPlace
     * @see Lowercase
     */
    public function LowercaseInPlace(): self
    {
        if ($this->isSingleByte) {
            $this->value = \strtolower($this->value);
        } else {
            $this->value = \mb_strtolower($this->value, $this->encoding);
        }
        return $this;
    }

    /**
     * Converts to lowercase.
     *
     * @return CString
     *   A new `CString` instance with all characters converted to lowercase.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see LowercaseInPlace
     * @see Uppercase
     */
    public function Lowercase(): CString
    {
        $clone = clone $this;
        return $clone->LowercaseInPlace();
    }

    /**
     * Converts to uppercase.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see LowercaseInPlace
     * @see Uppercase
     */
    public function UppercaseInPlace(): self
    {
        if ($this->isSingleByte) {
            $this->value = \strtoupper($this->value);
        } else {
            $this->value = \mb_strtoupper($this->value, $this->encoding);
        }
        return $this;
    }

    /**
     * Converts to uppercase.
     *
     * @return CString
     *   A new `CString` instance with all characters converted to uppercase.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see UppercaseInPlace
     * @see Lowercase
     */
    public function Uppercase(): CString
    {
        $clone = clone $this;
        return $clone->UppercaseInPlace();
    }

    /**
     * Checks if this string is equal to another string.
     *
     * @param string|\Stringable $other
     *   The string to compare with.
     * @param bool $caseSensitive (Optional)
     *   Whether the comparison should be case-sensitive. By default, it is
     *   case-sensitive.
     * @return bool
     *   Returns `true` if the strings are equal, `false` otherwise.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function Equals(string|\Stringable $other, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            return $this->value === (string)$other;
        } else {
            if (!$other instanceof self) {
                $other = $this->wrap((string)$other);
            }
            return (string)$this->Lowercase() === (string)$other->Lowercase();
        }
    }

    /**
     * Checks if the string starts with the specified search string.
     *
     * @param string|\Stringable $searchString
     *   The string to check if the instance starts with it.
     * @param bool $caseSensitive (Optional)
     *   Whether the comparison should be case-sensitive. By default, it is
     *   case-sensitive.
     * @return bool
     *   Returns `true` if the string starts with the given search string,
     *   `false` otherwise.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see EndsWith
     */
    public function StartsWith(string|\Stringable $searchString,
        bool $caseSensitive = true): bool
    {
        if (!$searchString instanceof self) {
            $searchString = $this->wrap((string)$searchString);
        }
        $searchStringLength = $searchString->Length();
        if ($searchStringLength > $this->Length()) {
            return false;
        }
        return $this->Left($searchStringLength)
                    ->Equals($searchString, $caseSensitive);
    }

    /**
     * Checks if the string ends with the specified search string.
     *
     * @param string|\Stringable $searchString
     *   The string to check if the instance ends with it.
     * @param bool $caseSensitive (Optional)
     *   Whether the comparison should be case-sensitive. By default, it is
     *   case-sensitive.
     * @return bool
     *   Returns `true` if the string ends with the given search string,
     *   `false` otherwise.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see StartsWith
     */
    public function EndsWith(string|\Stringable $searchString,
        bool $caseSensitive = true): bool
    {
        if (!$searchString instanceof self) {
            $searchString = $this->wrap((string)$searchString);
        }
        $searchStringLength = $searchString->Length();
        if ($searchStringLength > $this->Length()) {
            return false;
        }
        return $this->Right($searchStringLength)
                    ->Equals($searchString, $caseSensitive);
    }

    /**
     * Finds the offset of the first occurrence of a string.
     *
     * @param string|\Stringable $searchString
     *   The string to search for.
     * @param int $startOffset (Optional)
     *   The zero-based offset from which to start the search. Defaults to 0.
     * @param bool $caseSensitive (Optional)
     *   Whether the comparison should be case-sensitive. By default, it is
     *   case-sensitive.
     * @return ?int
     *   The zero-based offset of the search string, or `null` if not found.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function IndexOf(string|\Stringable $searchString, int $startOffset = 0,
        bool $caseSensitive = true): ?int
    {
        if ($searchString instanceof \Stringable) {
            $searchString = (string)$searchString;
        }
        if ($this->isSingleByte) {
            if ($caseSensitive) {
                $foundOffset = \strpos($this->value, $searchString, $startOffset);
            } else {
                $foundOffset = \stripos($this->value, $searchString, $startOffset);
            }
        } else {
            if ($caseSensitive) {
                $foundOffset = \mb_strpos($this->value, $searchString, $startOffset,
                    $this->encoding);
            } else {
                $foundOffset = \mb_stripos($this->value, $searchString, $startOffset,
                    $this->encoding);
            }
        }
        if ($foundOffset === false) {
            return null;
        }
        return $foundOffset;
    }

    /**
     * Replaces all occurrences of a specified search string with a replacement
     * string.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @param string|\Stringable $searchString
     *   The substring to search for.
     * @param string|\Stringable $replacement
     *   The substring to replace each occurrence of the search string.
     * @param bool $caseSensitive (Optional)
     *   Whether the comparison should be case-sensitive. By default, it is
     *   case-sensitive.
     * @return self
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see Replace
     */
    public function ReplaceInPlace(
        string|\Stringable $searchString,
        string|\Stringable $replacement,
        bool $caseSensitive = true
        ): self
    {
        if ($searchString instanceof \Stringable) {
            $searchString = (string)$searchString;
        }
        if ($replacement instanceof \Stringable) {
            $replacement = (string)$replacement;
        }
        if ($this->isSingleByte) {
            if ($caseSensitive) {
                $this->value = \str_replace($searchString, $replacement, $this->value);
            } else {
                $this->value = \str_ireplace($searchString, $replacement, $this->value);
            }
        } else {
            $this->value = $this->withMultibyteRegexEncoding(function()
                use($searchString, $replacement, $caseSensitive)
            {
                $searchString = \preg_quote($searchString);
                if ($caseSensitive) {
                    return \mb_ereg_replace($searchString, $replacement, $this->value);
                } else {
                    return \mb_eregi_replace($searchString, $replacement, $this->value);
                }
            });
        }
        return $this;
    }

    /**
     * Replaces all occurrences of a specified search string with a replacement
     * string.
     *
     * @param string|\Stringable $searchString
     *   The substring to search for.
     * @param string|\Stringable $replacement
     *   The substring to replace each occurrence of the search string.
     * @param bool $caseSensitive (Optional)
     *   Whether the comparison should be case-sensitive. By default, it is
     *   case-sensitive.
     * @return CString
     *   A new `CString` instance with the replacements made.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @see ReplaceInPlace
     */
    public function Replace(
        string|\Stringable $searchString,
        string|\Stringable $replacement,
        bool $caseSensitive = true
        ): CString
    {
        $clone = clone $this;
        return $clone->ReplaceInPlace($searchString, $replacement, $caseSensitive);
    }

    /**
     * Splits the string by a given delimiter, yielding each substring as a
     * `CString` instance.
     *
     * This method provides memory-efficient processing by yielding each
     * substring one at a time, making it suitable for large strings.
     *
     * By default, it performs a straightforward split without trimming or
     * excluding empty results. These behaviors can be customized with options.
     *
     * @param string $delimiter
     *   The delimiter indicating the points at which each split should occur.
     * @param int $options (Optional)
     *   Bitwise options for splitting behavior. `CString::SPLIT_OPTION_TRIM` trims
     *   whitespace from each substring, and `CString::SPLIT_OPTION_EXCLUDE_EMPTY`
     *   excludes empty substrings from the result. The default is `SPLIT_OPTION_NONE`,
     *   which applies no trimming or exclusion.
     * @return \Generator
     *   A generator yielding `CString` instances for each substring.
     *
     * @see SplitToArray
     */
    public function Split(string $delimiter, int $options = self::SPLIT_OPTION_NONE): \Generator
    {
        $delimiter = $this->wrap($delimiter);
        $delimiterLength = $delimiter->Length();
        if ($delimiterLength === 0) {
            return;
        }
        $yieldIfEligible = function(CString $substring) use($options): \Generator {
            if ($options & self::SPLIT_OPTION_TRIM) {
                $substring = $substring->Trim();
            }
            if (!($options & self::SPLIT_OPTION_EXCLUDE_EMPTY) || !$substring->IsEmpty()) {
                yield $substring;
            }
        };
        $start = 0;
        while (($offset = $this->IndexOf($delimiter, $start)) !== null) {
            yield from $yieldIfEligible($this->Middle($start, $offset - $start));
            $start = $offset + $delimiterLength;
        }
        yield from $yieldIfEligible($this->Middle($start));
    }

    /**
     * Splits the string by a given delimiter and returns the result as an array
     * of `CString` instances.
     *
     * This method is a convenient alternative to `Split`, returning the results
     * directly as an array of `CString` instances.
     *
     * By default, it performs a straightforward split without trimming or
     * excluding empty results. These behaviors can be customized with options.
     *
     * @param string $delimiter
     *   The delimiter indicating the points at which each split should occur.
     * @param int $options (Optional)
     *   Bitwise options for splitting behavior. `CString::SPLIT_OPTION_TRIM` trims
     *   whitespace from each substring, and `CString::SPLIT_OPTION_EXCLUDE_EMPTY`
     *   excludes empty substrings from the result. The default is `SPLIT_OPTION_NONE`,
     *   which applies no trimming or exclusion.
     * @return CString[]
     *   An array of `CString` instances for each substring.
     *
     * @see Split
     */
    public function SplitToArray(string $delimiter, int $options = self::SPLIT_OPTION_NONE): array
    {
        // Setting `false` prevents `iterator_to_array` from preserving keys.
        // Since `yield from` in `Split` retains keys, using `false` avoids key
        // collisions that would otherwise overwrite earlier elements, returning
        // only the last item.
        return \iterator_to_array($this->Split($delimiter, $options), false);
    }

    /**
     * Applies a callable function to the current value.
     *
     * This version of the method directly modifies the current instance,
     * instead of creating and returning a new one.
     *
     * @param callable $function
     *   The function to apply to the current value. The function must accept a
     *   `string` as its first parameter. Any additional arguments passed to
     *   this method will be forwarded to the callable.
     * @param mixed ...$args
     *   Additional arguments to pass to the callable.
     * @return self
     *   The current instance.
     *
     * @see Apply
     */
    public function ApplyInPlace(callable $function, mixed ...$args): self
    {
        $this->value = (string)$function($this->value, ...$args);
        return $this;
    }

    /**
     * Applies a callable function to the current value.
     *
     * @param callable $function
     *   The function to apply to the current value. The function must accept a
     *   `string` as its first parameter. Any additional arguments passed to
     *   this method will be forwarded to the callable.
     * @param mixed ...$args
     *   Additional arguments to pass to the callable.
     * @return CString
     *   A new `CString` instance containing the result of the callable.
     *
     * @see ApplyInPlace
     */
    public function Apply(callable $function, mixed ...$args): CString
    {
        return new CString((string)$function($this->value, ...$args));
    }

    #region Interface: Stringable

    /**
     * Returns the string representation for use in string contexts.
     *
     * @return string
     *   The string value stored in the instance.
     *
     * @override
     */
    public function __toString(): string
    {
        return $this->value;
    }

    #endregion Interface: Stringable

    #region Interface: ArrayAccess

    /**
     * Provides array-like access to check if a character exists at a given offset.
     *
     * @param mixed $offset
     *   The zero-based offset to check.
     * @return bool
     *   Returns `true` if the offset is within range, `false` otherwise.
     * @throws \InvalidArgumentException
     *   If the offset is not an integer.
     *
     * @override
     */
    public function offsetExists(mixed $offset): bool
    {
        if (!\is_int($offset)) {
            throw new \InvalidArgumentException('Offset must be an integer.');
        }
        return 0 <= $offset && $offset < $this->Length();
    }

    /**
     * Provides array-like access to retrieve the character at a given offset.
     *
     * @param mixed $offset
     *   The zero-based offset of the character to return.
     * @return mixed
     *   The character at the specified offset, or an empty string if the offset
     *   is out of range.
     * @throws \TypeError
     *   If the offset is not an integer.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @override
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->At($offset);
    }

    /**
     * Provides array-like access to set the character at a specified offset.
     *
     * @param mixed $offset
     *   The zero-based offset where the character will be set. If the offset is
     *   negative or greater than or equal to the length of the string, no
     *   changes will be made.
     * @param mixed $value
     *   The character to set at the specified offset. If more than one character
     *   is provided, no changes will be made.
     * @throws \TypeError
     *   If the offset is not an integer or the value is not a string.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @override
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->SetAt($offset, $value);
    }

    /**
     * Provides array-like access to delete the character at a specified offset.
     *
     * @param mixed $offset
     *   The zero-based offset where the character will be removed. If the
     *   offset is negative or greater than or equal to the length of the
     *   string, no changes will be made.
     * @throws \TypeError
     *   If the offset is not an integer.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     *
     * @override
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->DeleteAt($offset);
    }

    #endregion Interface: ArrayAccess

    #region Interface: IteratorAggregate

    /**
     * Provides array-like traversal over each character.
     *
     * @return \Traversable
     *   An iterator yielding each character.
     *
     * @override
     */
    public function getIterator(): \Traversable
    {
        $length = $this->Length();
        for ($i = 0; $i < $length; ++$i) {
            yield $this->At($i);
        }
    }

    #endregion Interface: IteratorAggregate

    #endregion public

    #region private ------------------------------------------------------------

    /**
     * Checks if the provided encoding is a single-byte encoding.
     *
     * Note: Support for ISO-8859-6 (Arabic) and ISO-8859-8 (Hebrew) has been
     * removed due to limitations in character coverage and inconsistent support
     * in modern PHP environments. These encodings cover only a subset of
     * characters for Arabic and Hebrew scripts, and lack support for essential
     * diacritics and punctuation. Given the shift toward more comprehensive
     * encodings like UTF-8, these encodings were deemed impractical for
     * continued support.
     *
     * @param string $encoding
     *   The encoding to check.
     * @return bool
     *   Returns `true` if it's a single-byte encoding, `false` otherwise.
     */
    private static function isSingleByteEncoding(string $encoding): bool
    {
        static $singleByteEncodings = [
            'ASCII' => 1,        // Standard ASCII (7-bit)
            'US-ASCII' => 1,     // Alias for ASCII
            'CP850' => 1,        // Western European, DOS
            'CP866' => 1,        // Cyrillic, DOS
            'CP1251' => 1,       // Cyrillic, Windows
            'CP1254' => 1,       // Turkish, Windows
            'ISO-8859-1' => 1,   // Latin-1, Western European
            'ISO-8859-2' => 1,   // Latin-2, Central European
            'ISO-8859-3' => 1,   // Latin-3, South European
            'ISO-8859-4' => 1,   // Latin-4, North European
            'ISO-8859-5' => 1,   // Cyrillic
            'ISO-8859-7' => 1,   // Greek
            'ISO-8859-9' => 1,   // Latin-5, Turkish
            'ISO-8859-10' => 1,  // Latin-6, Nordic
            'ISO-8859-13' => 1,  // Baltic Rim
            'ISO-8859-14' => 1,  // Latin-8, Celtic
            'ISO-8859-15' => 1,  // Latin-9, Western European
            'ISO-8859-16' => 1,  // Latin-10, South-Eastern European
            'KOI8-R' => 1,       // Cyrillic, Russian
            'KOI8-U' => 1,       // Cyrillic, Ukrainian
            'WINDOWS-1251' => 1, // Cyrillic, Windows
            'WINDOWS-1252' => 1, // Western European, Windows
            'WINDOWS-1254' => 1, // Turkish, Windows
        ];
        return isset($singleByteEncodings[\strtoupper($encoding)]);
    }

    /**
     * Returns a new `CString` instance whose value is an empty string.
     *
     * @return CString
     *   A new `CString` instance that contains an empty string and has the same
     *   encoding as the current instance.
     */
    private function empty(): CString
    {
        return new CString('', $this->encoding);
    }

    /**
     * Returns a new `CString` instance with the given string as its value.
     *
     * @param string $string
     *   The native string value to wrap in a `CString`.
     * @return CString
     *   A new `CString` instance containing the provided string and having the
     *   same encoding as the current instance.
     */
    private function wrap(string $string): CString
    {
        return new CString($string, $this->encoding);
    }

    /**
     * Sets the global multibyte regex encoding name to be the instance's
     * encoding and restores it to the original after executing the callback.
     *
     * For efficiency, if the global encoding name is already the same as the
     * instance's, it skips changing it and directly runs the callback.
     *
     * @param callable $callback
     *   The function to execute.
     * @return mixed
     *   The return value of the callback.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    private function withMultibyteRegexEncoding(callable $callback): mixed
    {
        $originalEncoding = \mb_regex_encoding();
        if (0 === \strcasecmp($originalEncoding, $this->encoding)) {
            return $callback();
        }
        \mb_regex_encoding($this->encoding);
        try {
            return $callback();
        } finally {
            \mb_regex_encoding($originalEncoding);
        }
    }

    #endregion private
}
