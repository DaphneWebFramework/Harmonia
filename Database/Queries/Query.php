<?php declare(strict_types=1);
/**
 * Query.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

namespace Harmonia\Database\Queries;

/**
 * Base class for SQL builders.
 */
abstract class Query
{
    /**
     * The pattern defining a valid SQL identifier.
     *
     * @var string
     */
    public const IDENTIFIER_PATTERN = '[a-zA-Z_][a-zA-Z0-9_]*';

    /**
     * The name of the table associated with the query.
     *
     * @var string
     */
    protected readonly string $tableName;

    /**
     * The values bound to placeholders.
     *
     * @var array<string, mixed>
     */
    private array $bindings;

    #region public -------------------------------------------------------------

    /**
     * Creates a new instance.
     *
     * @param string $tableName
     *   The name of the table associated with the query.
     */
    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
        $this->bindings = [];
    }

    /**
     * Generates the SQL string representation of the query.
     *
     * @return string
     *   The SQL string.
     * @throws \InvalidArgumentException
     *   If a placeholder is missing a binding or vice versa.
     */
    final public function ToSql(): string
    {
        $sql = $this->buildSql();
        $this->validate($sql);
        return $sql;
    }

    /**
     * Retrieves the parameter bindings used in the query.
     *
     * @return array<string, mixed>
     *   An associative array of bindings. For example, `['id' => 42,
     *   'name' => 'John']`.
     */
    public function Bindings(): array
    {
        return $this->bindings;
    }

    /**
     * Binds values to named placeholders.
     *
     * @param array<string, mixed> $bindings
     *   An associative array where keys are placeholders (without `:` prefix)
     *   and values are their corresponding replacements.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If a binding key is invalid, or if a binding value is an array,
     *   a resource, or an object without a `__toString()` method.
     */
    public function Bind(array $bindings): self
    {
        foreach ($bindings as $key => $value) {
            if (!\preg_match('/^' . self::IDENTIFIER_PATTERN . '$/', $key)) {
                throw new \InvalidArgumentException("Invalid binding key: {$key}");
            }
            if (\is_array($value)) {
                throw new \InvalidArgumentException(
                    "Invalid binding value for '{$key}': Array not allowed.");
            }
            if (\is_resource($value)) {
                throw new \InvalidArgumentException(
                    "Invalid binding value for '{$key}': Resource not allowed.");
            }
            if (\is_object($value)) {
                if (!\method_exists($value, '__toString')) {
                    throw new \InvalidArgumentException(
                        "Invalid binding value for '{$key}': Object without __toString() not allowed.");
                }
                $bindings[$key] = (string)$value;
            }
        }
        $this->bindings = $bindings;
        return $this;
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /**
     * Method that must be implemented by subclasses to build SQL string.
     *
     * @return string
     *   The SQL string.
     */
    abstract protected function buildSql(): string;

    /**
     * Determines whether the given string is a valid SQL identifier.
     *
     * @param string $string
     *   The string to check.
     * @return bool
     *   Returns `true` if the string is a valid identifier, `false` otherwise.
     */
    protected function isIdentifier(string $string): bool
    {
        return 1 === \preg_match('/^' . self::IDENTIFIER_PATTERN . '$/', $string);
    }

    #endregion protected

    #region private ------------------------------------------------------------

    /**
     * Validates that all placeholders have corresponding bindings.
     *
     * @param string $sql
     *   The SQL string to check for placeholders.
     * @throws \InvalidArgumentException
     *   If a placeholder in the SQL string has no matching binding, or if a
     *   binding is provided that does not match any placeholder.
     */
    private function validate(string $sql): void
    {
        \preg_match_all('/:' . self::IDENTIFIER_PATTERN . '/', $sql, $matches);
        $placeholders = \array_map(function($placeholder) {
            return \substr($placeholder, 1);
        }, isset($matches[0]) ? $matches[0] : []);
        if (!empty($placeholders) || !empty($this->bindings)) {
            $bindingKeys = \array_keys($this->bindings);
            if ($diff = \array_diff($placeholders, $bindingKeys)) {
                throw new \InvalidArgumentException(
                    'Missing bindings: ' . \implode(', ', $diff));
            }
            if ($diff = \array_diff($bindingKeys, $placeholders)) {
                throw new \InvalidArgumentException(
                    'Missing placeholders: ' . \implode(', ', $diff));
            }
        }
    }

    #endregion private
}
