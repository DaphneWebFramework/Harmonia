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
    private string $encoding;

    /**
     * A boolean indicating if the encoding is single-byte or multibyte.
     *
     * @var bool
     */
    private bool $isSingleByte;

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
     *   If the encoding is invalid when operating in multibyte mode.
     */
    public function Length(): int
    {
        return $this->coreLength();
    }

    /**
     * Returns the first character of the string.
     *
     * @return string
     *   The first character of the string, or an empty string if the string
     *   is empty.
     * @throws \ValueError
     *   If the encoding is invalid when operating in multibyte mode.
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
     *   If the encoding is invalid when operating in multibyte mode.
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
     *   If the encoding is invalid when operating in multibyte mode.
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
     * Core routine for getting the length of a string.
     *
     * @param ?string $string (Optional)
     *   If provided, the method will calculate the length of this string
     *   instead of using the instance's value.
     * @return int
     *   The number of characters in the string.
     * @throws \ValueError
     *   If the encoding is invalid when operating in multibyte mode.
     */
    private function coreLength(?string $string = null): int
    {
        if ($string === null) {
            $string = $this->value;
        }
        if ($this->isSingleByte) {
            return \strlen($string);
        } else {
            return \mb_strlen($string, $this->encoding);
        }
    }

    #endregion private
}
