<?php declare(strict_types=1);
/**
 * MinRule.php
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
 * Validates whether a given field satisfies a specified minimum value.
 */
class MinRule extends Rule
{
    /**
     * Validates that the field contains a number meeting the specified minimum
     * value.
     *
     * The specified minimum value is inclusive, meaning a value equal to
     * `$param` is valid.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   The minimum allowed value, inclusive.
     * @throws \InvalidArgumentException
     *   If the parameter is not numeric.
     * @throws \RuntimeException
     *   If the value is not numeric, the specified minimum is not numeric, or
     *   the value is less than the specified minimum.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if (!$this->nativeFunctions->IsNumeric($value)) {
            throw new \RuntimeException("Field '{$field}' must be numeric.");
        }
        if (!$this->nativeFunctions->IsNumeric($param)) {
            throw new \InvalidArgumentException(
                "Rule 'min' must be used with a number.");
        }
        if ($value >= $param) {
            return;
        }
        throw new \RuntimeException(
            "Field '{$field}' must have a minimum value of {$param}.");
    }
}
