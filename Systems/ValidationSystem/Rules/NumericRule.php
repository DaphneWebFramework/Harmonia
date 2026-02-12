<?php declare(strict_types=1);
/**
 * NumericRule.php
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
 * Validates whether a given field contains a number or a numeric string.
 *
 * By default, both native numbers (integers and floats) and string
 * representations of numbers (referred to as numeric) are valid. If the
 * optional parameter "strict" is provided, only native numbers are considered
 * valid.
 */
class NumericRule extends Rule
{
    /**
     * Validates that the field contains a number or a numeric string.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   Optional parameter to specify validation mode. If set to "strict", the
     *   value must be a number. If omitted, both numbers and numeric strings
     *   are accepted.
     * @throws \InvalidArgumentException
     *   If the parameter is neither "strict" nor `null`.
     * @throws \RuntimeException
     *   If the parameter is "strict" and the value is not a number; or if no
     *   parameter is given and the value is not a number or numeric string; or
     *   if an invalid parameter is given.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if ($param === 'strict') {
            if ($this->nativeFunctions->IsNumber($value)) {
                return;
            }
        } elseif ($param === null) {
            if ($this->nativeFunctions->IsNumeric($value)) {
                return;
            }
        } else {
            throw new \InvalidArgumentException(
                "Rule 'numeric' must be used with either 'strict' or no parameter.");
        }
        throw new \RuntimeException("Field '{$field}' must be numeric.");
    }
}
