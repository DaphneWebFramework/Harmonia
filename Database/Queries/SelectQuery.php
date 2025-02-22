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

namespace Harmonia\Database\Queries;

/**
 * Class for building SQL SELECT queries.
 *
 * @todo Add support for GROUP BY, HAVING, DISTINCT, and JOIN clauses.
 */
class SelectQuery extends Query
{
    /**
     * The columns to be selected. Default is '*'.
     *
     * @var string
     */
    private string $columns = '*';

    /**
     * The name of the table associated with the query.
     *
     * @var string
     */
    private ?string $tableName = null;

    /**
     * The condition for the WHERE clause.
     *
     * @var ?string
     */
    private ?string $condition = null;

    /**
     * The ORDER BY clause.
     *
     * @var ?string
     */
    private ?string $orderBy = null;

    /**
     * The LIMIT clause.
     *
     * @var ?string
     */
    private ?string $limit = null;

    #region public -------------------------------------------------------------

    /**
     * Specifies the columns to retrieve in the query.
     *
     * If this method is not called, the query defaults to selecting
     * all columns (`*`).
     *
     * @param string ...$columns
     *   Column names or expressions to select. For example: `Columns('column1',
     *   'COUNT(*) AS total')`.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If no columns are provided or if any column name is empty.
     */
    public function Columns(string ...$columns): self
    {
        $this->columns = $this->formatStringList(...$columns);
        return $this;
    }

    /**
     * Adds a FROM clause to the query.
     *
     * @param string $tableName
     *   The name of the table.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the table name is empty.
     */
    public function From(string $tableName): self
    {
        $this->tableName = $this->formatString($tableName);
        return $this;
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * @param string $condition
     *   The WHERE condition.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If the condition is empty.
     */
    public function Where(string $condition): self
    {
        $this->condition = $this->formatString($condition);
        return $this;
    }

    /**
     * Adds an ORDER BY clause to the query.
     *
     * @param string ...$columns
     *   Column names and optional sorting directions, e.g., `OrderBy('column1
     *   DESC', 'column2', 'column3 ASC')`.
     * @return self
     *   The current instance.
     * @throws \InvalidArgumentException
     *   If no columns are provided or if any column name is empty.
     */
    public function OrderBy(string ...$columns): self
    {
        $this->orderBy = $this->formatStringList(...$columns);
        return $this;
    }

    /**
     * Adds a LIMIT clause to the query.
     *
     * @param int $limit
     *   The maximum number of rows to return.
     * @param ?int $offset
     *   (Optional) The number of rows to skip before starting to return rows.
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

    protected function buildSql(): string
    {
        if ($this->tableName === null) {
            throw new \InvalidArgumentException(
                'Table name must be provided.');
        }
        $clauses = [
            "SELECT {$this->columns}",
            "FROM {$this->tableName}"
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
