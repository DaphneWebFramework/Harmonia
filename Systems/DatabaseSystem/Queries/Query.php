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

namespace Harmonia\Systems\DatabaseSystem\Queries;

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
     * The values bound to placeholders.
     *
     * @var array<string, mixed>
     */
    private array $bindings = [];

    #region public -------------------------------------------------------------

    /**
     * Generates the SQL string representation of the query.
     *
     * @return string
     *   The SQL string.
     * @throws \InvalidArgumentException
     *   If a placeholder in the SQL string has no matching binding, or if a
     *   binding is provided that does not match any placeholder.
     */
    final public function ToSql(): string
    {
        $sql = $this->buildSql();
        $this->ensureBindingsMatchPlaceholders($sql);
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
            if (1 !== \preg_match('/^' . self::IDENTIFIER_PATTERN . '$/', $key)) {
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
     * Builds the SQL string.
     *
     * @return string
     *   The SQL string.
     */
    abstract protected function buildSql(): string;

    /**
     * Trims a string and ensures it is not empty.
     *
     * @param string $string
     *   The string to check.
     * @return string
     *   The given string, trimmed and validated as non-empty.
     * @throws \InvalidArgumentException
     *   If the string is empty or contains only whitespace.
     */
    protected function checkString(string $string): string
    {
        $string = \trim($string);
        if ($string === '') {
            throw new \InvalidArgumentException('String cannot be empty.');
        }
        return $string;
    }

    /**
     * Trims each string in a list and ensures none are empty.
     *
     * @param string ...$strings
     *   A list of strings to check.
     * @return string[]
     *   An array of strings, each trimmed and validated as non-empty.
     * @throws \InvalidArgumentException
     *   If no strings are provided or if any string is empty or contains only
     *   whitespace.
     */
    protected function checkStringList(string ...$strings): array
    {
        if (\count($strings) === 0) {
            throw new \InvalidArgumentException('String list cannot be empty.');
        }
        return \array_map([$this, 'checkString'], $strings);
    }

    /**
     * Formats a list of strings by checking them and joining them into a
     * comma-separated string.
     *
     * @param string ...$strings
     *   A list of strings to format.
     * @return string
     *   A comma-separated string of trimmed and validated non-empty strings.
     * @throws \InvalidArgumentException
     *   If no strings are provided or if any string is empty or contains only
     *   whitespace.
     */
    protected function formatStringList(string ...$strings): string
    {
        return \implode(', ', $this->checkStringList(...$strings));
    }

    /**
     * Safely formats an SQL identifier such as a table or column name.
     *
     * The identifier is enclosed in backticks, and any existing backtick
     * characters within it are escaped by doubling them.
     *
     * @param string $identifier
     *   The SQL identifier to be formatted.
     * @return string
     *   The identifier enclosed in backticks with internal backticks escaped.
     */
    protected function formatIdentifier(string $identifier): string
    {
        $identifier = \str_replace('`', '``', $identifier);
        return "`{$identifier}`";
    }

    #endregion protected

    #region private ------------------------------------------------------------

    /**
     * Ensures that all SQL placeholders have corresponding bindings and that
     * no extra bindings exist without a matching placeholder.
     *
     * @param string $sql
     *   The SQL query containing named placeholders (e.g., `:id`, `:name`).
     * @throws \RuntimeException
     *   If an error occurs while extracting placeholders from the SQL query.
     * @throws \InvalidArgumentException
     *   If there are missing bindings for placeholders or if there are
     *   bindings without matching placeholders.
     */
    private function ensureBindingsMatchPlaceholders(string $sql): void
    {
        // Extract placeholders from the SQL string
        if (false === \preg_match_all('/:' . self::IDENTIFIER_PATTERN . '/',
            $sql, $matches))
        {
            throw new \RuntimeException('Failed to match placeholders in SQL.');
        }

        // Remove the colon prefix from placeholders, e.g. ':id' -> 'id'
        $placeholders = \array_map(function($placeholder) {
            return \substr($placeholder, 1);
        }, $matches[0]);

        // If there are no placeholders or bindings, there is nothing to validate
        if (empty($placeholders) && empty($this->bindings)) {
            return;
        }

        // Obtain the keys of the bindings array,
        // e.g. ['id' => 42, 'name' => 'John'] -> ['id', 'name']
        $bindingKeys = \array_keys($this->bindings);

        // Compare the two arrays and throw an exception if they differ
        if ($diff = \array_diff($placeholders, $bindingKeys)) {
            throw new \InvalidArgumentException(
                'Missing bindings: ' . \implode(', ', $diff));
        }
        if ($diff = \array_diff($bindingKeys, $placeholders)) {
            throw new \InvalidArgumentException(
                'Missing placeholders: ' . \implode(', ', $diff));
        }
    }

    #endregion private
}
