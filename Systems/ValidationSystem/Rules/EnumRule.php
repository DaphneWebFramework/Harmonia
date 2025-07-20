<?php declare(strict_types=1);
/**
 * EnumRule.php
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
 * Validates whether a given field's value corresponds to a valid case of the
 * specified enum class.
 *
 * Supports both backed enums and pure enums.
 */
class EnumRule extends Rule
{
    /**
     * Validates that the field's value is a valid enum case of the specified
     * class.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   The fully qualified name of the enum class.
     * @throws \RuntimeException
     *   If the parameter is not a valid class name, or the value is not valid
     *   for the enum.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if (!$this->nativeFunctions->IsString($param)) {
            throw new \RuntimeException(Messages::Instance()->Get(
                'enum_requires_class_name'
            ));
        }
        if ($this->nativeFunctions->IsEnumValue($value, $param)) {
            return;
        }
        throw new \RuntimeException(Messages::Instance()->Get(
            'field_must_be_a_valid_enum_value',
            $field,
            $param
        ));
    }
}
