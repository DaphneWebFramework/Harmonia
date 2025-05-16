<?php declare(strict_types=1);
/**
 * FieldRequirementConstraints.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem\Requirements;

use \Harmonia\Systems\ValidationSystem\Messages;
use \Harmonia\Systems\ValidationSystem\MetaRules\IMetaRule;

/**
 * Encapsulates the constraints that determine whether a field is required.
 *
 * This class processes `required` and `requiredWithout` rules from validation
 * metadata, organizing them into structured constraints that can be easily
 * checked during validation.
 */
class FieldRequirementConstraints
{
    /**
     * The name of the requirement rule that indicates a field is required.
     */
    public const RULE_REQUIRED = 'required';

    /**
     * The name of the requirement rule that indicates a field is required if
     * another specified field is absent. Conversely, if the other field exists,
     * this field should not be present.
     */
    public const RULE_REQUIRED_WITHOUT = 'requiredwithout';

    /**
     * Indicates whether the field is explicitly required.
     *
     * @var bool
     */
    private readonly bool $isRequired;

    /**
     * List of fields that determine if this field is required.
     *
     * - If any of these fields exist in the dataset, this field is no longer
     *   required.
     * - If none exist, this field must be present.
     *
     * This supports validation cases where multiple fields influence each
     * other's required status (e.g., `email` or `phoneNumber` can be required,
     * but the presence of one makes the other optional).
     *
     * @var string[]
     */
    private readonly array $requiredWithoutFields;

    /**
     * Constructs a new instance with the given requirement constraints.
     *
     * @param bool $isRequired
     *   Whether the field is explicitly required.
     * @param string[] $requiredWithoutFields
     *   List of fields that can override the required status of this field.
     */
    private function __construct(bool $isRequired, array $requiredWithoutFields)
    {
        $this->isRequired = $isRequired;
        $this->requiredWithoutFields = $requiredWithoutFields;
    }

    #region public -------------------------------------------------------------

    /**
     * Extracts `required` and `requiredWithout` constraints from processed
     * validation rules.
     *
     * @param IMetaRule[] $metaRules
     *   An array of parsed meta rule objects (`IMetaRule`) representing field
     *   constraints.
     * @return self
     *   A `FieldRequirementConstraints` instance encapsulating extracted
     *   constraints.
     * @throws \InvalidArgumentException
     *   If a `requiredWithout` rule is defined without a field name.
     */
    public static function FromMetaRules(array $metaRules): self
    {
        $isRequired = false;
        $requiredWithoutFields = [];
        foreach ($metaRules as $metaRule) {
            switch ($metaRule->GetName()) {
            case self::RULE_REQUIRED:
                $isRequired = true;
                break;
            case self::RULE_REQUIRED_WITHOUT:
                $param = $metaRule->GetParam();
                if ($param === null) {
                    throw new \InvalidArgumentException(Messages::Instance()->Get(
                        'requiredwithout_requires_field_name'
                    ));
                }
                $requiredWithoutFields[] = $param;
                break;
            }
        }
        return new self($isRequired, $requiredWithoutFields);
    }

    /**
     * Checks if the field is explicitly required.
     *
     * @return bool
     *   Returns `true` if the field must be present, otherwise `false`.
     */
    public function IsRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * Checks whether the field has any mutually exclusive constraints.
     *
     * @return bool
     *   Returns `true` if there are `requiredWithout` fields, otherwise `false`.
     */
    public function HasRequiredWithoutFields(): bool
    {
        return !empty($this->requiredWithoutFields);
    }

    /**
     * Retrieves the list of fields that affect the required status of this field.
     *
     * These fields can make the current field optional if any of them exist.
     *
     * @return string[]
     *   An array of mutually exclusive field names.
     */
    public function RequiredWithoutFields(): array
    {
        return $this->requiredWithoutFields;
    }

    /**
     * Formats the list of mutually exclusive fields for error messages.
     *
     * @return string
     *   A formatted string containing mutually exclusive fields.
     */
    public function FormatRequiredWithoutList(): string
    {
        if (count($this->requiredWithoutFields) > 1) {
            $oneOf = \implode("', '", $this->requiredWithoutFields);
            return "one of '{$oneOf}'";
        } else {
            return "'{$this->requiredWithoutFields[0]}'";
        }
    }

    #endregion public
}
