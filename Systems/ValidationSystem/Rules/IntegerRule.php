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
 * Validates whether a given field contains an integer-like value.
 */
class IntegerRule extends Rule
{
    /**
     * Validates that the field contains an integer-like value.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   Unused in this rule.
     * @throws \RuntimeException
     *   If the value is not an integer or an integer-like string.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if ($this->nativeFunctions->IsIntegerLike($value)) {
            return;
        }
        throw new \RuntimeException(Messages::Instance()->Get(
            'field_must_be_an_integer',
            $field
        ));
    }
}
