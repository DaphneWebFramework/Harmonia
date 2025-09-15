<?php declare(strict_types=1);
/**
 * MaxRule.php
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
 * Validates whether a given field satisfies a specified maximum value.
 */
class MaxRule extends Rule
{
    /**
     * Validates that the field contains a number not exceeding the specified
     * maximum value.
     *
     * The specified maximum value is inclusive, meaning a value equal to
     * `$param` is valid.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   The maximum allowed value, inclusive.
     * @throws \RuntimeException
     *   If the value is not numeric, the specified maximum is not numeric, or
     *   the value exceeds the specified maximum.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if (!$this->nativeFunctions->IsNumeric($value)) {
            throw new \RuntimeException("Field '{$field}' must be numeric.");
        }
        if (!$this->nativeFunctions->IsNumeric($param)) {
            throw new \RuntimeException("Rule 'max' must be used with a number.");
        }
        if ($value <= $param) {
            return;
        }
        throw new \RuntimeException(
            "Field '{$field}' must have a maximum value of {$param}.");
    }
}
