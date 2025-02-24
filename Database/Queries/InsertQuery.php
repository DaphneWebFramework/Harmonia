<?php declare(strict_types=1);
/**
 * InsertQuery.php
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
 * Class for building SQL queries to insert data into a table.
 *
 * #### Example
 *
 * Inserting a new user with specified columns and values:
 *
 * ```php
 * $query = (new InsertQuery)
 *     ->Into('users')
 *     ->Columns('id', 'name', 'email', 'status', 'createdAt')
 *     ->Values(':id', ':name', ':email', ':status', ':createdAt')
 *     ->Bind([
 *         'id'        => 101,
 *         'name'      => 'John Doe',
 *         'email'     => 'john.doe@example.com',
 *         'status'    => 'active',
 *         'createdAt' => '2025-02-23 15:30:00'
 *     ]);
 * ```
 *
 * **Generated SQL:**
 * ```sql
 * INSERT INTO users (id, name, email, status, createdAt)
 * VALUES (:id, :name, :email, :status, :createdAt)
 * ```
 */
class InsertQuery extends Query
{
    private ?string $table = null;
    private ?string $columns = null;
    private ?string $values = null;

    #region public -------------------------------------------------------------

    /**
     * Defines the table where data will be inserted.
     *
     * @param string $table
     *   The name of the table where data will be inserted.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the table name is empty or contains only whitespace.
     */
    public function Into(string $table): self
    {
        $this->table = $this->formatString($table);
        return $this;
    }

    /**
     * Specifies the columns into which data will be inserted.
     *
     * @param string ...$columns
     *   One or more column names where data will be inserted.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If no columns are provided or if any column is empty or contains only
     *   whitespace.
     */
    public function Columns(string ...$columns): self
    {
        $this->columns = $this->formatStringList(...$columns);
        return $this;
    }

    /**
     * Specifies the values to be inserted into the table.
     *
     * @param string ...$values
     *   One or more values to insert. These should be provided as strings or
     *   placeholders (e.g., `:id`, `:name`).
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If no values are provided or if any value is empty or contains only
     *   whitespace.
     */
    public function Values(string ...$values): self
    {
        $this->values = $this->formatStringList(...$values);
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
     *   If the table name or values are not provided.
     */
    protected function buildSql(): string
    {
        if ($this->table === null) {
            throw new \RuntimeException(
                'Table name must be provided.');
        }
        if ($this->values === null) {
            throw new \RuntimeException(
                'Values must be provided.');
        }
        $clauses = [
            "INSERT INTO {$this->table}"
        ];
        if ($this->columns !== null) {
            $clauses[] = "({$this->columns})";
        }
        $clauses[] = "VALUES ({$this->values})";
        return \implode(' ', $clauses);
    }

    #endregion protected
}
