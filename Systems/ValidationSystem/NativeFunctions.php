<?php declare(strict_types=1);
/**
 * NativeFunctions.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem;

/**
 * Provides thin wrappers for native PHP functions to validate various data types.
 *
 * This ensures a unified interface for validation while keeping logic minimal
 * and testable, without adding extra behavior beyond the underlying PHP functions.
 */
class NativeFunctions
{
    /**
     * Determines if the given value is of integer type.
     *
     * @param mixed $value
     *   The value to check for integer type.
     * @return bool
     *   Returns `true` if the value is an integer, `false` otherwise.
     */
    public function IsInteger(mixed $value): bool
    {
        return \is_int($value);
    }

    /**
     * Determines if the given value is of a numeric type.
     *
     * @param mixed $value
     *   The value to check for numeric type.
     * @return bool
     *   Returns `true` if the value is numeric, `false` otherwise.
     */
    public function IsNumeric(mixed $value): bool
    {
        return \is_numeric($value);
    }

    /**
     * Determines if the given value is of string type.
     *
     * @param mixed $value
     *   The value to check for string type.
     * @return bool
     *   Returns `true` if the value is a string, `false` otherwise.
     */
    public function IsString(mixed $value): bool
    {
        return \is_string($value);
    }

    /**
     * Determines if the given value is of integer type or a string representing
     * an integer.
     *
     * @param mixed $value
     *   The value to validate as an integer or integer string.
     * @return bool
     *   Returns `true` if the value is an integer or a string representing an
     *   integer, `false` otherwise.
     */
    public function IsIntegerLike(mixed $value): bool
    {
        // Prevent `true` from being converted to "1" by `filter_var`.
        if ($value === true) {
            return false;
        }
        return false !== \filter_var($value, \FILTER_VALIDATE_INT);
    }

    /**
     * Determines if the given value is a valid email address.
     *
     * @param mixed $value
     *   The value to validate as an email address.
     * @return bool
     *   Returns `true` if the value is a valid email address, `false` otherwise.
     */
    public function IsEmailAddress(mixed $value): bool
    {
        return false !== \filter_var($value, \FILTER_VALIDATE_EMAIL);
    }

    /**
     * Determines if the given value is of array type.
     *
     * @param mixed $value
     *   The value to check for array type.
     * @return bool
     *   Returns `true` if the value is an array, `false` otherwise.
     */
    public function IsArray(mixed $value): bool
    {
        return \is_array($value);
    }

    /**
     * Determines if the given value is a valid datetime string.
     *
     * @param mixed $value
     *   The value to validate as a datetime string.
     * @return bool
     *   Returns `true` if the value is a valid datetime string, `false`
     *   otherwise.
     */
    public function IsDateTime(mixed $value): bool
    {
        if (!\is_string($value)) {
            return false;
        }
        try {
            new \DateTime($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Determines if the given value represents an uploaded file.
     *
     * @param mixed $value
     *   The value to validate as a file upload array.
     * @return bool
     *   Returns `true` if the value represents an uploaded file, `false`
     *   otherwise.
     */
    public function IsUploadedFile(mixed $value): bool
    {
        if (!\is_array($value)) {
            return false;
        }
        if (!\array_key_exists('name', $value)
         || !\is_string($value['name'])) {
            return false;
        }
        if (!\array_key_exists('type', $value)
         || !\is_string($value['type'])) {
            return false;
        }
        if (!\array_key_exists('tmp_name', $value)
         || !\is_string($value['tmp_name'])
         || !\is_file($value['tmp_name'])) {
            return false;
        }
        if (!\array_key_exists('error', $value)
         || \UPLOAD_ERR_OK !== $value['error']) {
            return false;
        }
        if (!\array_key_exists('size', $value)
         || !\is_int($value['size'])) {
            return false;
        }
        return true;
    }

    /**
     * Determines if the given value matches the pattern specified by the
     * parameter.
     *
     * @param string $value
     *   The string value to validate against the regular expression pattern.
     * @param string $param
     *   The regular expression pattern.
     * @return bool
     *   Returns `true` if the value matches the pattern, `false` otherwise.
     */
    public function MatchRegex(string $value, string $param): bool
    {
        // Temporarily override error handling to catch regex compilation errors.
        // `static function` avoids binding `$this`, reducing unnecessary overhead.
        \set_error_handler(static function() {
            throw new \ErrorException();
        });
        try {
            // Don't suppress with `@preg_match`, as it causes errors to leak
            // into registered shutdown handlers. Suppressed errors still affect
            // `error_get_last()`.
            return 1 === \preg_match($param, $value);
        } catch (\ErrorException) {
            // Regex compilation failed: Treat as non-matching without exposing
            // an error.
            return false;
        } finally {
            // Always restore the original error handler.
            \restore_error_handler();
        }
    }

    /**
     * Validates whether a specified string represents a datetime that matches
     * a given format.
     *
     * @param string $value
     *   The string value to validate as a datetime.
     * @param string $param
     *   The format string that the datetime should adhere to, as per
     *   `DateTime::createFromFormat` documentation.
     * @return bool
     *   Returns `true` if `$value` matches the format specified in `$param`,
     *   `false` otherwise.
     *
     * @link
     *   https://www.php.net/manual/en/datetime.formats.php
     */
    public function MatchDateTime(string $value, string $param): bool
    {
        $dt = \DateTime::createFromFormat($param, $value);
        if ($dt === false) {
            return false;
        }
        if ($dt->format($param) !== $value) {
            return false;
        }
        return true;
    }

    /**
     * Validates whether a given value is a valid case of a specified enum class.
     *
     * Supports both backed and pure enums. For backed enums, the value must
     * match the enum's backing type exactly and exist in its defined cases.
     * For pure enums, the value must match the name of a defined case.
     *
     * @param mixed $value
     *   The value to validate as an enum case.
     * @param string $enumClass
     *   The fully qualified name of the enum class.
     * @return bool
     *   Returns `true` if the value matches a valid enum case, `false` otherwise.
     */
    public function IsEnumValue(mixed $value, string $enumClass): bool
    {
        if (!\enum_exists($enumClass)) {
            return false;
        }
        if (\is_subclass_of($enumClass, \BackedEnum::class))
        {
            $reflectionEnum = new \ReflectionEnum($enumClass);
            switch ($reflectionEnum->getBackingType()->getName())
            {
            case 'int':
                if (!\is_int($value)) {
                    return false;
                }
                break;
            case 'string':
                if (!\is_string($value)) {
                    return false;
                }
                break;
            }
            return $enumClass::tryFrom($value) !== null;
        }
        else // Pure enum: match against case names
        {
            if (!\is_string($value)) {
                return false;
            }
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $value) {
                    return true;
                }
            }
            return false;
        }
    }
}
