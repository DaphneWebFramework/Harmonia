<?php declare(strict_types=1);
/**
 * Rule.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Validation\Rules;

use \Harmonia\Validation\NativeFunctions;

/**
 * Abstract base class for all validation rules.
 */
abstract class Rule
{
    /**
     * Provides access to validation functions.
     *
     * @var NativeFunctions
     */
    protected readonly NativeFunctions $nativeFunctions;

    /**
     * Constructs a new instance with a reference to the native functions utility.
     *
     * @param NativeFunctions $nativeFunctions
     *   The shared instance providing low-level validation utilities.
     */
    public function __construct(NativeFunctions $nativeFunctions)
    {
        $this->nativeFunctions = $nativeFunctions;
    }

    /**
     * Validates a field against the rule's logic.
     *
     * @param string|int $field
     *   The field name or index.
     * @param mixed $value
     *   The field value to validate.
     * @param mixed $param
     *   The optional parameter for validation.
     * @throws \RuntimeException
     *   If validation fails.
     */
    abstract public function Validate(string|int $field, mixed $value, mixed $param): void;
}
