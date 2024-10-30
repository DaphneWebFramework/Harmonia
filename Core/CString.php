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
 * CString is a wrapper class for string manipulation, allowing the use of both
 * single-byte and multibyte encodings.
 *
 * This class requires PHP's `mbstring` extension for multibyte encoding support.
 */
class CString implements \Stringable
{
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
     * Constructs a new instance of CString.
     *
     * @param string|\Stringable $value
     *   The string value to store. If a `CString` instance is provided, the
     *   value, encoding, and single-byte/multibyte status are copied from the
     *   original instance.
     * @param ?string $encoding
     *   The encoding to use (e.g., 'UTF-8', 'ISO-8859-1'). If `null`, defaults
     *   to the return value of `mb_internal_encoding`. This parameter is
     *   ignored when the `$value` is an instance of `CString`.
     */
    public function __construct(
        string|\Stringable $value = '',
        ?string $encoding = null
        )
    {
        if ($value instanceof self) {
            $this->value = $value->value;
            $this->encoding = $value->encoding;
            $this->isSingleByte = $value->isSingleByte;
        } else {
            if (\is_string($value)) {
                $this->value = $value;
            } else { // $value instanceof \Stringable
                $this->value = (string)$value;
            }
            $this->encoding = $encoding ?: \mb_internal_encoding();
            $this->isSingleByte = self::isSingleByteEncoding($this->encoding);
        }
    }

    /**
     * Converts the CString instance to a string.
     *
     * @return string
     *   The string value stored in the instance.
     */
    public function __toString(): string
    {
        return $this->value;
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
     * Returns the length of the string.
     *
     * @return int
     *   The number of characters in the string.
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
     * Returns the first character of the string.
     *
     * @return string
     *   The first character of the string, or an empty string if the string
     *   is empty.
     * @throws \ValueError
     *   If an error occurs due to encoding.
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
     * Returns the last character of the string.
     *
     * @return string
     *   The last character of the string, or an empty string if the string
     *   is empty.
     * @throws \ValueError
     *   If an error occurs due to encoding.
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
     *   is out of bounds.
     * @throws \ValueError
     *   If an error occurs due to encoding.
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
     * @return CString
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function SetAt(int $offset, string $character): CString
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
     *   made.
     * @param string $substring
     *   The substring to insert. If an empty string is provided, no changes
     *   will be made.
     * @return CString
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function InsertAt(int $offset, string $substring): CString
    {
        if ($offset < 0) {
            return $this;
        }
        $length = $this->Length();
        if ($offset > $length) {
            return $this;
        }
        $substring = $this->wrap($substring);
        if ($substring->IsEmpty()) {
            return $this;
        }
        if ($offset === $length) {
            $this->value .= (string)$substring;
            return $this;
        }
        if ($this->isSingleByte) {
            $this->value =
                \substr($this->value, 0, $offset)
              . (string)$substring
              . \substr($this->value, $offset);
        } else {
            $this->value =
                \mb_substr($this->value, 0, $offset, $this->encoding)
              . (string)$substring
              . \mb_substr($this->value, $offset, null, $this->encoding);
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
     * @return CString
     *   The current instance.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function DeleteAt(int $offset, int $count = 1): CString
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
     * Extracts a specified number of characters from the left side of the
     * string.
     *
     * @param int $count
     *   The number of characters to return. If greater than or equal to the
     *   length of the string, the entire string is returned.
     * @return CString
     *   A new `CString` instance with the leftmost characters, or an empty
     *   instance if the count is negative.
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
     *   instance if the count is negative.
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
     * offset in the string.
     *
     * @param int $offset
     *   The zero-based starting offset of the characters to return.
     * @param int $count (Optional)
     *   The number of characters to return. If omitted, or if fewer characters
     *   are available in the string from the offset, only the available
     *   characters are returned.
     * @return CString
     *   A new `CString` instance with the specified middle characters, or an
     *   empty instance if the offset or count is negative.
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
     * @param ?string $characters (Optional)
     *   Characters to trim. Defaults to trimming whitespace characters.
     * @return CString
     *   A new `CString` instance with the trimmed string.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    public function Trim(?string $characters = null): CString
    {
        if ($this->isSingleByte) {
            if ($characters === null) {
                $trimmed = \trim($this->value);
            } else {
                $trimmed = \trim($this->value, $characters);
            }
        } else {
            if (PHP_VERSION_ID >= 80400) {
                $trimmed = \mb_trim($this->value, $characters, $this->encoding);
            } else {
                $trimmed = $this->withRegexEncoding(function() use($characters) {
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
        return new CString($trimmed, $this->encoding);
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
     */
    public function TrimLeft(?string $characters = null): CString
    {
        if ($this->isSingleByte) {
            if ($characters === null) {
                $trimmed = \ltrim($this->value);
            } else {
                $trimmed = \ltrim($this->value, $characters);
            }
        } else {
            if (PHP_VERSION_ID >= 80400) {
                $trimmed = \mb_ltrim($this->value, $characters, $this->encoding);
            } else {
                $trimmed = $this->withRegexEncoding(function () use($characters) {
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
        return new CString($trimmed, $this->encoding);
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
     */
    public function TrimRight(?string $characters = null): CString
    {
        if ($this->isSingleByte) {
            if ($characters === null) {
                $trimmed = \rtrim($this->value);
            } else {
                $trimmed = \rtrim($this->value, $characters);
            }
        } else {
            if (PHP_VERSION_ID >= 80400) {
                $trimmed = \mb_rtrim($this->value, $characters, $this->encoding);
            } else {
                $trimmed = $this->withRegexEncoding(function () use($characters) {
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
        return new CString($trimmed, $this->encoding);
    }

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
     * Returns a `CString` instance whose value is an empty string.
     *
     * @return CString
     *   A `CString` instance that contains an empty string and has the same
     *   encoding as the current instance.
     */
    private function empty(): CString
    {
        static $emptyInstance = null;
        if ($emptyInstance === null) {
            $emptyInstance = new CString('', $this->encoding);
        }
        return $emptyInstance;
    }

    /**
     * Converts a native string into a `CString` instance, ensuring
     * compatibility with the instance's encoding.
     *
     * @param string $string
     *   The native string to validate and convert to a `CString`.
     * @return CString
     *   A `CString` instance with the same encoding as the current instance.
     * @throws \ValueError
     *   If the string's encoding is not compatible with the current `CString`
     *   instance.
     */
    private function wrap(string $string): CString
    {
        if (!\mb_check_encoding($string, $this->encoding)) {
            throw new \ValueError(
                "String is not compatible with encoding '{$this->encoding}'.");
        }
        $detectedEncoding = \mb_detect_encoding($string);
        if ($detectedEncoding === false) {
            throw new \ValueError("Unable to detect string's encoding.");
        }
        if ($detectedEncoding !== $this->encoding) {
            $convertedString = \mb_convert_encoding($string, $this->encoding,
                $detectedEncoding);
            if ($convertedString !== $string) {
                throw new \ValueError(
                    "String could not be converted to encoding '{$this->encoding}'.");
            }
        }
        return new CString($string, $this->encoding);
    }

    /**
     * Temporarily sets the instance's encoding for regex operations.
     *
     * This method sets the regex encoding to the instance's encoding, performs
     * the callback, and restores the previous encoding afterward.
     *
     * @param callable $callback
     *   The function to execute.
     * @return mixed
     *   The return value of the callback.
     * @throws \ValueError
     *   If an error occurs due to encoding.
     */
    private function withRegexEncoding(callable $callback): mixed
    {
        $previousEncoding = \mb_regex_encoding();
        \mb_regex_encoding($this->encoding);
        try {
            return $callback();
        } finally {
            \mb_regex_encoding($previousEncoding);
        }
    }

    #endregion private
}
