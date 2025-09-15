<?php declare(strict_types=1);
/**
 * StringRule.php
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
 * Validates whether a given field is a string.
 */
class StringRule extends Rule
{
    /**
     * Validates that the field contains a string.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   Unused in this rule.
     * @throws \RuntimeException
     *   If the value is not a string.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if ($this->nativeFunctions->IsString($value)) {
            return;
        }
        throw new \RuntimeException("Field '{$field}' must be a string.");
    }
}
