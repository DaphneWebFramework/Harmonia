<?php declare(strict_types=1);
/**
 * Validator.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem;

use \Harmonia\Http\StatusCode;
use \Harmonia\Systems\ValidationSystem\Requirements\FieldRequirementConstraints;
use \Harmonia\Systems\ValidationSystem\Requirements\RequiredRuleException;
use \Harmonia\Systems\ValidationSystem\Requirements\RequiredWithoutRuleException;
use \Harmonia\Systems\ValidationSystem\Requirements\RequirementEngine;

/**
 * Validates data against a predefined set of validation rules.
 */
class Validator
{
    /**
     * The name of the rule that indicates a field is nullable.
     */
    public const RULE_NULLABLE = 'nullable';

    /**
     * Holds the compiled validation rules.
     *
     * @var CompiledRules
     */
    private readonly CompiledRules $compiledRules;

    /**
     * Holds custom error messages for specific rules.
     *
     * @var ?array<string, string>
     */
    private readonly ?array $customMessages;

    /**
     * Constructs a new instance with a set of user-defined validation rules.
     *
     * #### Examples
     *
     * ```php
     * $validator = new Validator([
     *     'id' => ['required', 'integer', 'min:1'],
     *     'token' => 'regex:/^[a-f0-9]{64}$/',
     *     'countryCode' => function($value) {
     *         return \in_array($value, ['US', 'CA', 'MX'], true);
     *     }
     * ]);
     * ```
     * ```php
     * $validator = new Validator([
     *     'username' => ['required', 'string', 'minLength:3'],
     *     'email' => ['required', 'email'],
     *     'rememberMe' => ['required', function($value) {
     *         return 'on' === $value || 'off' === $value;
     *     }]
     * ]);
     * ```
     * ```php
     * $validator = new Validator(
     *     [
     *         'coordinates.latitude' => ['numeric', 'min:-90', 'max:90'],
     *         'coordinates.longitude' => ['numeric', 'min:-180', 'max:180']
     *     ],
     *     [
     *         'coordinates.latitude.min' => 'Latitude must be at least -90 degrees.',
     *         'coordinates.latitude.max' => 'Latitude cannot exceed 90 degrees.',
     *         'coordinates.longitude.min' => 'Longitude must be at least -180 degrees.',
     *         'coordinates.longitude.max' => 'Longitude cannot exceed 180 degrees.'
     *     ]
     * );
     * ```
     *
     * @param array<string|int, string|\Closure|array<int, string|\Closure>> $userDefinedRules
     *   An associative array where each key represents a field, and each value
     *   is either a single rule (string or closure) or an array of rules.
     * @param ?array<string, string> $customMessages
     *   (Optional) An associative array, mapping 'field.rule' keys to custom
     *   error messages.
     * @throws \RuntimeException
     *   If an error occurs while compiling the rules.
     */
    public function __construct(
        array $userDefinedRules,
        ?array $customMessages = null
    ) {
        $this->compiledRules = new CompiledRules($userDefinedRules);
        $this->customMessages = $customMessages;
    }

    /**
     * Validates the provided data against the compiled rules.
     *
     * @param array|object $data
     *   The data to validate, provided as an array or an object. If the data is
     *   an instance of `CArray`, it is converted to an array.
     * @return DataAccessor
     *   Returns a data accessor object that provides access to the validated
     *   data fields.
     * @throws \InvalidArgumentException
     *   If a validation rule is incorrectly defined.
     * @throws \RuntimeException
     *   If the validation fails. These exceptions are thrown with the code set
     *   to 400 (Bad Request).
     */
    public function Validate(array|object $data): DataAccessor
    {
        $dataAccessor = new DataAccessor($data);
        foreach ($this->compiledRules->MetaRulesCollection() as $field => $metaRules) {
            $this->validateField($field, $metaRules, $dataAccessor);
        }
        return $dataAccessor;
    }

    #region private ------------------------------------------------------------

    /**
     * Validates a single field against its associated rules.
     *
     * This method first applies preprocessing by handling requirement rules
     * like `required` and `requiredWithout`. Once these constraints are
     * resolved, it filters them out and applies the remaining validation rules.
     *
     * @param string|int $field
     *   The name or index of the field to validate.
     * @param IMetaRule[] $metaRules
     *   The validation rules for the field.
     * @param DataAccessor $dataAccessor
     *   Provides access to the data fields.
     * @throws \InvalidArgumentException
     *   If a validation rule is incorrectly defined.
     * @throws \RuntimeException
     *   If the validation fails. These exceptions are thrown with the code set
     *   to 400 (Bad Request).
     */
    private function validateField(
        string|int $field,
        array $metaRules,
        DataAccessor $dataAccessor
    ): void
    {
        // 1
        $requirementEngine = new RequirementEngine($field, $metaRules, $dataAccessor);
        try {
            $requirementEngine->Validate();
        } catch (RequiredRuleException $e) {
            $this->rethrow($field, FieldRequirementConstraints::RULE_REQUIRED, $e);
        } catch (RequiredWithoutRuleException $e) {
            $this->rethrow($field, FieldRequirementConstraints::RULE_REQUIRED_WITHOUT, $e);
        }
        if ($requirementEngine->ShouldSkipFurtherValidation()) {
            return;
        }
        $metaRules = $requirementEngine->FilterOutRequirementRules($metaRules);
        // 2
        $value = $dataAccessor->GetField($field);
        // 3
        if ($this->shouldSkipFurtherValidationDueToNullable($metaRules, $value)) {
            return;
        }
        $metaRules = $this->filterOutNullableRules($metaRules);
        // 4
        foreach ($metaRules as $metaRule) {
            try {
                $metaRule->Validate($field, $value);
            } catch (\RuntimeException $e) {
                $this->rethrow($field, $metaRule->GetName(), $e);
            }
        }
    }

    /**
     * Determines whether further validation should be skipped due to the
     * presence of a `nullable` rule and the value is `null`.
     *
     * @param IMetaRule[] $metaRules
     *   The list of rules to inspect for `nullable`.
     * @param mixed $value
     *   The value of the field to check.
     * @return bool
     *   Returns `true` if a `nullable` rule is defined and the value is `null`.
     *   Otherwise, returns `false`.
     */
    private function shouldSkipFurtherValidationDueToNullable(
        array $metaRules,
        mixed $value
    ): bool
    {
        foreach ($metaRules as $rule) {
            if ($rule->GetName() === self::RULE_NULLABLE) {
                return $value === null;
            }
        }
        return false;
    }

    /**
     * Filters out all `nullable` rules from the rule list.
     *
     * This step is essential to avoid "Unknown rule" errors during rule
     * validation, since `nullable` is not implemented as an executable
     * validation rule.
     *
     * @param IMetaRule[] $metaRules
     *   The list of rules to clean.
     * @return IMetaRule[]
     *   The list of rules with all `nullable` entries removed.
     */
    private function filterOutNullableRules(array $metaRules): array
    {
        return \array_filter($metaRules, function($metaRule) {
            return $metaRule->GetName() !== self::RULE_NULLABLE;
        });
    }

    /**
     * Rethrows a validation exception, optionally replacing its message with a
     * custom one.
     *
     * This method ensures the final thrown exception has the code set to 400
     * (Bad Request).
     *
     * @param string|int $field
     *   The name or index of the field being validated.
     * @param string $rule
     *   The name of the validation rule. Comparison is case-insensitive.
     * @param \RuntimeException $e
     *   The original exception to rethrow if no custom message is defined.
     * @return never
     *   This method always throws and never returns.
     */
    private function rethrow(
        string|int $field,
        string $rule,
        \RuntimeException $e
    ): never
    {
        $message = $this->customMessage($field, $rule);
        if ($message === null) {
            $message = $e->getMessage();
        }
        throw new \RuntimeException(
            $message,
            StatusCode::BadRequest->value,
            $e
        );
    }

    /**
     * Retrieves a custom error message for a specific field and rule.
     *
     * @param string|int $field
     *   The name or index of the field being validated.
     * @param string $rule
     *   The name of the validation rule. Comparison is case-insensitive.
     * @return ?string
     *   Returns the custom error message if defined, or `null` otherwise.
     */
    private function customMessage(string|int $field, string $rule): ?string
    {
        if ($this->customMessages === null) {
            return null;
        }
        foreach ($this->customMessages as $key => $message) {
            $lastDot = \strrpos($key, '.');
            if ($lastDot === false) {
                continue;
            }
            if ((string)$field !== \substr($key, 0, $lastDot)) {
                continue;
            }
            if ($rule !== \strtolower(\substr($key, $lastDot + 1))) {
                continue;
            }
            return $message;
        }
        return null;
    }

    #endregion private
}
