<?php declare(strict_types=1);
/**
 * DatetimeRule.php
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
 * Validates whether a given field matches a specified datetime format.
 */
class DatetimeRule extends Rule
{
    /**
     * Validates that the field matches a specified datetime format.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   The expected datetime format.
     * @throws \RuntimeException
     *   If the value is not a string, the format is not a string, or the value
     *   does not match the format.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if (!$this->nativeFunctions->IsString($value)) {
            throw new \RuntimeException(Messages::Instance()->Get(
                'field_must_be_a_string',
                $field
            ));
        }
        if (!$this->nativeFunctions->IsString($param)) {
            throw new \RuntimeException(Messages::Instance()->Get(
                'datetime_requires_format'
            ));
        }
        if ($this->nativeFunctions->MatchDateTime($value, $param)) {
            return;
        }
        throw new \RuntimeException(Messages::Instance()->Get(
            'field_must_match_datetime_format',
            $field,
            $param
        ));
    }
}
