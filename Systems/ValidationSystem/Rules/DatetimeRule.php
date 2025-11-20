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

/**
 * Validates whether a given field contains a datetime string.
 *
 * If a format string is provided as the optional parameter, the value must
 * match that format exactly (per `DateTime::createFromFormat`). If no format
 * is provided, any string that PHP can parse into a datetime is accepted.
 */
class DatetimeRule extends Rule
{
    /**
     * Validates that the field contains a datetime string.
     *
     * @param string|int $field
     *   The field name or index to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @param mixed $param
     *   Optional format string. When provided, the value must match it exactly.
     *   When omitted, any PHP‑parsable datetime string is accepted.
     * @throws \RuntimeException
     *   If a format is provided and the value does not match it exactly; or if
     *   no format is provided and the value is not a valid datetime string; or
     *   if an invalid parameter is given.
     */
    public function Validate(string|int $field, mixed $value, mixed $param): void
    {
        if ($this->nativeFunctions->IsString($param)) {
            if ($this->nativeFunctions->MatchDateTime($value, $param)) {
                return;
            }
            throw new \RuntimeException(
                "Field '{$field}' must match the exact datetime format: {$param}");
        } elseif ($param === null) {
            if ($this->nativeFunctions->IsDateTime($value)) {
                return;
            }
            throw new \RuntimeException(
                "Field '{$field}' must be a valid datetime string.");
        } else {
            throw new \RuntimeException(
                "Rule 'datetime' must be used with either a format string or no parameter.");
        }
    }
}
