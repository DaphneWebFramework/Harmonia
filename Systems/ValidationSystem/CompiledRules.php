<?php declare(strict_types=1);
/**
 * CompiledRules.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Systems\ValidationSystem;

use \Harmonia\Systems\ValidationSystem\MetaRules\StandardMetaRule;
use \Harmonia\Systems\ValidationSystem\MetaRules\CustomMetaRule;

/**
 * Responsible for compiling and storing meta rules used for data validation.
 *
 * It is utilized internally by the `Validator` class to optimize the validation
 * process.
 */
class CompiledRules
{
    /**
     * Holds the compiled meta rules, where each key represents a data field
     * and each value is an array of `IMetaRule` objects that specify the
     * validation rules for that field.
     *
     * @var array<string|int, IMetaRule[]>
     */
    private array $metaRulesCollection;

    /**
     * Constructs a new instance by compiling the provided rules into meta rules.
     *
     * @param array<string|int, string|\Closure|array<int, string|\Closure>> $userDefinedRules
     *   An associative array where each key represents a field, and each value
     *   is either a single rule (string or closure) or an array of rules.
     * @throws \RuntimeException
     *   If an issue occurs during rule parsing.
     */
    public function __construct(array $userDefinedRules)
    {
        $this->metaRulesCollection = [];
        foreach ($userDefinedRules as $field => $rules) {
            if (!\is_array($rules)) {
                $rules = [$rules];
            }
            $metaRules = [];
            foreach ($rules as $rule) {
                if ($rule instanceof \Closure) {
                    $metaRules[] = new CustomMetaRule($rule);
                } else {
                    [$name, $param] = RuleParser::Parse($rule);
                    $metaRules[] = new StandardMetaRule($name, $param);
                }
            }
            $this->metaRulesCollection[$field] = $metaRules;
        }
    }

    /**
     * Retrieves the compiled meta rules collection.
     *
     * @return array<string|int, IMetaRule[]>
     *   An associative array where each key represents a field, and each value
     *   is an array of `IMetaRule` objects that specify the validation rules
     *   for that field.
     */
    public function MetaRulesCollection(): array
    {
        return $this->metaRulesCollection;
    }
}
