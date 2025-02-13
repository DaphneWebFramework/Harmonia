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
 * Base class for SQL query builders.
 */
abstract class Query
{
    /**
     * The name of the table associated with the query.
     *
     * @var string
     */
    protected readonly string $tableName;

    /**
     * The named substitutions for parameterized queries.
     *
     * @var array<string, mixed>
     */
    protected array $substitutions;

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
        $this->substitutions = [];
    }

    /**
     * Generates the SQL string representation of the query.
     *
     * @return string
     *   The SQL query string.
     */
    abstract public function ToSql(): string;

    /**
     * Retrieves the parameter substitutions used in the query.
     *
     * @return array
     *   An associative array of substitutions.
     */
    public function Substitutions(): array
    {
        return $this->substitutions;
    }

    #endregion public

    #region protected ----------------------------------------------------------

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
        return \preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $string) === 1;
    }

    #endregion protected
}
