<?php declare(strict_types=1);
/**
 * DeleteQuery.php
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
 * Class for building SQL queries to delete data from a table.
 *
 * #### Example
 *
 * Deleting a user by ID:
 *
 * ```php
 * $query = (new DeleteQuery)
 *     ->Table('users')
 *     ->Where('id = :id')
 *     ->Bind(['id' => 101]);
 * ```
 *
 * **Generated SQL:**
 * ```sql
 * DELETE FROM users WHERE id = :id
 * ```
 */
class DeleteQuery extends Query
{
    private ?string $table = null;
    private ?string $condition = null;

    #region public -------------------------------------------------------------

    /**
     * Defines the table from which data will be deleted.
     *
     * @param string $table
     *   The name of the table from which rows will be deleted.
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
     * Filters the rows that should be deleted.
     *
     * @param string $condition
     *   A condition that specifies which rows should be deleted.
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
     *   If the table name or condition is not provided.
     */
    protected function buildSql(): string
    {
        if ($this->table === null) {
            throw new \RuntimeException(
                'Table name must be provided.');
        }
        if ($this->condition === null) {
            throw new \RuntimeException(
                'Condition must be provided.');
        }
        return "DELETE FROM {$this->table} WHERE {$this->condition}";
    }

    #endregion protected
}
