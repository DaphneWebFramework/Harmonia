<?php declare(strict_types=1);
/**
 * MinlengthRule.php
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
 * Validates whether a given field satisfies a specified minimum length.
 */
class MinlengthRule extends Rule
{
    /**
     * Validates that the field contains a string meeting the specified minimum
     * length.
     *
     * The specified minimum length is inclusive, meaning a string with exactly
     * `$param` characters is valid.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   The minimum allowed length, inclusive.
     * @throws \RuntimeException
     *   If the value is not a string, the specified length is not an
     *   integer-like value, or the value is shorter than the specified length.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if (!$this->nativeFunctions->IsString($value)) {
            throw new \RuntimeException(Messages::Instance()->Get(
                'field_must_be_a_string',
                $field
            ));
        }
        if (!$this->nativeFunctions->IsIntegerLike($param)) {
            throw new \RuntimeException(Messages::Instance()->Get(
                'minlength_requires_integer'
            ));
        }
        if (\strlen($value) >= (int)$param) {
            return;
        }
        throw new \RuntimeException(Messages::Instance()->Get(
            'field_min_length',
            $field,
            $param
        ));
    }
}
