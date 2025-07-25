<?php declare(strict_types=1);
/**
 * SelectQuery.php
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
 * Class for building SQL queries to retrieve data from a table.
 *
 * #### Example
 *
 * Fetching active users who registered after a specific date, sorted by their
 * last login:
 *
 * ```php
 * $query = (new SelectQuery)
 *     ->Table('users')
 *     ->Columns('name', 'email', 'COUNT(*) AS loginCount')
 *     ->Where('status = :status AND createdAt >= :startDate')
 *     ->OrderBy(
 *         'lastLogin DESC',
 *         'name'
 *     )
 *     ->Limit(20, 10)
 *     ->Bind([
 *         'status'    => 'active',
 *         'startDate' => '2025-01-01'
 *     ]);
 * ```
 *
 * **Generated SQL:**
 * ```sql
 * SELECT name, email, COUNT(*) AS loginCount FROM `users`
 * WHERE status = :status AND createdAt >= :startDate
 * ORDER BY lastLogin DESC, name
 * LIMIT 20 OFFSET 10
 * ```
 *
 * @todo Add support for GROUP BY, HAVING, DISTINCT, and JOIN clauses.
 */
class SelectQuery extends Query
{
    private ?string $table = null;
    private string $columns = '*';
    private ?string $condition = null;
    private ?string $orderBy = null;
    private ?string $limit = null;

    #region public -------------------------------------------------------------

    /**
     * Defines the table from which data will be selected.
     *
     * @param string $table
     *   The name of the table from which data will be retrieved.
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
     * Specifies which columns should be retrieved in the query.
     *
     * If this method is not called, the query defaults to selecting
     * all columns (`*`).
     *
     * @param string ...$columns
     *   One or more column names or expressions to retrieve.
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
     * Filters the query results based on a condition.
     *
     * @param string $condition
     *   A condition used to filter the query results.
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

    /**
     * Sorts the query results based on one or more columns.
     *
     * @param string ...$columns
     *   One or more column names, each optionally followed by a sorting
     *   direction (`ASC` or `DESC`).
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If no columns are provided or if any column is empty or contains only
     *   whitespace.
     */
    public function OrderBy(string ...$columns): self
    {
        $this->orderBy = $this->formatStringList(...$columns);
        return $this;
    }

    /**
     * Restricts the number of rows returned by the query.
     *
     * @param int $limit
     *   The maximum number of rows to return.
     * @param ?int $offset
     *   (Optional) The number of rows to skip before returning results. If
     *   `null`, no offset is applied.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the limit or offset is negative.
     */
    public function Limit(int $limit, ?int $offset = null): self
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException(
                'Limit must be a non-negative integer.');
        }
        if ($offset !== null && $offset < 0) {
            throw new \InvalidArgumentException(
                'Offset must be a non-negative integer.');
        }
        if ($offset !== null) {
            $this->limit = "{$limit} OFFSET {$offset}";
        } else {
            $this->limit = (string)$limit;
        }
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
     *   If the table name is not provided.
     */
    protected function buildSql(): string
    {
        if ($this->table === null) {
            throw new \RuntimeException(
                'Table name must be provided.');
        }
        $clauses = [
            "SELECT {$this->columns}",
            "FROM {$this->formatIdentifier($this->table)}"
        ];
        if ($this->condition !== null) {
            $clauses[] = "WHERE {$this->condition}";
        }
        if ($this->orderBy !== null) {
            $clauses[] = "ORDER BY {$this->orderBy}";
        }
        if ($this->limit !== null) {
            $clauses[] = "LIMIT {$this->limit}";
        }
        return \implode(' ', $clauses);
    }

    #endregion protected
}
