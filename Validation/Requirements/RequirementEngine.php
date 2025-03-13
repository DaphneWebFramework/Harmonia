<?php declare(strict_types=1);
/**
 * RequirementEngine.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Validation\Requirements;

use \Harmonia\Validation\DataAccessor;
use \Harmonia\Validation\Messages;
use \Harmonia\Validation\MetaRules\IMetaRule;

/**
 * Processes requirement rules for a given field.
 */
class RequirementEngine
{
    /**
     * The field being validated.
     *
     * @var string|int
     */
    private string|int $field;

    /**
     * The requirement constraints for the field.
     *
     * This member provides a convenient way to quickly look up the requirement
     * rules that apply to the field being validated.
     *
     * @var FieldRequirementConstraints
     */
    private FieldRequirementConstraints $fieldRequirementConstraints;

    /**
     * Indicates whether the field exists in the dataset.
     *
     * @var bool
     */
    private bool $fieldExists;

    /**
     * Indicates whether any `requiredWithout` field exists in the dataset.
     *
     * @var bool
     */
    private bool $anyRequiredWithoutFieldExists;

    /**
     * Constructs a new instance by extracting constraints and checking field
     * presence.
     *
     * @param string|int $field
     *   The field name or index being validated.
     * @param IMetaRule[] $metaRules
     *   An array of meta rules associated with the field.
     * @param DataAccessor $dataAccessor
     *   Provides access to the dataset.
     * @throws \InvalidArgumentException
     *   If a `requiredWithout` rule is defined without a field name.
     */
    public function __construct(
        string|int $field,
        array $metaRules,
        DataAccessor $dataAccessor
    ) {
        $this->field = $field;
        $this->fieldRequirementConstraints =
            FieldRequirementConstraints::FromMetaRules($metaRules);
        $requiredWithoutFields =
            $this->fieldRequirementConstraints->RequiredWithoutFields();
        if (\in_array($field, $requiredWithoutFields, true)) {
            throw new \InvalidArgumentException(Messages::Instance()->Get(
                'requiredwithout_cannot_reference_itself'
            ));
        }
        $this->fieldExists = $dataAccessor->HasField($field);
        $this->anyRequiredWithoutFieldExists = false;
        foreach ($requiredWithoutFields as $otherField) {
            if ($dataAccessor->HasField($otherField)) {
                $this->anyRequiredWithoutFieldExists = true;
                break;
            }
        }
    }

    /**
     * Validates the field against requirement rules.
     *
     * @throws \RuntimeException
     *   If the field fails any requirement rule. This includes cases where a
     *   required field is missing, the field and any mutually exclusive field
     *   are both present, or neither the field nor any mutually exclusive
     *   fields are present.
     */
    public function Validate(): void
    {
        if ($this->fieldExists) {
            if ($this->anyRequiredWithoutFieldExists) {
                // Both the field and a mutually exclusive field are present.
                throw new \RuntimeException(Messages::Instance()->Get(
                    'only_one_of_fields_can_be_present',
                    $this->field,
                    $this->fieldRequirementConstraints->FormatRequiredWithoutList()
                ));
            }
        } else {
            if ($this->anyRequiredWithoutFieldExists) {
                if ($this->fieldRequirementConstraints->IsRequired()) {
                    // The field is required but missing, even though a mutually
                    // exclusive field is present.
                    throw new \RuntimeException(Messages::Instance()->Get(
                        'required_field_missing',
                        $this->field
                    ));
                }
            } elseif ($this->fieldRequirementConstraints->IsRequired()) {
                // The field is required but missing, and no mutually exclusive
                // fields are present.
                throw new \RuntimeException(Messages::Instance()->Get(
                    'required_field_missing',
                    $this->field
                ));
            } elseif ($this->fieldRequirementConstraints->HasRequiredWithoutFields()) {
                // Neither the field nor any of its mutually exclusive fields
                // are present.
                throw new \RuntimeException(Messages::Instance()->Get(
                    'either_field_or_other_must_be_present',
                    $this->field,
                    $this->fieldRequirementConstraints->FormatRequiredWithoutList()
                ));
            }
        }
    }

    /**
     * Determines whether further validation for the field should be skipped.
     *
     * @return false
     *   If the field is present, meaning it should undergo further validation.
     * @return true
     *   If the field is absent but a mutually exclusive field is present, or
     *   if the field is absent and not required.
     */
    public function ShouldSkipFurtherValidation(): bool
    {
        if ($this->fieldExists) {
            return false;
        }
        if ($this->anyRequiredWithoutFieldExists) {
            return true;
        }
        if (!$this->fieldRequirementConstraints->IsRequired()) {
            return true;
        }
        return false;
    }

    /**
     * Filters out requirement rules from a list of meta rules.
	 *
	 * This step is essential to avoid "Unknown rule" errors during subsequent
	 * validations in the validator.
     *
     * @param IMetaRule[] $metaRules
     *   An array of meta rule objects for the field.
     * @return IMetaRule[]
     *   Returns the meta rules excluding `required` and `requiredWithout` rules.
     */
    public function FilterOutRequirementRules(array $metaRules): array
    {
        $ruleNames = [
            FieldRequirementConstraints::RULE_REQUIRED,
            FieldRequirementConstraints::RULE_REQUIRED_WITHOUT
        ];
        return \array_filter($metaRules, function($metaRule) use($ruleNames) {
            return !\in_array($metaRule->GetName(), $ruleNames, true);
        });
    }
}
