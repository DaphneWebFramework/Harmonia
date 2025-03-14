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

namespace Harmonia\Validation;

use \Harmonia\Validation\Requirements\RequirementEngine;

/**
 * Validates data against a predefined set of validation rules.
 */
class Validator
{
    /**
     * Holds the compiled validation rules.
     *
     * @var CompiledRules
     */
    private readonly CompiledRules $compiledRules;

    /**
     * Constructs a new instance with a set of user-defined validation rules.
     *
     * #### Examples
     *
     * ```php
     * $validator = new Validator([
     *     'id' => ['required', 'integer', 'min:1'],
     *     'token' => 'regex:^[a-f0-9]{64}$',
     *     'countryCode' => function($value) {
     *         return \in_array($value, ['US', 'CA', 'MX']);
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
     * $validator = new Validator([
     *     'coordinates.latitude' => ['numeric', 'min:-90', 'max:90'],
     *     'coordinates.longitude' => ['numeric', 'min:-180', 'max:180']
     * ]);
     * ```
     *
     * @param array<string|int, string|\Closure|array<string|\Closure>> $userDefinedRules
     *   An associative array where each key represents a field, and each value
     *   is either a single rule (string or closure) or an array of rules.
     * @throws \RuntimeException
     *   If an error occurs while compiling the rules.
     */
    public function __construct(array $userDefinedRules)
    {
        $this->compiledRules = new CompiledRules($userDefinedRules);
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
     *   If a `requiredWithout` rule is defined without a field name, or if the
     *   field references itself as a `requiredWithout` field.
     * @throws \RuntimeException
     *   If the field fails any requirement rule. This includes cases where a
     *   required field is missing, the field and any mutually exclusive field
     *   are both present, or neither the field nor any mutually exclusive
     *   fields are present.
     * @throws \RuntimeException
     *   If the rule does not exist or validation fails.
     * @throws \RuntimeException
     *   If the user-defined closure returns `false`.
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
     * @param array<IMetaRule> $metaRules
     *   The validation rules for the field.
     * @param DataAccessor $dataAccessor
     *   Provides access to the data fields.
     * @throws \InvalidArgumentException
     *   If a `requiredWithout` rule is defined without a field name, or if the
     *   field references itself as a `requiredWithout` field.
     * @throws \RuntimeException
     *   If the field fails any requirement rule. This includes cases where a
     *   required field is missing, the field and any mutually exclusive field
     *   are both present, or neither the field nor any mutually exclusive
     *   fields are present.
     * @throws \RuntimeException
     *   If the rule does not exist or validation fails.
     * @throws \RuntimeException
     *   If the user-defined closure returns `false`.
     */
    private function validateField(
        string|int $field,
        array $metaRules,
        DataAccessor $dataAccessor
    ): void
    {
        $requirementEngine = new RequirementEngine($field, $metaRules, $dataAccessor);
        $requirementEngine->Validate();
        if ($requirementEngine->ShouldSkipFurtherValidation()) {
            return;
        }
        $metaRules = $requirementEngine->FilterOutRequirementRules($metaRules);
        $value = $dataAccessor->GetField($field);
        foreach ($metaRules as $metaRule) {
            $metaRule->Validate($field, $value);
        }
    }

    #endregion private
}
