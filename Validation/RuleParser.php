<?php declare(strict_types=1);
/**
 * RuleParser.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Validation;

/**
 * Parses validation rules into components (name and parameter).
 */
abstract class RuleParser
{
    /**
     * Parses a rule string into its name and optional parameter.
     *
     * @param string $rule
     *   The rule string (e.g., `'min:10'` or `'required'`).
     * @return array<int, ?string>
     *   A tuple where the first element is the rule name and the second is the
     *   optional rule parameter (`null` if absent).
     * @throws \InvalidArgumentException
     *   If the rule is empty or contains only whitespace.
     */
    public static function Parse(string $rule): array
    {
        $rule = \trim($rule);
        if ($rule === '') {
            throw new \InvalidArgumentException(Messages::Instance()->Get(
                'rule_must_be_non_empty'
            ));
        }
        $pos = \strpos($rule, ':');
        if ($pos !== false) {
            $ruleName = \rtrim(\substr($rule, 0, $pos));
            $ruleParam = \ltrim(\substr($rule, $pos + 1));
            if ($ruleParam === '') {
                $ruleParam = null;
            }
        } else {
            $ruleName = $rule;
            $ruleParam = null;
        }
        return [$ruleName, $ruleParam];
    }
}
