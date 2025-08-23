<?php declare(strict_types=1);
/**
 * IntegerRule.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem\Rules;

use \Harmonia\Systems\ValidationSystem\Messages;

/**
 * Validates whether a given field contains an integer or an integer-like
 * string.
 *
 * By default, both native integers and string representations of integers
 * (often referred to as integer-like) are valid. If the optional parameter
 * 'strict' is provided, only native integers are valid.
 */
class IntegerRule extends Rule
{
    /**
     * Validates that the field contains an integer or an integer-like string.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   Optional parameter to specify validation mode. If set to 'strict', the
     *   value must be an integer. If omitted, both integers and integer-like
     *   strings are accepted.
     * @throws \RuntimeException
     *   If the parameter is 'strict' and the value is not an integer; or if no
     *   parameter is given and the value is not an integer or an integer-like
     *   string; or if an invalid parameter is given.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if ($param === 'strict') {
            if ($this->nativeFunctions->IsInteger($value)) {
                return;
            }
        } else if ($param === null) {
            if ($this->nativeFunctions->IsIntegerLike($value)) {
                return;
            }
        } else {
            throw new \RuntimeException(Messages::Instance()->Get(
                'integer_requires_strict_or_no_param'
            ));
        }
        throw new \RuntimeException(Messages::Instance()->Get(
            'field_must_be_an_integer',
            $field
        ));
    }
}
