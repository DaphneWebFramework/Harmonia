<?php declare(strict_types=1);
/**
 * IMetaRule.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem\MetaRules;

/**
 * Interface for meta rule classes.
 */
interface IMetaRule
{
    /**
     * Retrieves the name of the rule.
     *
     * @return string
     *   The name of the rule.
     */
    public function GetName(): string;

    /**
     * Retrieves the parameter of the rule.
     *
     * @return mixed
     *   The parameter of the rule, or `null` if no parameter exists.
     */
    public function GetParam(): mixed;

    /**
     * Validates a given field against the rule.
     *
     * @param string|int $field
     *   The name or index of the field to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @throws \InvalidArgumentException
     *   If the rule name is invalid.
     * @throws \RuntimeException
     *   If the validation fails.
     */
    public function Validate(string|int $field, mixed $value): void;
}
