<?php declare(strict_types=1);
/**
 * RuleFactory.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Validation;

use \Harmonia\Core\CArray;
use \Harmonia\Validation\Rules\Rule;

/**
 * Factory class responsible for creating and managing rule objects.
 *
 * Implements the Flyweight pattern to optimize memory usage by sharing
 * identical rule objects instead of creating new ones for each validation
 * request.
 */
abstract class RuleFactory
{
    /**
     * A collection of rule objects indexed by their rule names.
     *
     * @var ?CArray
     */
    private static ?CArray $ruleObjects = null;

    /**
     * A shared instance of `NativeFunctions`, injected into each created rule.
     *
     * @var ?NativeFunctions
     */
    private static ?NativeFunctions $nativeFunctions = null;

    /**
     * Creates or retrieves a rule object based on the given rule name.
     *
     * @param string $ruleName
     *   The name of the desired rule.
     * @return ?Rule
     *   Returns the rule object associated with the given name, or `null` if
     *   the rule doesn't exist.
     * @throws \InvalidArgumentException
     *   If the rule name is empty, contains leading or trailing spaces, or
     *   contains uppercase letters.
     */
    public static function Create(string $ruleName): ?Rule
    {
        if ($ruleName === ''
         || $ruleName !== \trim($ruleName)
         || $ruleName !== \strtolower($ruleName))
        {
            throw new \InvalidArgumentException(
                'Rule name must be non-empty, trimmed, and lowercased.');
        }
        if (self::$ruleObjects === null) {
            self::$ruleObjects = new CArray();
        }
        // Normalize rule names to match class names, which start with an
        // uppercase letter, followed by lowercase letters, and end with "Rule".
        $ruleName = \ucfirst($ruleName);
        if (!self::$ruleObjects->Has($ruleName)) {
            $ruleClassName = "\\Harmonia\\Validation\\Rules\\{$ruleName}Rule";
            if (!\class_exists($ruleClassName)) {
                return null;
            }
            if (self::$nativeFunctions === null) {
                self::$nativeFunctions = new NativeFunctions();
            }
            self::$ruleObjects->Set($ruleName, new $ruleClassName(self::$nativeFunctions));
        }
        return self::$ruleObjects->Get($ruleName);
    }
}
