<?php declare(strict_types=1);
/**
 * StandardMetaRule.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Validation\MetaRules;

use \Harmonia\Validation\Messages;
use \Harmonia\Validation\RuleFactory;

/**
 * Represents a standard validation rule with an optional parameter.
 */
class StandardMetaRule implements IMetaRule
{
    /**
     * The name of the rule.
     *
     * @var string
     */
    private readonly string $name;

    /**
     * The parameter associated with the rule.
     *
     * @var mixed
     */
    private readonly mixed $param;

    /**
     * The custom message to override the default message.
     *
     * @var string|null
     */
    private readonly ?string $customMessage;

    /**
     * Constructs a new instance with the provided rule name and parameter.
     *
     * @param string $name
     *   The name of the rule.
     * @param mixed $param
     *   The parameter for the rule.
     * @param ?string $customMessage
     *   (Optional) A custom message to override the default message.
     */
    public function __construct(
        string $name,
        mixed $param,
        ?string $customMessage = null
    ) {
        $this->name = $name;
        $this->param = $param;
        $this->customMessage = $customMessage;
    }

    /**
     * Retrieves the name of the rule.
     *
     * @return string
     *   The name of the rule.
     */
    public function GetName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the parameter of the rule.
     *
     * @return mixed
     *   The parameter of the rule, or `null` if no parameter exists.
     */
    public function GetParam(): mixed
    {
        return $this->param;
    }

    /**
     * Validates a given field against the rule.
     *
     * @param string|int $field
     *   The name or index of the field to validate.
     * @param mixed $value
     *   The value of the field to validate.
     * @throws \RuntimeException
     *   If the rule does not exist or validation fails.
     */
    public function Validate(string|int $field, mixed $value): void
    {
        $ruleObject = RuleFactory::Create($this->name);
        if ($ruleObject === null) {
            throw new \RuntimeException(Messages::Instance()->Get(
                'unknown_rule',
                $this->name
            ));
        }
        try {
            $ruleObject->Validate($field, $value, $this->param);
        } catch (\RuntimeException $e) {
            if ($this->customMessage !== null) {
                throw new \RuntimeException($this->customMessage);
            }
            throw $e;
        }
    }
}
