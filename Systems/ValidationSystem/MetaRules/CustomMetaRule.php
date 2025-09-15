<?php declare(strict_types=1);
/**
 * CustomMetaRule.php
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
 * Provides functionality for user-defined validation rules represented as
 * closures.
 */
class CustomMetaRule implements IMetaRule
{
    /**
     * The closure function used for custom validation.
     *
     * @var \Closure
     */
    private readonly \Closure $closure;

    /**
     * Constructs a new instance with the provided closure.
     *
     * @param \Closure $closure
     *   The closure function to be used for custom validation. The closure must
     *   accept a single parameter representing the field value and return
     *   `false` if the validation fails. Any other return value is considered a
     *   successful validation.
     */
    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * Retrieves the name of the rule.
     *
     * @return string
     *   Always returns an empty string for custom rules.
     */
    public function GetName(): string
    {
        return '';
    }

    /**
     * Retrieves the parameter of the rule.
     *
     * @return mixed
     *   Always returns `null` since custom rules do not have parameters.
     */
    public function GetParam(): mixed
    {
        return null;
    }

    /**
     * Validates a given field against the rule.
     *
     * @param string|int $field
     *   The name or index of the field to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @throws \RuntimeException
     *   If the user-defined closure returns `false`.
     */
    public function Validate(string|int $field, mixed $value): void
    {
        if (false !== ($this->closure)($value)) {
            return;
        }
        throw new \RuntimeException("Field '{$field}' failed custom validation.");
    }
}
