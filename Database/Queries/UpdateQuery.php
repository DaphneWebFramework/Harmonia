<?php declare(strict_types=1);
/**
 * UpdateQuery.php
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
 * Class for building SQL queries to update data in a table.
 *
 * #### Example
 *
 * Updating the email and status of a specific user:
 *
 * ```php
 * $query = (new UpdateQuery())
 *     ->Table('users')
 *     ->Columns('email', 'status')
 *     ->Values(':email', ':status')
 *     ->Where('id = :id')
 *     ->Bind([
 *         'email'  => 'new.email@example.com',
 *         'status' => 'active',
 *         'id'     => 101
 *     ]);
 * ```
 *
 * **Generated SQL:**
 * ```sql
 * UPDATE users
 * SET email = :email, status = :status
 * WHERE id = :id
 * ```
 */
class UpdateQuery extends Query
{
    private ?string $table = null;
    private ?array $columns = null;
    private ?array $values = null;
    private ?string $condition = null;

    #region public -------------------------------------------------------------

    /**
     * Defines the table where data will be updated.
     *
     * @param string $table
     *   The name of the table to update.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the table name is empty or contains only whitespace.
     */
    public function Table(string $table): self
    {
        $this->table = $this->checkString($table);
        return $this;
    }

    /**
     * Specifies the columns to update.
     *
     * @param string ...$columns
     *   One or more column names to be updated.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If no columns are provided or if any column is empty or contains only
     *   whitespace.
     */
    public function Columns(string ...$columns): self
    {
        $this->columns = $this->checkStringList(...$columns);
        return $this;
    }

    /**
     * Specifies the values for the update operation.
     *
     * @param string ...$values
     *   One or more values to be assigned to the columns. These should be
     *   provided as strings or placeholders (e.g., `:email`, `:status`).
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If no values are provided, if any value is empty or contains only
     *   whitespace.
     */
    public function Values(string ...$values): self
    {
        $this->values = $this->checkStringList(...$values);
        return $this;
    }

    /**
     * Filters the rows that should be updated.
     *
     * @param string $condition
     *   A condition that specifies which rows should be updated.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the condition is empty or contains only whitespace.
     */
    public function Where(string $condition): self
    {
        $this->condition = $this->checkString($condition);
        return $this;
    }

    #endregion public

    #region protected ----------------------------------------------------------

    /**
     * Builds the SQL string.
     *
     * @return string
     *   The SQL string.
     * @throws \RuntimeException
     *   If the table name, columns, or values are not provided, or if the number
     *   of columns does not match the number of values.
     */
    protected function buildSql(): string
    {
        if ($this->table === null) {
            throw new \RuntimeException(
                'Table name must be provided.');
        }
        if ($this->columns === null) {
            throw new \RuntimeException(
                'Columns must be provided.');
        }
        if ($this->values === null) {
            throw new \RuntimeException(
                'Values must be provided.');
        }
        if (\count($this->columns) !== \count($this->values)) {
            throw new \RuntimeException(
                'Number of columns must match number of values.');
        }
        $clauses = [
            "UPDATE {$this->table}",
            "SET " . \implode(', ', \array_map(
                function($column, $value) { return "{$column} = {$value}"; },
                $this->columns,
                $this->values
            ))
        ];
        if ($this->condition !== null) {
            $clauses[] = "WHERE {$this->condition}";
        }
        return \implode(' ', $clauses);
    }

    #endregion protected
}
