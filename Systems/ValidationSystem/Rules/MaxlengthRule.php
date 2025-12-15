<?php declare(strict_types=1);
/**
 * MaxlengthRule.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem\Rules;

/**
 * Validates whether a given field satisfies a specified maximum length.
 */
class MaxlengthRule extends Rule
{
    /**
     * Validates that the field contains a string not exceeding the specified
     * maximum length.
     *
     * The specified maximum length is inclusive, meaning a string with exactly
     * `$param` characters is valid.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   The maximum allowed length, inclusive.
     * @throws \InvalidArgumentException
     *   If the parameter is not an integer.
     * @throws \RuntimeException
     *   If the value is not a string, the specified length is not an
     *   integer-like value, or the value exceeds the specified length.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if (!$this->nativeFunctions->IsString($value)) {
            throw new \RuntimeException("Field '{$field}' must be a string.");
        }
        if (!$this->nativeFunctions->IsIntegerLike($param)) {
            throw new \InvalidArgumentException(
                "Rule 'maxLength' must be used with an integer.");
        }
        if (\strlen($value) <= (int)$param) {
            return;
        }
        throw new \RuntimeException(
            "Field '{$field}' must have a maximum length of {$param} characters.");
    }
}
